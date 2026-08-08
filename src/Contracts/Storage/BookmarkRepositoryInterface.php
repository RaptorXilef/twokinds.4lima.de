<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\Bookmark;

interface BookmarkRepositoryInterface
{
    /**
     * @return Bookmark[]
     */
    public function findByUser(string $userId): array;

    public function add(string $userId, string $comicId): void;

    public function remove(string $userId, string $comicId): void;

    /**
     * @param array<int, string> $comicIds
     */
    public function replaceUserBookmarks(string $userId, array $comicIds): void;
}
