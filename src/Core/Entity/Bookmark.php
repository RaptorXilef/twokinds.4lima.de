<?php

declare(strict_types=1);

namespace App\Core\Entity;

use DateTimeImmutable;

final readonly class Bookmark
{
    public function __construct(
        public string $userId,
        public string $comicId,
        public DateTimeImmutable $addedAt,
    ) {
    }
}
