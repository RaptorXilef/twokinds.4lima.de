<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\Chapter;

interface ChapterRepositoryInterface
{
    public function save(Chapter $chapter): void;

    /**
     * @return array<int, Chapter>
     */
    public function findAll(): array;

    public function delete(string $id): void;
}
