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
     * Wenn das Bild bereits WebP ist und nicht skaliert werden muss (oder es das Original-Hires ist),
     * wird es verlustfrei 1:1 kopiert.
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

        // Smart-Copy: Wenn es schon WebP ist UND (kleiner als Max-Breite ODER Hires(4000px) ist)
        // -> Kein Re-Encoding! 1:1 kopieren für 100% Original-Qualität.
        if ($type === \IMAGETYPE_WEBP && ($width <= $maxWidth || $maxWidth >= 4000)) {
            return \copy($sourcePath, $targetPath);
        }

        $ratio     = $width > $maxWidth ? ($maxWidth / $width) : 1;
        $newWidth  = (int) \round($width * $ratio);
        $newHeight = (int) \round($height * $ratio);

        $image = $this->createImageFromFile($sourcePath, $type);
        if (! $image) {
            return false;
        }

        $targetImage = \imagecreatetruecolor($newWidth, $newHeight);
        $this->applyBackground($targetImage, $newWidth, $newHeight);

        \imagecopyresampled($targetImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $quality = $this->config->get('webp_lossless', false) ? 100 : (int) $this->config->get('webp_quality', 85);
        $success = \imagewebp($targetImage, $targetPath, $quality);

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
        $this->applyBackground($targetImage, $size, $size);

        // Quadratischen Ausschnitt aus der Mitte berechnen
        $minSize = \min($width, $height);
        $srcX    = (int) \round(($width - $minSize) / 2);
        $srcY    = (int) \round(($height - $minSize) / 2);

        \imagecopyresampled($targetImage, $image, 0, 0, $srcX, $srcY, $size, $size, $minSize, $minSize);

        $quality = $this->config->get('webp_lossless', false) ? 100 : (int) $this->config->get('webp_quality_thumb', 80);
        $success = \imagewebp($targetImage, $targetPath, $quality);

        return $success;
    }

    /**
     * Setzt den Hintergrund basierend auf der Config (Transparent oder Hex-Farbe)
     */
    private function applyBackground(\GdImage $targetImage, int $width, int $height): void
    {
        $bgColor = $this->config->get('image_background_color', 'transparent');

        if (\strtolower($bgColor) === 'transparent') {
            \imagealphablending($targetImage, false);
            \imagesavealpha($targetImage, true);
            $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            \imagefilledrectangle($targetImage, 0, 0, $width, $height, $transparent);
        } else {
            // Hex Color in RGB umwandeln
            $hex = \ltrim($bgColor, '#');
            if (\strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = \hexdec(\substr($hex, 0, 2));
            $g = \hexdec(\substr($hex, 2, 2));
            $b = \hexdec(\substr($hex, 4, 2));

            $color = \imagecolorallocate($targetImage, $r, $g, $b);
            \imagefilledrectangle($targetImage, 0, 0, $width, $height, $color);
        }
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

    /**
     * Benennt alle physischen Medien-Dateien eines Comics auf dem Server um.
     */
    public function renameComicMedia(string $oldId, string $newId): void
    {
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comics';
        $folders   = ['hires', 'lowres', 'thumbnails', 'social'];

        foreach ($folders as $folder) {
            $oldFile = "$targetDir/$folder/$oldId.webp";
            $newFile = "$targetDir/$folder/$newId.webp";
            if (\file_exists($oldFile)) {
                @\rename($oldFile, $newFile);
            }
        }
    }

    /**
     * Schneidet einen exakten Bereich aus und speichert ihn, primär für Social Media.
     */
    public function generateManualCrop(
        string $sourcePath,
        string $targetPath,
        int $cropX,
        int $cropY,
        int $cropWidth,
        int $cropHeight,
        int $finalWidth = 1200,
        int $finalHeight = 630,
    ): bool {
        $info = @\getimagesize($sourcePath);
        if (! $info) {
            return false;
        }

        $srcW = $info[0];
        $srcH = $info[1];
        $type = $info[2];

        $sourceImage = $this->createImageFromFile($sourcePath, $type);
        if (! $sourceImage) {
            return false;
        }

        // SICHERHEIT: Koordinaten zwingend in die Bildgrenzen pressen (Verhindert GD Absturz!)
        $cropX      = \max(0, \min($cropX, $srcW - 1));
        $cropY      = \max(0, \min($cropY, $srcH - 1));
        $cropWidth  = \min($cropWidth, $srcW - $cropX);
        $cropHeight = \min($cropHeight, $srcH - $cropY);

        if ($cropWidth <= 0 || $cropHeight <= 0) {
            \imagedestroy($sourceImage);

            return false;
        }

        // @ unterdrückt irrelevante GD Warnings, die das JSON zerstören könnten
        $croppedImage = @\imagecrop($sourceImage, [
            'x'      => $cropX,
            'y'      => $cropY,
            'width'  => $cropWidth,
            'height' => $cropHeight,
        ]);

        if (! $croppedImage) {
            \imagedestroy($sourceImage);

            return false;
        }

        $finalImage = \imagecreatetruecolor($finalWidth, $finalHeight);
        $white      = \imagecolorallocate($finalImage, 255, 255, 255);
        \imagefill($finalImage, 0, 0, $white);

        @\imagecopyresampled(
            $finalImage,
            $croppedImage,
            0,
            0,
            0,
            0,
            $finalWidth,
            $finalHeight,
            $cropWidth,
            $cropHeight,
        );

        $ext     = \strtolower(\pathinfo($targetPath, \PATHINFO_EXTENSION));
        $success = false;

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $success = \imagejpeg($finalImage, $targetPath, 90);
        } else {
            $quality = $this->config->get('webp_lossless', false) ? 100 : (int) $this->config->get('webp_quality_thumb', 80);
            $success = \imagewebp($finalImage, $targetPath, $quality);
        }

        \imagedestroy($sourceImage);
        \imagedestroy($croppedImage);
        \imagedestroy($finalImage);

        return $success;
    }
}
