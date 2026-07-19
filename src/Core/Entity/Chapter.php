<?php

declare(strict_types=1);

namespace App\Core\Entity;

final readonly class Chapter
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description = '',
    ) {
    }
}
