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

    /**
     * Verarbeitet hochgeladene Comic-Bilder (Hires/Lowres), skaliert sie,
     * generiert Thumbnails & Social-Media Bilder und speichert sie am korrekten Ort.
     */
    public function processAndStoreComicMedia(string $comicId, ?string $tmpHires, ?string $tmpLowres): void;

    /**
     * Verarbeitet den Massen-Upload von Profilbildern (Galerie).
     * Gibt die Anzahl der erfolgreich verarbeiteten Bilder zurück.
     */
    public function processMassProfileUpload(array $files): int;

    /**
     * Verarbeitet hochgeladene Bilder für einen einzelnen Charakter.
     * Gibt ein Array mit den neuen Dateinamen und eventuellen Warnungen zurück.
     */
    public function processCharacterImages(string $safeName, array $files): array;
}
