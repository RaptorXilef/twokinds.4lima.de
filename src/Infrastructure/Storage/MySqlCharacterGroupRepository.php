<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Core\Entity\CharacterGroup;
use App\Core\ValueObject\CharacterId;

final readonly class MySqlCharacterGroupRepository implements CharacterGroupRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function save(CharacterGroup $group): void
    {
        $charIds = \array_map(fn (CharacterId $id) => $id->value, $group->characterIds);

        $sql = 'INSERT INTO `character_groups` (name, character_ids)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE character_ids = VALUES(character_ids)';

        $this->pdo->prepare($sql)->execute([
            $group->name,
            \json_encode($charIds, \JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function findByName(string $name): ?CharacterGroup
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `character_groups` WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `character_groups` ORDER BY name ASC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    public function delete(string $name): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `character_groups` WHERE name = ?');
        $stmt->execute([$name]);
    }

    private function mapToEntity(array $row): CharacterGroup
    {
        $charIdsRaw = \json_decode($row['character_ids'] ?? '[]', true) ?? [];
        $charIds    = \array_map(fn (string $id) => new CharacterId($id), $charIdsRaw);

        return new CharacterGroup(
            name: $row['name'],
            characterIds: $charIds,
        );
    }
}
