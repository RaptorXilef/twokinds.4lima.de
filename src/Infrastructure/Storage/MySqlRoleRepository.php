<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\Role;
use App\Infrastructure\Database\Table;

final readonly class MySqlRoleRepository implements RoleRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(
        private \PDO $pdo,
        private JsonHelperInterface $jsonHelper, // Wird für die DI Container Kompatibilität beibehalten, aber intern nicht mehr zwingend benötigt
    ) {
    }

    public function loadAll(): array
    {
        $roles = [];
        $stmt  = $this->pdo->query('SELECT * FROM `' . Table::ROLES . '` ORDER BY name ASC');

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $role             = $this->hydrateEntity(Role::class, $row);
            $roles[$role->id] = $role;
        }

        return $roles;
    }

    public function save(Role $role): void
    {
        $data = $this->extractEntity($role);
        $this->executeUpsert(Table::ROLES, $data, ['id']);
    }

    public function delete(string $roleId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::ROLES . '` WHERE id = ?');
        $stmt->execute([$roleId]);
    }
}
