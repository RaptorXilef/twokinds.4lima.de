<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\CharacterId;
use InvalidArgumentException;

final readonly class CharacterGroup
{
    /**
     * @var array<int, CharacterId>
     */
    public array $characterIds;

    /**
     * @param array<int, CharacterId> $characterIds
     */
    public function __construct(
        public string $name,
        array $characterIds,
        public int $sortOrder = 0,
        public bool $manualSort = false,
    ) {
        // Strikte Typisierung für das Array erzwingen
        foreach ($characterIds as $charId) {
            if (!$charId instanceof CharacterId) {
                throw new InvalidArgumentException(
                    'Das characterIds Array darf nur Instanzen von CharacterId enthalten.',
                );
            }
        }
        $this->characterIds = \array_values($characterIds);
    }
}
