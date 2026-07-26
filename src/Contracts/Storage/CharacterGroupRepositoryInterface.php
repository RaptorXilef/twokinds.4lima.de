<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\CharacterGroup;

interface CharacterGroupRepositoryInterface
{
    public function save(CharacterGroup $group): void;

    public function findByName(string $name): ?CharacterGroup;

    /**
     * @return CharacterGroup[]
     */
    public function findAll(): array;

    public function delete(string $name): void;
}
