<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Entity\Chapter;

final readonly class MySqlChapterRepository implements ChapterRepositoryInterface
{
    use DynamicSqlTrait;

    public function __construct(private \PDO $pdo)
    {
    }

    public function save(Chapter $chapter): void
    {
        $data = [
            'id'          => $chapter->id,
            'title'       => $chapter->title,
            'description' => $chapter->description,
        ];
        $this->executeUpsert('chapters', $data, ['id']);
    }

    public function findAll(): array
    {
        // Sortiert numerische IDs korrekt (1, 2, 10) und Text-IDs ans Ende
        $stmt = $this->pdo->query('SELECT * FROM `chapters` ORDER BY CAST(id AS UNSIGNED) ASC, id ASC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map(fn ($r) => new Chapter(
            $r['id'],
            $r['title'],
            $r['description'] ?? '',
        ), $rows);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `chapters` WHERE id = ?');
        $stmt->execute([$id]);
    }
}
