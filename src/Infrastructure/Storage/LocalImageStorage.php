<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\ImageStorageInterface;
use Throwable;

final readonly class LocalImageStorage implements ImageStorageInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function uploadImage(string $folder, string $id, array $file): bool
    {
        // Zielordner: z.B. /public/assets/images/comics/hires
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $publicDir = \rtrim($rootStr, '/\\') . '/public/assets/images/' . \trim($folder, '/');

        if (!\is_dir($publicDir)) {
            \mkdir($publicDir, 0o755, true);
        }

        $tmpPath = \is_string($file['tmp_name'] ?? null) ? $file['tmp_name'] : '';
        $name = \is_string($file['name'] ?? null) ? $file['name'] : '';

        if ($tmpPath === '' || $name === '') {
            return false;
        }

        $ext = \strtolower(\pathinfo($name, \PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'webp'; // Fallback
        }

        $targetPath = $publicDir . '/' . $id . '.' . $ext;

        try {
            return \move_uploaded_file($tmpPath, $targetPath);
        } catch (Throwable) {
            return false;
        }
    }

    public function getImageUrl(string $folder, string $id, string $fallbackIcon): string
    {
        $baseUrl = \rtrim($this->config->getBaseUrl(), '/');
        $folder = \trim($folder, '/');

        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $publicDir = \rtrim($rootStr, '/\\') . '/public/assets/images/' . $folder;

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
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/comics';

        $folders = ['hires', 'lowres', 'thumbnails', 'social'];
        $deleted = 0;

        foreach ($folders as $folder) {
            foreach (['webp', 'jpg'] as $ext) {
                $file = "$targetDir/$folder/$comicId.$ext";
                if (!\file_exists($file)) {
                    continue;
                }

                try {
                    \unlink($file);
                    ++$deleted;
                } catch (Throwable) {
                }
            }
        }

        return $deleted;
    }

    public function deleteCharacterMedia(string $folder, string $filename): bool
    {
        $allowed = ['profiles', 'portraits', 'palettes', 'refsheets'];
        if (!\in_array($folder, $allowed, true)) {
            $folder = 'profiles';
        }

        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $targetDir = \rtrim($rootStr, '/\\') . '/public/assets/images/characters/' . $folder;

        $filePath = "$targetDir/$filename";

        if ($filename !== '' && \file_exists($filePath)) {
            try {
                return \unlink($filePath);
            } catch (Throwable) {
                return false;
            }
        }

        return false;
    }

    public function listCharacterMediaFiles(string $folder): array
    {
        $allowed = ['profiles', 'portraits', 'palettes', 'refsheets'];
        if (!\in_array($folder, $allowed, true)) {
            $folder = 'profiles';
        }

        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $dir = \rtrim($rootStr, '/\\') . '/public/assets/images/characters/' . $folder;

        if (!\is_dir($dir)) {
            return [];
        }

        $scan = \scandir($dir);
        if ($scan === false) {
            return [];
        }

        $files = \array_diff($scan, ['.', '..']);
        $result = [];

        foreach ($files as $file) {
            if (!\is_file($dir . '/' . $file)) {
                continue;
            }

            $result[] = ['filename' => $file, 'url' => "/assets/images/characters/{$folder}/{$file}"];
        }

        return $result;
    }

    public function listComicMediaFiles(): array
    {
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $baseDir = \rtrim($rootStr, '/\\') . '/public/assets/images/comics';

        $thumbDir = $baseDir . '/thumbnails';

        if (!\is_dir($thumbDir)) {
            return [];
        }

        $scan = \scandir($thumbDir);
        if ($scan === false) {
            return [];
        }

        $files = \array_diff($scan, ['.', '..']);
        $result = [];

        foreach ($files as $file) {
            $id = \pathinfo($file, \PATHINFO_FILENAME);
            $result[] = [
                'id' => $id,
                'has_hires' => \file_exists("$baseDir/hires/$file"),
                'has_lowres' => \file_exists("$baseDir/lowres/$file"),
                'has_social' => \file_exists("$baseDir/social/$id.jpg"),
                'has_thumb' => \file_exists("$baseDir/thumbnails/$file"),
                'url' => "/assets/images/comics/thumbnails/{$file}",
            ];
        }

        \usort(
            $result,
            fn (array $mediaA, array $mediaB): int => \strcmp((string) $mediaB['id'], (string) $mediaA['id']),
        );

        return $result;
    }
}
