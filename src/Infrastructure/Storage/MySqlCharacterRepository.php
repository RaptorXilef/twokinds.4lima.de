<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;
use App\Infrastructure\Database\Table;

final readonly class MySqlCharacterRepository implements CharacterRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private \PDO $pdo)
    {
    }

    public function save(Character $character): void
    {
        $data = $this->extractEntity($character);
        $this->executeUpsert(Table::CHARACTERS, $data, ['id']);
    }

    public function findById(CharacterId $id): ?Character
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::CHARACTERS . '` WHERE id = ? LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrateEntity(Character::class, $row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::CHARACTERS . '` ORDER BY name ASC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map(fn (array $r): object => $this->hydrateEntity(Character::class, $r), $rows);
    }

    public function delete(CharacterId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::CHARACTERS . '` WHERE id = ?');
        $stmt->execute([$id->value]);
    }
}
