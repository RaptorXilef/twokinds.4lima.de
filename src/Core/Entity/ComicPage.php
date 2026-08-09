<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use InvalidArgumentException;

final readonly class ComicPage
{
    /**
     * @var array<int, CharacterId>
     */
    public array $characterIds;

    /**
     * @param array<int, CharacterId> $characterIds
     * @param array<int, string> $userIds
     */
    public function __construct(
        public ComicId $id,
        public string $type,
        public string $name,
        public ?string $transcript,
        public ?string $chapterId,
        array $characterIds,
        public string $originalUrl,
        public string $sketchUrl,
        public array $userIds = [],
        public ?int $imageUpdatedAt = null, // Ersetzt die image_cache.json
    ) {
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
