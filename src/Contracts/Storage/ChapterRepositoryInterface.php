<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\Chapter;

interface ChapterRepositoryInterface
{
    public function save(Chapter $chapter): void;

    public function findAll(): array;

    public function delete(string $id): void;
}
