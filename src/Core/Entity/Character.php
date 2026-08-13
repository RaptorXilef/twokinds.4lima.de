<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\CharacterId;

final readonly class Character
{
    /**
     * @param array<int, string> $refSheets
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        public CharacterId $id,
        public string $name,
        public ?string $picUrl,
        public ?string $description,
        public ?string $fullName = null,
        public ?string $altNames = null,
        public ?string $gender = null,
        public ?string $age = null,
        public ?string $keidranAge = null,
        public ?string $rank = null,
        public ?string $species = null,
        public ?string $subspecies = null,
        public ?string $languages = null,
        public ?string $hairColor = null,
        public ?string $eyeColor = null,
        public ?string $furColor = null,
        public ?string $mainPic = null,
        public ?string $swatchPic = null,
        public array $refSheets = [],
        public bool $isDead = false,
    ) {
    }
}
