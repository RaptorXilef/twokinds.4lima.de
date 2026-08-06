<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\ImageStorageInterface;

final readonly class LocalImageStorage implements ImageStorageInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function uploadImage(string $folder, string $id, array $file): bool
    {
        // Zielordner: z.B. /public/assets/images/comics/hires
        $publicDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/' . \trim($folder, '/');

        if (! \is_dir($publicDir)) {
            @\mkdir($publicDir, 0o755, true);
        }

        $tmpPath = $file['tmp_name'] ?? '';
        $name    = $file['name'] ?? '';

        if ($tmpPath === '' || $name === '') {
            return false;
        }

        $ext = \strtolower(\pathinfo($name, \PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'webp'; // Fallback
        }

        $targetPath = $publicDir . '/' . $id . '.' . $ext;

        return @\move_uploaded_file($tmpPath, $targetPath);
    }

    public function getImageUrl(string $folder, string $id, string $fallbackIcon): string
    {
        $baseUrl   = \rtrim($this->config->getBaseUrl(), '/');
        $folder    = \trim($folder, '/');
        $publicDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/' . $folder;

        // Wir suchen automatisch nach der korrekten Dateiendung
        foreach (['webp', 'png', 'jpg', 'jpeg', 'gif'] as $ext) {
            if (\file_exists($publicDir . '/' . $id . '.' . $ext)) {
                return $baseUrl . '/assets/images/' . $folder . '/' . $id . '.' . $ext;
            }
        }

        return $fallbackIcon;
    }

    public function deleteComicMedia(string $comicId): int
    {
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comics';
        $folders = ['hires', 'lowres', 'thumbnails', 'social'];
        $deleted = 0;

        foreach ($folders as $folder) {
            foreach (['webp', 'jpg'] as $ext) {
                $file = "$targetDir/$folder/$comicId.$ext";
                if (\file_exists($file)) {
                    @\unlink($file);
                    ++$deleted;
                }
            }
        }

        return $deleted;
    }

    public function deleteCharacterMedia(string $folder, string $filename): bool
    {
        $allowed = ['profiles', 'portraits', 'palettes', 'refsheets'];
        if (! \in_array($folder, $allowed, true)) {
            $folder = 'profiles';
        }

        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/' . $folder;
        $filePath = "$targetDir/$filename";

        if ($filename !== '' && \file_exists($filePath)) {
            return @\unlink($filePath);
        }

        return false;
    }
}
