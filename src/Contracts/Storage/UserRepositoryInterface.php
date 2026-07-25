<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function save(User $user): void;

    public function delete(string $id): void;
}
