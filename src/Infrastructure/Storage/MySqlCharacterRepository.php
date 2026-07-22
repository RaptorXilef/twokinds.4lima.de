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
            'full_name'   => $character->fullName,
            'pic_url'     => $character->picUrl,
            'description' => $character->description,
            'alt_names'   => $character->altNames,
            'gender'      => $character->gender,
            'age'         => $character->age,
            'rank'        => $character->rank,
            'species'     => $character->species,
            'languages'   => $character->languages,
            'main_pic'    => $character->mainPic,
            'swatch_pic'  => $character->swatchPic,
            'ref_sheets'  => \json_encode($character->refSheets, \JSON_UNESCAPED_UNICODE),
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
            fullName: $row['full_name'] ?? null,
            altNames: $row['alt_names'] ?? null,
            gender: $row['gender'] ?? null,
            age: $row['age'] ?? null,
            rank: $row['rank'] ?? null,
            species: $row['species'] ?? null,
            languages: $row['languages'] ?? null,
            mainPic: $row['main_pic'] ?? null,
            swatchPic: $row['swatch_pic'] ?? null,
            refSheets: \json_decode($row['ref_sheets'] ?? '[]', true) ?? [],
        );
    }
}
