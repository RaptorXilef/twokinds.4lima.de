<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface MediaServiceInterface
{
    public function generateScaledImage(string $sourcePath, string $targetPath, int $maxWidth): bool;

    public function generateSquareCrop(string $sourcePath, string $targetPath, int $size): bool;

    public function renameComicMedia(string $oldId, string $newId): void;

    public function generateManualCrop(
        string $sourcePath,
        string $targetPath,
        int $cropX,
        int $cropY,
        int $cropWidth,
        int $cropHeight,
        int $finalWidth = 1200,
        int $finalHeight = 630,
    ): bool;

    public function autoGenerateSocialMediaJpg(string $sourcePath, string $targetPath): void;
}
