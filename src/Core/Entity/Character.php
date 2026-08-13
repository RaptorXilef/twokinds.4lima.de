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
    ) {}
    public function getKeidranAgeEquivalent(): ?string
    {
        $isKeidran = \stripos($this->species ?? '', 'keidran') !== false || \stripos($this->subspecies ?? '', 'keidran') !== false;
        if (!$isKeidran || $this->age === null) {
            return null;
        }
        return \preg_replace_callback('/(\d+)/', function (array $m): string {
            $raw = (int) $m[1];
            $map = [1 => 3, 2 => 6, 3 => 9, 4 => 12, 5 => 14, 6 => 16, 7 => 18, 8 => 20, 9 => 22, 10 => 24, 11 => 27, 12 => 29, 13 => 32, 14 => 35, 15 => 39, 16 => 44, 17 => 49, 18 => 54, 19 => 60, 20 => 66, 21 => 73, 22 => 80,];
            return (string) ($map[$raw] ?? \round($raw * 2.5));
        }, $this->age);
    }
}
