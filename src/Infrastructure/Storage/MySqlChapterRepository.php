<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Core\Entity\Chapter;
use App\Infrastructure\Database\Table;
use PDO;

final readonly class MySqlChapterRepository implements ChapterRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private PDO $pdo)
    {
    }

    public function save(Chapter $chapter): void
    {
        $data = $this->extractEntity($chapter);
        $this->executeUpsert(Table::CHAPTERS, $data, ['id']);
    }

    public function findAll(): array
    {
        // Sortiert numerische IDs korrekt (1, 2, 10) und Text-IDs ans Ende
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::CHAPTERS . '` ORDER BY CAST(id AS UNSIGNED) ASC, id ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return \array_map(fn (array $r): Chapter => $this->hydrateEntity(Chapter::class, $r), $rows);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::CHAPTERS . '` WHERE id = ?');
        $stmt->execute([$id]);
    }
}
