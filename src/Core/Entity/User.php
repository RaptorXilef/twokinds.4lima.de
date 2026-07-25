<?php

declare(strict_types=1);

namespace App\Core\Entity;

final readonly class User
{
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public string $passwordHash,
        public string $roleId,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
