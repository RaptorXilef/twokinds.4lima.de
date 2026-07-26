<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;

interface ComicRevisionRepositoryInterface
{
    public function createSnapshot(ComicPage $oldState): void;

    // Holt die letzte Revision und löscht sie aus dem Log
    public function popLatestRevision(ComicId $id): ?array;
}
