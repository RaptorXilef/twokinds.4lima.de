<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

final readonly class MySqlComicRepository implements ComicRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function save(ComicPage $comic): void
    {
        $charIds = \array_map(fn (CharacterId $id) => $id->value, $comic->characterIds);

        $sql = 'INSERT INTO `comics`
                (id, type, name, transcript, chapter_id, character_ids, original_url, sketch_url, image_updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                type = VALUES(type), name = VALUES(name), transcript = VALUES(transcript),
                chapter_id = VALUES(chapter_id), character_ids = VALUES(character_ids),
                original_url = VALUES(original_url), sketch_url = VALUES(sketch_url),
                image_updated_at = VALUES(image_updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $comic->id->value,
            $comic->type,
            $comic->name,
            $comic->transcript,
            $comic->chapterId,
            \json_encode($charIds, \JSON_UNESCAPED_UNICODE),
            $comic->originalUrl,
            $comic->sketchUrl,
            $comic->imageUpdatedAt,
        ]);
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
        $charIds    = \array_map(fn (string $id) => new CharacterId($id), $charIdsRaw);

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
}
