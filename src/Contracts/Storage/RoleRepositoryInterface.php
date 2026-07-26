<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\Role;

interface RoleRepositoryInterface
{
    /**
     * @return array<string, Role>
     */
    public function loadAll(): array;

    public function save(Role $role): void;

    public function delete(string $roleId): void;
}
