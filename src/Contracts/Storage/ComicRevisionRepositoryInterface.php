<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\ComicPage;

interface ComicRevisionRepositoryInterface
{
    public function createSnapshot(ComicPage $oldState): void;
}
