<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\CharacterId;

final class CharacterGroup
{
    /**
     * @var CharacterId[]
     */
    public readonly array $characterIds;

    public function __construct(
        public readonly string $name,
        array $characterIds,
        public readonly int $sortOrder = 0,
        public readonly bool $manualSort = false,
    ) {
        // Strikte Typisierung für das Array erzwingen
        foreach ($characterIds as $charId) {
            if (! $charId instanceof CharacterId) {
                throw new \InvalidArgumentException('Das characterIds Array darf nur Instanzen von CharacterId enthalten.');
            }
        }
        $this->characterIds = \array_values($characterIds);
    }
}
