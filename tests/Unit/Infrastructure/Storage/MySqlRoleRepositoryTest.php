<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\Role;
use App\Infrastructure\Storage\MySqlRoleRepository;
use PDO;
use PDOStatement;

\uses()->group('infrastructure', 'storage', 'database');

\it('loads all roles from database', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $jsonHelper = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())
        ->method('query')
        ->with($this->stringContains('SELECT * FROM `roles`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('fetchAll')
        ->with(PDO::FETCH_ASSOC)
        ->willReturn([
            ['id' => 'admin', 'name' => 'Administrator', 'permissions' => '["*"]'],
            ['id' => 'user', 'name' => 'Benutzer', 'permissions' => '[]'],
        ]);

    $repo = new MySqlRoleRepository($pdo, $jsonHelper);
    $roles = $repo->loadAll();

    \expect($roles)->toHaveCount(2)
        ->and($roles)->toHaveKey('admin')
        ->and($roles['admin'])->toBeInstanceOf(Role::class)
        ->and($roles['admin']->permissions)->toBe(['*']);
})->covers(MySqlRoleRepository::class);

\it('saves a role using dynamic upsert', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $jsonHelper = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with($this->stringContains('INSERT INTO `roles`'))
        ->willReturn($stmt);

    $stmt->expects($this->once())->method('execute')->willReturn(true);

    $repo = new MySqlRoleRepository($pdo, $jsonHelper);
    $role = new Role('editor', 'Redakteur', ['comics.edit', 'media.upload']);

    $repo->save($role);
})->covers(MySqlRoleRepository::class);

\it('deletes a role by id', function (): void {
    $pdo = $this->createMock(PDO::class);
    $stmt = $this->createMock(PDOStatement::class);
    $jsonHelper = $this->createStub(JsonHelperInterface::class);

    $pdo->expects($this->once())
        ->method('prepare')
        ->with('DELETE FROM `roles` WHERE id = ?')
        ->willReturn($stmt);

    $stmt->expects($this->once())
        ->method('execute')
        ->with(['editor'])
        ->willReturn(true);

    $repo = new MySqlRoleRepository($pdo, $jsonHelper);
    $repo->delete('editor');
})->covers(MySqlRoleRepository::class);
