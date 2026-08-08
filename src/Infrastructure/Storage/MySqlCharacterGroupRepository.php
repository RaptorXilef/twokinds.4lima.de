<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Core\Entity\CharacterGroup;
use App\Core\ValueObject\CharacterId;
use App\Infrastructure\Database\Table;
use PDO;

final readonly class MySqlCharacterGroupRepository implements CharacterGroupRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private PDO $pdo)
    {
    }

    public function save(CharacterGroup $group): void
    {
        $charIds = \array_map(fn (CharacterId $id): string => $id->value, $group->characterIds);

        $data = $this->extractEntity($group, [
            'character_ids' => \json_encode($charIds, \JSON_UNESCAPED_UNICODE),
        ]);

        $this->executeUpsert(Table::CHARACTER_GROUPS, $data, ['name']);
    }

    public function findByName(string $name): ?CharacterGroup
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::CHARACTER_GROUPS . '` WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
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
        // WICHTIG: Hier greift jetzt die neue Sortierung!
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::CHARACTER_GROUPS . '` ORDER BY sort_order ASC, name ASC');
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        /** @var array<int, CharacterGroup> $groups */
        $groups = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            /** @var array<string, mixed> $validRow */
            $validRow = $row;

            $groups[] = $this->mapToEntity($validRow);
        }

        return $groups;
    }

    public function delete(string $name): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::CHARACTER_GROUPS . '` WHERE name = ?');
        $stmt->execute([$name]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapToEntity(array $row): CharacterGroup
    {
        $charIdsRawJson = \is_string($row['character_ids'] ?? '') ? $row['character_ids'] : '[]';
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

        return $this->hydrateEntity(CharacterGroup::class, $row, [
            'characterIds' => $charIds,
        ]);
    }
}
