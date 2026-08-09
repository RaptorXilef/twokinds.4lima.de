<?php

declare(strict_types=1);

namespace App\Infrastructure\Media;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\MediaServiceInterface;
use GdImage;
use InvalidArgumentException;
use RuntimeException;

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
        if (!\file_exists($sourcePath)) {
            return false;
        }

        $info = @\getimagesize($sourcePath);
        if (!\is_array($info)) {
            return false;
        }

        $width = $info[0];
        $height = $info[1];
        $type = $info[2];

        if ($width < 1 || $height < 1) {
            return false;
        }

        // Smart-Copy: Wenn es schon WebP ist UND (kleiner als Max-Breite ODER Hires(4000px) ist)
        // -> Kein Re-Encoding! 1:1 kopieren für 100% Original-Qualität.
        if ($type === \IMAGETYPE_WEBP && ($width <= $maxWidth || $maxWidth >= 4000)) {
            return \copy($sourcePath, $targetPath);
        }

        $ratio = $width > $maxWidth ? $maxWidth / $width : 1;
        $newWidth = \max(1, (int) \round($width * $ratio));
        $newHeight = \max(1, (int) \round($height * $ratio));

        $image = $this->createImageFromFile($sourcePath, $type);
        if ($image === false) {
            return false;
        }

        $targetImage = \imagecreatetruecolor($newWidth, $newHeight);
        if ($targetImage === false) {
            return false;
        }

        $this->applyBackground($targetImage, $newWidth, $newHeight);

        \imagecopyresampled($targetImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $isLossless = $this->config->get('webp_lossless', false) === true;
        $qRaw = $this->config->get('webp_quality', 85);
        $quality = $isLossless ? 100 : (\is_scalar($qRaw) ? (int) $qRaw : 85);

        return \imagewebp($targetImage, $targetPath, $quality);
    }

    /**
     * Erstellt ein exakt quadratisches Bild (Zentrierter Crop) für Social Media.
     */
    public function generateSquareCrop(string $sourcePath, string $targetPath, int $size): bool
    {
        if (!\file_exists($sourcePath)) {
            return false;
        }

        $info = @\getimagesize($sourcePath);
        if (!\is_array($info)) {
            return false;
        }

        $width = $info[0];
        $height = $info[1];
        $type = $info[2];

        if ($width < 1 || $height < 1) {
            return false;
        }

        $image = $this->createImageFromFile($sourcePath, $type);
        if ($image === false) {
            return false;
        }

        $safeSize = \max(1, $size);
        $targetImage = \imagecreatetruecolor($safeSize, $safeSize);
        if ($targetImage === false) {
            return false;
        }

        $this->applyBackground($targetImage, $safeSize, $safeSize);

        // Quadratischen Ausschnitt aus der Mitte berechnen
        $minSize = \min($width, $height);
        $srcX = (int) \round(($width - $minSize) / 2);
        $srcY = (int) \round(($height - $minSize) / 2);

        \imagecopyresampled($targetImage, $image, 0, 0, $srcX, $srcY, $safeSize, $safeSize, $minSize, $minSize);

        $isLossless = $this->config->get('webp_lossless', false) === true;
        $qRaw = $this->config->get('webp_quality_thumb', 80);
        $quality = $isLossless ? 100 : (\is_scalar($qRaw) ? (int) $qRaw : 80);

        return \imagewebp($targetImage, $targetPath, $quality);
    }

    /**
     * Setzt den Hintergrund basierend auf der Config (Transparent oder Hex-Farbe)
     */
    private function applyBackground(GdImage $targetImage, int $width, int $height): void
    {
        $bgColorRaw = $this->config->get('image_background_color', 'transparent');
        $bgColor = \is_string($bgColorRaw) ? $bgColorRaw : 'transparent';

        if (\strtolower($bgColor) === 'transparent') {
            \imagealphablending($targetImage, false);
            \imagesavealpha($targetImage, true);
            $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            if ($transparent !== false) {
                \imagefilledrectangle($targetImage, 0, 0, $width, $height, $transparent);
            }
        } else {
            // Hex Color in RGB umwandeln
            $hex = \ltrim($bgColor, '#');
            if (\strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = (int) \hexdec(\substr($hex, 0, 2));
            $g = (int) \hexdec(\substr($hex, 2, 2));
            $b = (int) \hexdec(\substr($hex, 4, 2));

            $color = \imagecolorallocate($targetImage, \max(0, \min(255, $r)), \max(0, \min(255, $g)), \max(0, \min(255, $b)));
            if ($color !== false) {
                \imagefilledrectangle($targetImage, 0, 0, $width, $height, $color);
            }
        }
    }

    private function createImageFromFile(string $path, int $type): GdImage|false
    {
        return match ($type) {
            \IMAGETYPE_JPEG => @\imagecreatefromjpeg($path),
            \IMAGETYPE_PNG => @\imagecreatefrompng($path),
            \IMAGETYPE_WEBP => @\imagecreatefromwebp($path),
            \IMAGETYPE_GIF => @\imagecreatefromgif($path),
            default => false,
        };
    }

    /**
     * Benennt alle physischen Medien-Dateien eines Comics auf dem Server um.
     */
    public function renameComicMedia(string $oldId, string $newId): void
    {
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/comics';

        $folders = ['hires', 'lowres', 'thumbnails', 'social'];

        foreach ($folders as $folder) {
            $oldFile = "$targetDir/$folder/$oldId.webp";
            $newFile = "$targetDir/$folder/$newId.webp";

            if (!\file_exists($oldFile)) {
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
        if (!\is_dir($dir)) {
            @\mkdir($dir, 0o755, true);
        }

        $info = @\getimagesize($sourcePath);
        if (!\is_array($info)) {
            return false;
        }

        $srcW = $info[0];
        $srcH = $info[1];
        $type = $info[2];

        $sourceImage = $this->createImageFromFile($sourcePath, $type);
        if ($sourceImage === false) {
            return false;
        }

        // SICHERHEIT: Koordinaten zwingend in die Bildgrenzen pressen (Verhindert GD Absturz!)
        $cropX = \max(0, \min($cropX, $srcW - 1));
        $cropY = \max(0, \min($cropY, $srcH - 1));
        $cropWidth = \min($cropWidth, $srcW - $cropX);
        $cropHeight = \min($cropHeight, $srcH - $cropY);

        if ($cropWidth <= 0 || $cropHeight <= 0) {
            return false;
        }

        // @ unterdrückt irrelevante GD Warnings, die das JSON zerstören könnten
        $croppedImage = @\imagecrop($sourceImage, [
            'x' => $cropX,
            'y' => $cropY,
            'width' => $cropWidth,
            'height' => $cropHeight,
        ]);

        if ($croppedImage === false) {
            return false;
        }

        $fw = \max(1, $finalWidth);
        $fh = \max(1, $finalHeight);

        $finalImage = \imagecreatetruecolor($fw, $fh);
        if ($finalImage === false) {
            return false;
        }

        $white = \imagecolorallocate($finalImage, 255, 255, 255);
        if ($white !== false) {
            \imagefill($finalImage, 0, 0, $white);
        }

        @\imagecopyresampled(
            $finalImage,
            $croppedImage,
            0,
            0,
            0,
            0,
            $fw,
            $fh,
            $cropWidth,
            $cropHeight,
        );

        $ext = \strtolower(\pathinfo($targetPath, \PATHINFO_EXTENSION));

        if ($ext === 'jpg' || $ext === 'jpeg') {
            return \imagejpeg($finalImage, $targetPath, 90);
        }

        $isLossless = $this->config->get('webp_lossless', false) === true;
        $qRaw = $this->config->get('webp_quality_thumb', 80);
        $quality = $isLossless ? 100 : (\is_scalar($qRaw) ? (int) $qRaw : 80);

        return \imagewebp($finalImage, $targetPath, $quality);
    }

    public function autoGenerateSocialMediaJpg(string $sourcePath, string $targetPath): void
    {
        $content = \file_get_contents($sourcePath);
        if ($content === false) {
            return;
        }

        $img = @\imagecreatefromstring($content);
        if ($img === false) {
            return;
        }

        $width = \imagesx($img);
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
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/comics';

        foreach (['hires', 'lowres', 'thumbnails', 'social'] as $sub) {
            $path = "$targetDir/$sub";
            if (\is_dir($path)) {
                continue;
            }

            @\mkdir($path, 0o755, true);
        }

        $baseProcessPath = '';
        $hiresPath = "$targetDir/hires/{$comicId}.webp";

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
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/characters/profiles';

        if (!\is_dir($targetDir)) {
            @\mkdir($targetDir, 0o755, true);
        }

        $processedCount = 0;

        $names = $files['name'] ?? [];
        $errors = $files['error'] ?? [];
        $tmpNames = $files['tmp_name'] ?? [];

        if (!\is_array($names) || !\is_array($errors) || !\is_array($tmpNames)) {
            return 0;
        }

        $count = \count($names);

        for ($i = 0; $i < $count; ++$i) {
            if (!isset($errors[$i])) {
                continue;
            }
            if ($errors[$i] !== \UPLOAD_ERR_OK) {
                continue;
            }
            $tmpName = \is_string($tmpNames[$i] ?? null) ? $tmpNames[$i] : '';
            $originalName = \is_scalar($names[$i] ?? null) ? (string) $names[$i] : '';
            if ($tmpName === '') {
                continue;
            }
            if ($originalName === '') {
                continue;
            }

            $slugifiedName = $this->slugify($originalName);
            $nameWithoutExt = \pathinfo($slugifiedName, \PATHINFO_FILENAME);
            $targetPath = $targetDir . '/' . $nameWithoutExt . '.webp';

            if (!$this->generateScaledImage($tmpName, $targetPath, 1000)) {
                continue;
            }

            ++$processedCount;
        }

        return $processedCount;
    }

    public function processCharacterImages(string $safeName, array $files): array
    {
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $baseTargetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/characters';

        foreach (['profiles', 'portraits', 'palettes', 'refsheets'] as $sub) {
            $dir = $baseTargetDir . '/' . $sub;
            if (\is_dir($dir)) {
                continue;
            }

            @\mkdir($dir, 0o755, true);
        }

        $result = ['profile' => null, 'main' => null, 'swatch' => null, 'refs' => [], 'warnings' => []];

        // Profilbild
        if (isset($files['profile_image']) && \is_array($files['profile_image'])) {
            $pImg = $files['profile_image'];
            $err = $pImg['error'] ?? \UPLOAD_ERR_NO_FILE;
            if ($err !== \UPLOAD_ERR_NO_FILE) {
                if ($err === \UPLOAD_ERR_OK) {
                    $tmpName = \is_string($pImg['tmp_name'] ?? null) ? $pImg['tmp_name'] : '';
                    if ($tmpName !== '') {
                        $fileName = $safeName . '-profile.webp';
                        if ($this->generateScaledImage($tmpName, $baseTargetDir . '/profiles/' . $fileName, 1000)) {
                            $result['profile'] = $fileName;
                        } else {
                            $result['warnings'][] = 'Profilbild: Konnte vom Server nicht verarbeitet werden.';
                        }
                    }
                } else {
                    $result['warnings'][] = 'Profilbild: PHP Upload-Fehler (Code: ' . (int) $err . ')';
                }
            }
        }

        // Hauptbild (Portrait)
        if (isset($files['main_pic']) && \is_array($files['main_pic'])) {
            $mImg = $files['main_pic'];
            if (($mImg['error'] ?? \UPLOAD_ERR_NO_FILE) === \UPLOAD_ERR_OK) {
                $tmpName = \is_string($mImg['tmp_name'] ?? null) ? $mImg['tmp_name'] : '';
                if ($tmpName !== '') {
                    $fileName = $safeName . '-portrait.webp';
                    if ($this->generateScaledImage($tmpName, $baseTargetDir . '/portraits/' . $fileName, 2000)) {
                        $result['main'] = $fileName;
                    } else {
                        $result['warnings'][] = 'Hauptbild: Fehler bei Verarbeitung.';
                    }
                }
            }
        }

        // Farbpalette
        if (isset($files['swatch_pic']) && \is_array($files['swatch_pic'])) {
            $sImg = $files['swatch_pic'];
            if (($sImg['error'] ?? \UPLOAD_ERR_NO_FILE) === \UPLOAD_ERR_OK) {
                $tmpName = \is_string($sImg['tmp_name'] ?? null) ? $sImg['tmp_name'] : '';
                if ($tmpName !== '') {
                    $fileName = $safeName . '-palette.webp';
                    if ($this->generateScaledImage($tmpName, $baseTargetDir . '/palettes/' . $fileName, 1500)) {
                        $result['swatch'] = $fileName;
                    }
                }
            }
        }

        // Reference Sheets
        if (isset($files['ref_sheets']) && \is_array($files['ref_sheets'])) {
            $refFiles = $files['ref_sheets'];
            $names = $refFiles['name'] ?? [];
            $errors = $refFiles['error'] ?? [];
            $tmpNames = $refFiles['tmp_name'] ?? [];

            if (\is_array($names) && \is_array($errors) && \is_array($tmpNames)) {
                $count = \count($names);
                for ($i = 0; $i < $count; ++$i) {
                    if (!isset($errors[$i])) {
                        continue;
                    }
                    if ($errors[$i] !== \UPLOAD_ERR_OK) {
                        continue;
                    }
                    $tmpName = \is_string($tmpNames[$i] ?? null) ? $tmpNames[$i] : '';
                    if ($tmpName === '') {
                        continue;
                    }

                    $fileName = $safeName . '-ref-' . \uniqid() . '.webp';
                    if (!$this->generateScaledImage($tmpName, $baseTargetDir . '/refsheets/' . $fileName, 3000)) {
                        continue;
                    }

                    $result['refs'][] = $fileName;
                }
            }
        }

        return $result;
    }

    public function processAvatarUpload(string $userId, ?string $oldAvatarUrl, array $file): string
    {
        $tmpFile = \is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '';
        if ($tmpFile === '') {
            throw new InvalidArgumentException('Keine gültige Datei hochgeladen.');
        }

        $info = @\getimagesize($tmpFile);

        if (!\is_array($info)) {
            throw new InvalidArgumentException('Die hochgeladene Datei ist kein gültiges Bild.');
        }

        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/avatars';

        if (!\is_dir($targetDir)) {
            @\mkdir($targetDir, 0o755, true);
        }

        $typeRaw = $info[2] ?? 0;
        $type = \is_scalar($typeRaw) ? $typeRaw : 0;

        $srcImage = match ($type) {
            \IMAGETYPE_JPEG => @\imagecreatefromjpeg($tmpFile),
            \IMAGETYPE_PNG => @\imagecreatefrompng($tmpFile),
            \IMAGETYPE_WEBP => @\imagecreatefromwebp($tmpFile),
            default => false,
        };

        if ($srcImage === false) {
            throw new InvalidArgumentException('Nicht unterstütztes Bildformat.');
        }

        $finalSize = 400;
        $targetImage = \imagecreatetruecolor($finalSize, $finalSize);
        if ($targetImage === false) {
            throw new RuntimeException('Fehler beim Konvertieren und Speichern des Bildes.');
        }

        \imagealphablending($targetImage, false);
        \imagesavealpha($targetImage, true);
        $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        if ($transparent !== false) {
            \imagefilledrectangle($targetImage, 0, 0, $finalSize, $finalSize, $transparent);
        }

        $srcWRaw = $info[0] ?? 0;
        $srcHRaw = $info[1] ?? 0;
        $srcW = \is_scalar($srcWRaw) ? $srcWRaw : 0;
        $srcH = \is_scalar($srcHRaw) ? $srcHRaw : 0;

        \imagecopyresampled($targetImage, $srcImage, 0, 0, 0, 0, $finalSize, $finalSize, $srcW, $srcH);

        if ($oldAvatarUrl !== null && \file_exists($targetDir . '/' . $oldAvatarUrl)) {
            @\unlink($targetDir . '/' . $oldAvatarUrl);
        }

        $newFilename = $userId . '_' . \time() . '.webp';
        $success = \imagewebp($targetImage, $targetDir . '/' . $newFilename, 75);

        if (!$success) {
            throw new RuntimeException('Fehler beim Konvertieren und Speichern des Bildes.');
        }

        return $newFilename;
    }

    public function saveReportScreenshot(array $file): ?string
    {
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/reports';

        if (!\is_dir($targetDir)) {
            @\mkdir($targetDir, 0o777, true);
        }

        $fileName = 'rep_' . \uniqid('', true) . '.webp';

        $tmpName = \is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '';
        if ($tmpName === '') {
            return null;
        }

        if ($this->generateScaledImage($tmpName, $targetDir . '/' . $fileName, 1500)) {
            return $fileName;
        }

        return null;
    }

    // Weitgehend Identisch zu Sanitizer
    private function slugify(string $filename): string
    {
        $info = \pathinfo($filename);
        $nameRaw = $info['filename'] ?? '';
        $name = \is_string($nameRaw) ? $nameRaw : '';

        $ext = isset($info['extension']) && \is_string($info['extension']) ? '.' . \strtolower($info['extension']) : '';

        $name = \mb_strtolower($name, 'UTF-8');
        $name = \str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);

        $nameRep = \preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = \is_string($nameRep) ? $nameRep : $name;

        $nameRep2 = \preg_replace('/-+/', '-', $name);
        $name = \trim(\is_string($nameRep2) ? $nameRep2 : $name, '-');

        return $name . $ext;
    }
}
