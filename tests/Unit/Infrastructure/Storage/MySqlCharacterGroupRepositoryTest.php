<?php

declare(strict_types=1);

use App\Core\Entity\CharacterGroup;
use App\Core\ValueObject\CharacterId;
use App\Infrastructure\Storage\MySqlCharacterGroupRepository;

\uses()->group('infrastructure', 'storage', 'database');

\it('saves a character group encoding character IDs to JSON', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `character_groups`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with($this->callback(fn (array $data): bool => $data['name'] === 'Heroes'
            && $data['character_ids'] === '["char_1","char_2"]'))
        ->willReturn(true);

    $repo = new MySqlCharacterGroupRepository($pdo);
    $group = new CharacterGroup('Heroes', [new CharacterId('char_1'), new CharacterId('char_2')]);

    $repo->save($group);
})->covers(MySqlCharacterGroupRepository::class);

\it('finds all groups and decodes JSON IDs into ValueObjects', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())->method('query')->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(\PDO::FETCH_ASSOC)
        ->willReturn([
            ['name' => 'Villains', 'character_ids' => '["char_99"]', 'sort_order' => 1, 'manual_sort' => 0],
        ]);

    $repo = new MySqlCharacterGroupRepository($pdo);
    $groups = $repo->findAll();

    \expect($groups)->toHaveCount(1)
        ->and($groups[0]->name)->toBe('Villains')
        ->and($groups[0]->characterIds)->toHaveCount(1)
        ->and($groups[0]->characterIds[0])->toBeInstanceOf(CharacterId::class)
        ->and($groups[0]->characterIds[0]->value)->toBe('char_99');
})->covers(MySqlCharacterGroupRepository::class);

\it('deletes a character group', function (): void {
    $pdo = $this->createMock(\PDO::class);
    $stmt = $this->createMock(\PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with('DELETE FROM `character_groups` WHERE name = ?')
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->with(['Heroes'])->willReturn(true);

    $repo = new MySqlCharacterGroupRepository($pdo);
    $repo->delete('Heroes');
})->covers(MySqlCharacterGroupRepository::class);
