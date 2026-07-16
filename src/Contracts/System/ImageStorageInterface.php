<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface ImageStorageInterface
{
    public function uploadImage(string $folder, string $id, array $file): bool;

    public function getImageUrl(string $folder, string $id, string $fallbackIcon): string;
}
