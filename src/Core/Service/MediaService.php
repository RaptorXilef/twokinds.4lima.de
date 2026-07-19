<?php

declare(strict_types=1);

namespace App\Core\Service;

final class MediaService
{
    /**
     * Skaliert ein Bild unter Beibehaltung des Seitenverhältnisses.
     * Nutzt GD-Library zur Konvertierung in WebP.
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

        $image = $this->createImageFromFile($sourcePath, $type);
        if (! $image) {
            return false;
        }

        // Muss es überhaupt skaliert werden?
        if ($width <= $maxWidth) {
            $newWidth  = $width;
            $newHeight = $height;
        } else {
            $ratio     = $maxWidth / $width;
            $newWidth  = $maxWidth;
            $newHeight = (int) \round($height * $ratio);
        }

        $targetImage = \imagecreatetruecolor($newWidth, $newHeight);

        // WICHTIG: Transparenz für PNG/WebP erhalten!
        \imagealphablending($targetImage, false);
        \imagesavealpha($targetImage, true);
        $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        \imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);

        \imagecopyresampled($targetImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // WebP mit 85% Qualität erzeugen (Perfekter Kompromiss aus Qualität und Dateigröße)
        // WebP Qualität aus der Config lesen (Standard: 85)
        $quality = (int) $this->config->get('webp_quality', 85);
        $success = \imagewebp($targetImage, $targetPath, $quality);

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

        // WICHTIG: Transparenz für PNG/WebP erhalten!
        \imagealphablending($targetImage, false);
        \imagesavealpha($targetImage, true);
        $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        \imagefilledrectangle($targetImage, 0, 0, $size, $size, $transparent);

        // Quadratischen Ausschnitt aus der Mitte berechnen
        $minSize = \min($width, $height);
        $srcX    = (int) \round(($width - $minSize) / 2);
        $srcY    = (int) \round(($height - $minSize) / 2);

        \imagecopyresampled($targetImage, $image, 0, 0, $srcX, $srcY, $size, $size, $minSize, $minSize);

        // WebP mit 80% Qualität für Thumbnails
        // WebP Qualität für Thumbnails etwas geringer (Standard: 80)
        $quality = (int) $this->config->get('webp_quality_thumb', 80);
        $success = \imagewebp($targetImage, $targetPath, $quality);

        \imagedestroy($image);
        \imagedestroy($targetImage);

        return $success;
    }

    private function createImageFromFile(string $path, int $type): \GdImage|false
    {
        return match ($type) {
            \IMAGETYPE_JPEG => @\imagecreatefromjpeg($path),
            \IMAGETYPE_PNG  => @\imagecreatefrompng($path),
            \IMAGETYPE_WEBP => @\imagecreatefromwebp($path),
            \IMAGETYPE_GIF  => @\imagecreatefromgif($path),
            default         => false,
        };
    }
}
