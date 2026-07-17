<?php
declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;

final readonly class MySqlComicRevisionRepository implements ComicRevisionRepositoryInterface
{
    public function __construct(
        private \PDO $pdo,
        private ClockInterface $clock,
        private ConfigInterface $config // Config hinzugefügt
    ) {}

    public function createSnapshot(ComicPage $oldState): void
    {
        $comicIdStr = $oldState->id->value;

        $snapshotData = [
            'type' => $oldState->type,
            'name' => $oldState->name,
            'transcript' => $oldState->transcript,
            'chapter_id' => $oldState->chapterId,
            'character_ids' => \array_map(fn (CharacterId $id) => $id->value, $oldState->characterIds),
            'original_url' => $oldState->originalUrl,
            'sketch_url' => $oldState->sketchUrl,
            'image_updated_at' => $oldState->imageUpdatedAt,
        ];

        // 1. Neuen Snapshot einfügen
        $stmtInsert = $this->pdo->prepare('INSERT INTO `comic_revisions` (`comic_id`, `revision_data`, `created_at`) VALUES (?, ?, ?)');
        $stmtInsert->execute([
            $comicIdStr,
            \json_encode($snapshotData, \JSON_UNESCAPED_UNICODE),
            $this->clock->now()->format('Y-m-d H:i:s'),
        ]);

        // 2. Rolling History Limit durchsetzen (Alte Snapshots löschen)
        $limit = (int) $this->config->get('comic_revision_limit', 10);

        if ($limit > 0) {
            // MySQL Workaround: Man kann nicht DELETE und SELECT auf dieselbe Tabelle ohne Subquery-Alias machen
            $stmtCleanup = $this->pdo->prepare('
                DELETE FROM `comic_revisions`
                WHERE `comic_id` = :cid
                AND `id` NOT IN (
                    SELECT id FROM (
                        SELECT id FROM `comic_revisions`
                        WHERE `comic_id` = :cid2
                        ORDER BY `created_at` DESC
                        LIMIT :limit
                    ) tmp
                )
            ');

            $stmtCleanup->bindValue(':cid', $comicIdStr);
            $stmtCleanup->bindValue(':cid2', $comicIdStr);
            $stmtCleanup->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmtCleanup->execute();
        }
    }
}
