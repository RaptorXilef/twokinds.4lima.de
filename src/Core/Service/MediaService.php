<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;

final readonly class MediaService
{
    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * Skaliert ein Bild unter Beibehaltung des Seitenverhältnisses.
     */
    public function generateScaledImage(string $sourcePath, string $targetPath, int $maxWidth): bool
    {
        if (! \file_exists($sourcePath)) {
            return false;
        }

        $info = \getimagesize($sourcePath);
        if (! $info) {
            return false;
        }

        [$width, $height, $type] = $info;

        // Wenn das Bild schon klein genug ist, einfach kopieren
        if ($width <= $maxWidth) {
            return \copy($sourcePath, $targetPath);
        }

        // Neues Seitenverhältnis berechnen
        $ratio     = $maxWidth / $width;
        $newWidth  = $maxWidth;
        $newHeight = (int) \round($height * $ratio);

        $image = $this->createImageFromFile($sourcePath, $type);
        if (! $image) {
            return false;
        }

        $targetImage = \imagecreatetruecolor($newWidth, $newHeight);

        // Transparenz für WebP/PNG erhalten
        \imagealphablending($targetImage, false);
        \imagesavealpha($targetImage, true);
        $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        \imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);

        \imagecopyresampled($targetImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $success = \imagewebp($targetImage, $targetPath, 85); // 85% Qualität für exzellente Web-Performance

        \imagedestroy($image);
        \imagedestroy($targetImage);

        return $success;
    }

    /**
     * Erstellt ein exakt quadratisches Bild (Zentrierter Crop) für Social Media.
     */
    public function generateSquareCrop(string $sourcePath, string $targetPath, int $size): bool
    {
        if (! \file_exists($sourcePath)) {
            return false;
        }

        $info = \getimagesize($sourcePath);
        if (! $info) {
            return false;
        }

        [$width, $height, $type] = $info;

        $image = $this->createImageFromFile($sourcePath, $type);
        if (! $image) {
            return false;
        }

        $targetImage = \imagecreatetruecolor($size, $size);

        // Transparenz erhalten
        \imagealphablending($targetImage, false);
        \imagesavealpha($targetImage, true);
        $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        \imagefilledrectangle($targetImage, 0, 0, $size, $size, $transparent);

        // Quadratischen Ausschnitt aus der Mitte berechnen
        $minSize = \min($width, $height);
        $srcX    = (int) \round(($width - $minSize) / 2);
        $srcY    = (int) \round(($height - $minSize) / 2);

        \imagecopyresampled($targetImage, $image, 0, 0, $srcX, $srcY, $size, $size, $minSize, $minSize);

        $success = \imagewebp($targetImage, $targetPath, 80);

        \imagedestroy($image);
        \imagedestroy($targetImage);

        return $success;
    }

    private function createImageFromFile(string $path, int $type): \GdImage|false
    {
        return match ($type) {
            \IMAGETYPE_JPEG => \imagecreatefromjpeg($path),
            \IMAGETYPE_PNG  => \imagecreatefrompng($path),
            \IMAGETYPE_WEBP => \imagecreatefromwebp($path),
            \IMAGETYPE_GIF  => \imagecreatefromgif($path),
            default         => false,
        };
    }
}
