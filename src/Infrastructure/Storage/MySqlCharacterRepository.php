<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;

final readonly class MySqlCharacterRepository implements CharacterRepositoryInterface
{
    use DynamicSqlTrait;

    public function __construct(private \PDO $pdo)
    {
    }

    public function save(Character $character): void
    {
        $data = [
            'id'          => $character->id->value,
            'name'        => $character->name,
            'pic_url'     => $character->picUrl,
            'description' => $character->description,
        ];

        $this->executeUpsert('characters', $data, ['id']);
    }

    public function findById(CharacterId $id): ?Character
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `characters` WHERE id = ? LIMIT 1');
        $stmt->execute([$id->value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `characters` ORDER BY name ASC');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    public function delete(CharacterId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `characters` WHERE id = ?');
        $stmt->execute([$id->value]);
    }

    private function mapToEntity(array $row): Character
    {
        return new Character(
            id: new CharacterId($row['id']),
            name: $row['name'],
            picUrl: $row['pic_url'],
            description: $row['description'],
        );
    }
}
