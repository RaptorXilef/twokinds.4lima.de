<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use App\Infrastructure\Database\Table;
use Exception;
use PDO;

final readonly class MySqlComicRepository implements ComicRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private PDO $pdo)
    {
    }

    public function save(ComicPage $comic): void
    {
        // Da 'characterIds' ein Array aus Objekten ist, nutzen wir den Override, um es zu einem JSON-String aus reinen IDs zu machen.
        $charIds = \array_map(fn (CharacterId $id): string => $id->value, $comic->characterIds);

        $data = $this->extractEntity($comic, [
            'character_ids' => \json_encode($charIds, \JSON_UNESCAPED_UNICODE),
        ]);

        // Die ID soll bei einem Update natürlich nicht überschrieben werden
        $this->executeUpsert(Table::COMICS, $data, ['id']);
    }

    public function findById(ComicId $id): ?ComicPage
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::COMICS . '` WHERE id = ? LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $validRow */
        $validRow = $row;

        return $this->mapToEntity($validRow);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::COMICS . '` ORDER BY id DESC');
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        /** @var array<int, ComicPage> $comics */
        $comics = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            /** @var array<string, mixed> $validRow */
            $validRow = $row;

            $comics[] = $this->mapToEntity($validRow);
        }

        return $comics;
    }

    public function delete(ComicId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::COMICS . '` WHERE id = ?');
        $stmt->execute([$id->value]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapToEntity(array $row): ComicPage
    {
        // Da die Property 'characterIds' CharacterId-Objekte erwartet, parsen wir sie manuell und geben sie als Override mit.
        $rawCharIds = $row['character_ids'] ?? '[]';
        $charIdsRawJson = \is_string($rawCharIds) ? $rawCharIds : '[]';
        $charIdsRaw = \json_decode($charIdsRawJson, true);
        $charIdsArr = \is_array($charIdsRaw) ? $charIdsRaw : [];

        /** @var array<int, CharacterId> $charIds */
        $charIds = [];
        foreach ($charIdsArr as $idStr) {
            if (!\is_string($idStr)) {
                continue;
            }

            $charIds[] = new CharacterId($idStr);
        }

        return $this->hydrateEntity(ComicPage::class, $row, [
            'characterIds' => $charIds,
        ]);
    }

    public function renameComicId(ComicId $oldId, ComicId $newId): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt1 = $this->pdo->prepare('UPDATE `' . Table::COMICS . '` SET `id` = ? WHERE `id` = ?');
            $stmt1->execute([$newId->value, $oldId->value]);

            $stmt2 = $this->pdo->prepare('UPDATE `' . Table::COMIC_REVISIONS . '` SET `comic_id` = ? WHERE `comic_id` = ?');
            $stmt2->execute([$newId->value, $oldId->value]);

            $stmt3 = $this->pdo->prepare('UPDATE `' . Table::REPORTS . '` SET `comic_id` = ? WHERE `comic_id` = ?');
            $stmt3->execute([$newId->value, $oldId->value]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();

            throw clone $e;
        }
    }
}
