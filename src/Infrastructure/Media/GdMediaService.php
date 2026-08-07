<?php

declare(strict_types=1);

namespace App\Infrastructure\Media;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\MediaServiceInterface;

final readonly class GdMediaService implements MediaServiceInterface
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

        $ratio     = $width > $maxWidth ? $maxWidth / $width : 1;
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

        return \imagewebp($targetImage, $targetPath, $quality);
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

        return \imagewebp($targetImage, $targetPath, $quality);
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

            if (! \file_exists($oldFile)) {
                continue;
            }

            @\rename($oldFile, $newFile);
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
        // Ordnererstellung in die Infrastruktur verlagert
        $dir = \dirname($targetPath);
        if (! \is_dir($dir)) {
            @\mkdir($dir, 0o755, true);
        }

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

        return $success;
    }

    public function autoGenerateSocialMediaJpg(string $sourcePath, string $targetPath): void
    {
        $img = @\imagecreatefromstring(\file_get_contents($sourcePath));
        if (! $img) {
            return;
        }

        $width  = \imagesx($img);
        $height = \imagesy($img);

        $targetRatio = 1200 / 630;
        $sourceRatio = $width / $height;

        $cropW = $width;
        $cropH = $height;

        if ($sourceRatio > $targetRatio) {
            $cropW = (int) ($height * $targetRatio);
        } else {
            $cropH = (int) ($width / $targetRatio);
        }

        $cropX = (int) (($width - $cropW) / 2);
        $cropY = (int) (($height - $cropH) / 2);

        $this->generateManualCrop(
            $sourcePath,
            $targetPath,
            $cropX,
            $cropY,
            $cropW,
            $cropH,
            1200,
            630,
        );
    }

    public function processAndStoreComicMedia(string $comicId, ?string $tmpHires, ?string $tmpLowres): void
    {
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comics';

        foreach (['hires', 'lowres', 'thumbnails', 'social'] as $sub) {
            $path = "$targetDir/$sub";
            if (\is_dir($path)) {
                continue;
            }

            @\mkdir($path, 0o755, true);
        }

        $baseProcessPath = '';
        $hiresPath       = "$targetDir/hires/{$comicId}.webp";

        if ($tmpHires !== null && $tmpHires !== '') {
            $this->generateScaledImage($tmpHires, $hiresPath, 4000);
            $baseProcessPath = $hiresPath;
        }

        if ($tmpLowres !== null && $tmpLowres !== '') {
            $lowresPath = "$targetDir/lowres/{$comicId}.webp";
            $this->generateScaledImage($tmpLowres, $lowresPath, 1500);
            $baseProcessPath = $lowresPath;
        } elseif ($tmpHires !== null && $tmpHires !== '' && \file_exists($hiresPath)) {
            $lowresPath = "$targetDir/lowres/{$comicId}.webp";
            $this->generateScaledImage($hiresPath, $lowresPath, 1080);
            $baseProcessPath = $lowresPath;
        }

        if ($baseProcessPath === '') {
            return;
        }

        $this->generateScaledImage($baseProcessPath, "$targetDir/thumbnails/{$comicId}.webp", 200);

        $socialPath = "$targetDir/social/{$comicId}.jpg";
        $this->autoGenerateSocialMediaJpg($baseProcessPath, $socialPath);
    }

    public function processMassProfileUpload(array $files): int
    {
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';
        if (! \is_dir($targetDir)) {
            @\mkdir($targetDir, 0o755, true);
        }

        $processedCount = 0;
        $count          = \count($files['name'] ?? []);

        for ($i = 0; $i < $count; ++$i) {
            if ($files['error'][$i] !== \UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName      = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];

            $slugifiedName  = $this->slugify($originalName);
            $nameWithoutExt = \pathinfo($slugifiedName, \PATHINFO_FILENAME);
            $targetPath     = $targetDir . '/' . $nameWithoutExt . '.webp';

            if (! $this->generateScaledImage($tmpName, $targetPath, 1000)) {
                continue;
            }

            ++$processedCount;
        }

        return $processedCount;
    }

    public function processCharacterImages(string $safeName, array $files): array
    {
        $baseTargetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters';

        foreach (['profiles', 'portraits', 'palettes', 'refsheets'] as $sub) {
            $dir = $baseTargetDir . '/' . $sub;
            if (\is_dir($dir)) {
                continue;
            }

            @\mkdir($dir, 0o755, true);
        }

        $result = ['profile' => null, 'main' => null, 'swatch' => null, 'refs' => [], 'warnings' => []];

        // Profilbild
        if (isset($files['profile_image']) && $files['profile_image']['error'] !== \UPLOAD_ERR_NO_FILE) {
            if ($files['profile_image']['error'] === \UPLOAD_ERR_OK) {
                $fileName = $safeName . '-profile.webp';
                if ($this->generateScaledImage($files['profile_image']['tmp_name'], $baseTargetDir . '/profiles/' . $fileName, 1000)) {
                    $result['profile'] = $fileName;
                } else {
                    $result['warnings'][] = 'Profilbild: Konnte vom Server nicht verarbeitet werden.';
                }
            } else {
                $result['warnings'][] = 'Profilbild: PHP Upload-Fehler (Code: ' . $files['profile_image']['error'] . ')';
            }
        }

        // Hauptbild (Portrait)
        if (isset($files['main_pic']) && $files['main_pic']['error'] !== \UPLOAD_ERR_NO_FILE) {
            if ($files['main_pic']['error'] === \UPLOAD_ERR_OK) {
                $fileName = $safeName . '-portrait.webp';
                if ($this->generateScaledImage($files['main_pic']['tmp_name'], $baseTargetDir . '/portraits/' . $fileName, 2000)) {
                    $result['main'] = $fileName;
                } else {
                    $result['warnings'][] = 'Hauptbild: Fehler bei Verarbeitung.';
                }
            }
        }

        // Farbpalette
        if (isset($files['swatch_pic']) && $files['swatch_pic']['error'] !== \UPLOAD_ERR_NO_FILE) {
            if ($files['swatch_pic']['error'] === \UPLOAD_ERR_OK) {
                $fileName = $safeName . '-palette.webp';
                if ($this->generateScaledImage($files['swatch_pic']['tmp_name'], $baseTargetDir . '/palettes/' . $fileName, 1500)) {
                    $result['swatch'] = $fileName;
                }
            }
        }

        // Reference Sheets
        if (isset($files['ref_sheets']) && \is_array($files['ref_sheets']['name'])) {
            $refFiles = $files['ref_sheets'];
            for ($i = 0, $c = \count($refFiles['name']); $i < $c; ++$i) {
                if ($refFiles['error'][$i] !== \UPLOAD_ERR_OK) {
                    continue;
                }

                $fileName = $safeName . '-ref-' . \uniqid() . '.webp';
                if (! $this->generateScaledImage($refFiles['tmp_name'][$i], $baseTargetDir . '/refsheets/' . $fileName, 3000)) {
                    continue;
                }

                $result['refs'][] = $fileName;
            }
        }

        return $result;
    }

    public function processAvatarUpload(string $userId, ?string $oldAvatarUrl, array $file): string
    {
        $tmpFile = $file['tmp_name'] ?? '';
        $info    = @\getimagesize($tmpFile);

        if (! $info) {
            throw new \InvalidArgumentException('Die hochgeladene Datei ist kein gültiges Bild.');
        }

        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/avatars';
        if (! \is_dir($targetDir)) {
            @\mkdir($targetDir, 0o755, true);
        }

        $srcImage = match ($info[2]) {
            \IMAGETYPE_JPEG => @\imagecreatefromjpeg($tmpFile),
            \IMAGETYPE_PNG  => @\imagecreatefrompng($tmpFile),
            \IMAGETYPE_WEBP => @\imagecreatefromwebp($tmpFile),
            default         => false,
        };

        if (! $srcImage) {
            throw new \InvalidArgumentException('Nicht unterstütztes Bildformat.');
        }

        $finalSize   = 400;
        $targetImage = \imagecreatetruecolor($finalSize, $finalSize);
        \imagealphablending($targetImage, false);
        \imagesavealpha($targetImage, true);
        $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        \imagefilledrectangle($targetImage, 0, 0, $finalSize, $finalSize, $transparent);

        \imagecopyresampled($targetImage, $srcImage, 0, 0, 0, 0, $finalSize, $finalSize, $info[0], $info[1]);

        if ($oldAvatarUrl !== null && \file_exists($targetDir . '/' . $oldAvatarUrl)) {
            @\unlink($targetDir . '/' . $oldAvatarUrl);
        }

        $newFilename = $userId . '_' . \time() . '.webp';
        $success     = \imagewebp($targetImage, $targetDir . '/' . $newFilename, 75);

        if (! $success) {
            throw new \RuntimeException('Fehler beim Konvertieren und Speichern des Bildes.');
        }

        return $newFilename;
    }

    public function saveReportScreenshot(array $file): ?string
    {
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/reports';
        if (! \is_dir($targetDir)) {
            @\mkdir($targetDir, 0o777, true);
        }

        $fileName = 'rep_' . \uniqid('', true) . '.webp';
        if ($this->generateScaledImage($file['tmp_name'], $targetDir . '/' . $fileName, 1500)) {
            return $fileName;
        }

        return null;
    }

    // Weitgehend Identisch zu Sanitizer
    private function slugify(string $filename): string
    {
        $info = \pathinfo($filename);
        $name = $info['filename'];
        $ext  = isset($info['extension']) ? '.' . \strtolower($info['extension']) : '';

        $name = \mb_strtolower($name, 'UTF-8');
        $name = \str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
        $name = \preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = \trim(\preg_replace('/-+/', '-', (string) $name), '-');

        return $name . $ext;
    }
}
