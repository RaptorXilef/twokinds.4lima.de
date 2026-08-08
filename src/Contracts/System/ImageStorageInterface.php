<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface ImageStorageInterface
{
    /**
     * @param array<string, mixed> $file
     */
    public function uploadImage(string $folder, string $id, array $file): bool;

    public function getImageUrl(string $folder, string $id, string $fallbackIcon): string;

    public function deleteComicMedia(string $comicId): int;

    public function deleteCharacterMedia(string $folder, string $filename): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCharacterMediaFiles(string $folder): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listComicMediaFiles(): array;
}
