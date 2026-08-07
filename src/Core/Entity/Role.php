<?php

declare(strict_types=1);

namespace App\Core\Entity;

final readonly class Role
{
    /**
     * @param array<int, string> $permissions
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $permissions,
    ) {
    }
}
