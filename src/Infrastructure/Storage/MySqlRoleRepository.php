<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\Role;

final readonly class MySqlRoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private \PDO $pdo,
        private JsonHelperInterface $jsonHelper,
    ) {
    }

    public function loadAll(): array
    {
        $roles = [];
        $stmt  = $this->pdo->query('SELECT * FROM `roles` ORDER BY name ASC');

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $perms = \is_string($row['permissions'])
                ? $this->jsonHelper->decode($row['permissions'])
                : $row['permissions'];

            $roles[$row['id']] = new Role(
                $row['id'],
                $row['name'],
                $perms ?? [],
            );
        }

        return $roles;
    }

    public function save(Role $role): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO `roles` (`id`, `name`, `permissions`)
            VALUES (:id, :name, :permissions)
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `permissions` = VALUES(`permissions`)
        ');

        $stmt->execute([
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => \json_encode($role->permissions, \JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function delete(string $roleId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM `roles` WHERE id = ?');
        $stmt->execute([$roleId]);
    }
}
