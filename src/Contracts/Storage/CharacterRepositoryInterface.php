<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\Character;
use App\Core\ValueObject\CharacterId;

interface CharacterRepositoryInterface
{
    public function save(Character $character): void;

    public function findById(CharacterId $id): ?Character;

    /**
     * @return Character[]
     */
    public function findAll(): array;

    public function delete(CharacterId $id): void;
}
