<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\ComicPage;
use App\Core\ValueObject\ComicId;

interface ComicRepositoryInterface
{
    public function save(ComicPage $comic): void;

    public function findById(ComicId $id): ?ComicPage;

    /**
     * @return ComicPage[]
     */
    public function findAll(): array;

    public function delete(ComicId $id): void;
}
