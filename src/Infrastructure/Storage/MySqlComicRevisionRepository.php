<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\Database\Table;
use PDO;

final readonly class MySqlComicRevisionRepository implements ComicRevisionRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
        private ConfigInterface $config,
    ) {
    }

    public function createSnapshot(ComicPage $oldState): void
    {
        $comicIdStr = $oldState->id->value;

        $snapshotData = [
            'type' => $oldState->type,
            'name' => $oldState->name,
            'transcript' => $oldState->transcript,
            'chapter_id' => $oldState->chapterId,
            'character_ids' => \array_map(fn (CharacterId $id): string => $id->value, $oldState->characterIds),
            'user_ids' => $oldState->userIds,
            'original_url' => $oldState->originalUrl,
            'sketch_url' => $oldState->sketchUrl,
            'image_updated_at' => $oldState->imageUpdatedAt,
        ];

        // 1. Neuen Snapshot einfügen
        $stmtInsert = $this->pdo->prepare('INSERT INTO `' . Table::COMIC_REVISIONS . '` (`comic_id`, `revision_data`, `created_at`) VALUES (?, ?, ?)');
        $stmtInsert->execute([
            $comicIdStr,
            \json_encode($snapshotData, \JSON_UNESCAPED_UNICODE),
            $this->clock->now()->format('Y-m-d H:i:s'),
        ]);

        // 2. Rolling History Limit durchsetzen (Alte Snapshots löschen)
        $limitRaw = $this->config->get('comic_revision_limit', 10);
        $limit = \is_scalar($limitRaw) ? (int) $limitRaw : 10;

        if ($limit <= 0) {
            return;
        }

        // MySQL Workaround: Man kann nicht DELETE und SELECT auf dieselbe Tabelle ohne Subquery-Alias machen
        $stmtCleanup = $this->pdo->prepare('
			DELETE FROM `' . Table::COMIC_REVISIONS . '`
			WHERE `comic_id` = :cid
			AND `id` NOT IN (
				SELECT id FROM (
					SELECT id FROM `' . Table::COMIC_REVISIONS . '`
					WHERE `comic_id` = :cid2
					ORDER BY `created_at` DESC
					LIMIT :limit
				) tmp
			)
		');

        $stmtCleanup->bindValue(':cid', $comicIdStr);
        $stmtCleanup->bindValue(':cid2', $comicIdStr);
        $stmtCleanup->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmtCleanup->execute();
    }

    public function popLatestRevision(ComicId $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT `id`, `revision_data` FROM `' . Table::COMIC_REVISIONS . '` WHERE `comic_id` = ? ORDER BY `created_at` DESC LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null; // Kein Snapshot vorhanden
        }

        // Snapshot löschen, damit man mehrfach zurückgehen kann (Strg+Z, Strg+Z...)
        $delStmt = $this->pdo->prepare('DELETE FROM `' . Table::COMIC_REVISIONS . '` WHERE `id` = ?');
        $delStmt->execute([$row['id'] ?? 0]);

        $dataJson = \is_string($row['revision_data'] ?? null) ? $row['revision_data'] : '{}';
        $data = \json_decode($dataJson, true);

        return \is_array($data) ? $data : null;
    }

    public function popLatestDeletedRevision(): ?array
    {
        // Sucht ein Backup, dessen comic_id in der Haupttabelle nicht mehr existiert
        $stmt = $this->pdo->query('
            SELECT r.id, r.comic_id, r.revision_data
            FROM `' . Table::COMIC_REVISIONS . '` r
            LEFT JOIN `' . Table::COMICS . '` c ON r.comic_id = c.id
            WHERE c.id IS NULL
            ORDER BY r.created_at DESC
            LIMIT 1
        ');

        if ($stmt === false) {
            return null;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        $delStmt = $this->pdo->prepare('DELETE FROM `' . Table::COMIC_REVISIONS . '` WHERE `id` = ?');
        $delStmt->execute([$row['id'] ?? 0]);

        $dataJson = \is_string($row['revision_data'] ?? null) ? $row['revision_data'] : '{}';
        $dataRaw = \json_decode($dataJson, true);

        if (!\is_array($dataRaw)) {
            $dataRaw = [];
        }

        /** @var array<string, mixed> $dataRaw */
        $data = $dataRaw;

        $data['comic_id'] = \is_string($row['comic_id'] ?? null) ? $row['comic_id'] : '';

        return $data;
    }
}
