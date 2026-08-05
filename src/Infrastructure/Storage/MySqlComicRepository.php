<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

final readonly class MySqlComicRepository implements ComicRepositoryInterface
{
    use DynamicSqlTrait;

    public function __construct(private \PDO $pdo)
    {
    }

    public function save(ComicPage $comic): void
    {
        $charIds = \array_map(fn (CharacterId $id): string => $id->value, $comic->characterIds);

        $data = [
            'id'               => $comic->id->value,
            'type'             => $comic->type,
            'name'             => $comic->name,
            'transcript'       => $comic->transcript,
            'chapter_id'       => $comic->chapterId,
            'character_ids'    => \json_encode($charIds, \JSON_UNESCAPED_UNICODE),
            'original_url'     => $comic->originalUrl,
            'sketch_url'       => $comic->sketchUrl,
            'image_updated_at' => $comic->imageUpdatedAt,
        ];

        // Die ID soll bei einem Update natürlich nicht überschrieben werden
        $this->executeUpsert('comics', $data, ['id']);
    }

    public function findById(ComicId $id): ?ComicPage
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `comics` WHERE id = ? LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `comics` ORDER BY id DESC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    public function delete(ComicId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `comics` WHERE id = ?');
        $stmt->execute([$id->value]);
    }

    private function mapToEntity(array $row): ComicPage
    {
        $charIdsRaw = \json_decode($row['character_ids'] ?? '[]', true) ?? [];
        $charIds    = \array_map(fn (string $id): CharacterId => new CharacterId($id), $charIdsRaw);

        return new ComicPage(
            id: new ComicId($row['id']),
            type: $row['type'],
            name: $row['name'],
            transcript: $row['transcript'],
            chapterId: $row['chapter_id'],
            characterIds: $charIds,
            originalUrl: $row['original_url'] ?? '',
            sketchUrl: $row['sketch_url'] ?? '',
            imageUpdatedAt: $row['image_updated_at'] !== null ? (int) $row['image_updated_at'] : null,
        );
    }

    public function renameComicId(ComicId $oldId, ComicId $newId): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt1 = $this->pdo->prepare('UPDATE `comics` SET `id` = ? WHERE `id` = ?');
            $stmt1->execute([$newId->value, $oldId->value]);

            $stmt2 = $this->pdo->prepare('UPDATE `comic_revisions` SET `comic_id` = ? WHERE `comic_id` = ?');
            $stmt2->execute([$newId->value, $oldId->value]);

            $stmt3 = $this->pdo->prepare('UPDATE `reports` SET `comic_id` = ? WHERE `comic_id` = ?');
            $stmt3->execute([$newId->value, $oldId->value]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();

            throw clone $e;
        }
    }
}
