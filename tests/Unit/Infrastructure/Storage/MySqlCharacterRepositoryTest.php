<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;
use App\Infrastructure\Storage\MySqlCharacterRepository;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('saves a character using the dynamic upsert trait', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `characters`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->willReturn(true);

    $repo = new MySqlCharacterRepository($pdo);
    $char = new Character(new CharacterId('char_0001'), 'Trace', null, null);

    $repo->save($char);
})->covers(MySqlCharacterRepository::class);

\it('finds a character by id and hydrates it', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('SELECT * FROM `characters` WHERE id = ?'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['char_1234']);

    $stmt->expects($this->once())
        ->method('fetch')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            'id' => 'char_1234',
            'name' => 'Trace',
            'pic_url' => 'trace.webp',
            'description' => 'A Templar.',
            'full_name' => 'Trace Legacy',
        ]);

    $repo = new MySqlCharacterRepository($pdo);
    $character = $repo->findById(new CharacterId('char_1234'));

    \expect($character)->toBeInstanceOf(Character::class)
        ->and($character->name)->toBe('Trace')
        ->and($character->fullName)->toBe('Trace Legacy');
})->covers(MySqlCharacterRepository::class);

\it('returns null if character is not found', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetch')
        ->willReturn(false);

    $repo = new MySqlCharacterRepository($pdo);
    \expect($repo->findById(new CharacterId('char_9999')))->toBeNull();
})->covers(MySqlCharacterRepository::class);

\it('returns all characters from database', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('query')
        ->with($this->stringContains('SELECT * FROM `characters`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            ['id' => 'char_0001', 'name' => 'Trace'],
            ['id' => 'char_0002', 'name' => 'Flora'],
        ]);

    $repo = new MySqlCharacterRepository($pdo);
    $characters = $repo->findAll();

    \expect($characters)->toHaveCount(2)
        ->and($characters[0])->toBeInstanceOf(Character::class)
        ->and($characters[1]->name)->toBe('Flora');
})->covers(MySqlCharacterRepository::class);

\it('deletes a character by its id', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with('DELETE FROM `characters` WHERE id = ?')
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['char_0001'])
        ->willReturn(true);

    $repo = new MySqlCharacterRepository($pdo);
    $repo->delete(new CharacterId('char_0001'));
})->covers(MySqlCharacterRepository::class);
