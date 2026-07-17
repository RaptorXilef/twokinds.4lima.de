<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

final class ComicPage
{
    /**
     * @var CharacterId[]
     */
    public readonly array $characterIds;

    public function __construct(
        public readonly ComicId $id,
        public readonly string $type,
        public readonly string $name,
        public readonly ?string $transcript,
        public readonly ?string $chapterId,
        array $characterIds,
        public readonly string $originalUrl,
        public readonly string $sketchUrl,
    ) {
        foreach ($characterIds as $charId) {
            if (! $charId instanceof CharacterId) {
                throw new \InvalidArgumentException('Das characterIds Array darf nur Instanzen von CharacterId enthalten.');
            }
        }
        $this->characterIds = \array_values($characterIds);
    }
}
