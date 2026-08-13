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
    ) {
    }

    public function getKeidranAgeEquivalent(): ?string
    {
        $isKeidran = \stripos($this->species ?? '', 'keidran') !== false || \stripos($this->subspecies ?? '', 'keidran') !== false;
        if (!$isKeidran || $this->age === null) {
            return null;
        }

        // Mathematische Kurvenanpassung (Polynom 2. Grades)
        // Bildet Tom's exponentielle Alterung extrem gut und unendlich ab:
        // Formel: Menschenjahre = 0.12 * x^2 + 0.8 * x + 2.0
        return \preg_replace_callback('/(\d+)/', function (array $m): string {
            $raw = (int) $m[1];
            $humanAge = (int) \round(0.12 * ($raw ** 2) + 0.8 * $raw + 2.0);

            return (string) $humanAge;
        }, $this->age);
    }
}
