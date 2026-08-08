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

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findAll(): array
    {
        // WICHTIG: Hier greift jetzt die neue Sortierung!
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::CHARACTER_GROUPS . '` ORDER BY sort_order ASC, name ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    public function delete(string $name): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::CHARACTER_GROUPS . '` WHERE name = ?');
        $stmt->execute([$name]);
    }

    private function mapToEntity(array $row): CharacterGroup
    {
        $charIdsRaw = \json_decode($row['character_ids'] ?? '[]', true) ?? [];
        $charIds = \array_map(fn (string $id): CharacterId => new CharacterId($id), $charIdsRaw);

        return $this->hydrateEntity(CharacterGroup::class, $row, [
            'characterIds' => $charIds,
        ]);
    }
}
