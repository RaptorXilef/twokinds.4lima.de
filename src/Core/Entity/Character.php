<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\CharacterId;

final readonly class Character
{
    public function __construct(
        public CharacterId $id,
        public string $name,
        public ?string $picUrl,
        public ?string $description,
        public ?string $altNames = null,
        public ?string $rank = null,
    ) {
    }
}
