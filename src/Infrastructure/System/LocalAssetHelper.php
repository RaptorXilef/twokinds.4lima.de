<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\AssetHelperInterface;

final class LocalAssetHelper implements AssetHelperInterface
{
    // Der extrem schnelle RAM-Cache für den aktuellen Request
    private array $mtimeCache = [];

    public function __construct(private readonly ConfigInterface $config)
    {
    }

    public function url(string $assetPath): string
    {
        $assetPath = \ltrim($assetPath, '/');
        $baseUrl   = \rtrim($this->config->getBaseUrl(), '/');
        $fullUrl   = $baseUrl . '/' . $assetPath;

        // 1. RAM-Cache prüfen (Verhindert mehrfache Festplatten-Aufrufe)
        if (isset($this->mtimeCache[$assetPath])) {
            $mtime = $this->mtimeCache[$assetPath];

            return $mtime === '' ? $fullUrl : $fullUrl . '?v=' . $mtime;
        }

        // 2. Physischen Pfad ermitteln
        $publicDir    = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public';
        $physicalPath = $publicDir . '/' . $assetPath;

        // Wichtig: Stat-Cache leeren, damit Änderungen per FTP an JS/CSS sofort erkannt werden!
        \clearstatcache(true, $physicalPath);

        // 3. Datei prüfen und Zeitstempel lesen
        if (\file_exists($physicalPath)) {
            $mtime                        = (string) \filemtime($physicalPath);
            $this->mtimeCache[$assetPath] = $mtime;

            return $fullUrl . '?v=' . $mtime;
        }

        // 4. Fallback (Datei existiert nicht physisch, wir geben sie ohne Cache-Buster zurück)
        $this->mtimeCache[$assetPath] = '';

        return $fullUrl;
    }

    // NEU: Durchsucht den JS-Ordner und erstellt die dynamische Mapping-Logik
    public function getImportMap(string $baseDir = 'assets/js'): string
    {
        $publicDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public';
        $scanDir   = $publicDir . '/' . \ltrim($baseDir, '/');
        $baseUrl   = \rtrim($this->config->getBaseUrl(), '/');
        $map       = ['imports' => []];

        if (! \is_dir($scanDir)) {
            return '{"imports":{}}';
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scanDir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $physicalPath = $file->getPathname();
                \clearstatcache(true, $physicalPath);
                $mtime = (string) $file->getMTime();

                // Berechne den genauen URL-Pfad (z.B. /assets/js/frontend/modules/ArchiveManager.js)
                $relativePath = \str_replace('\\', '/', \str_replace($publicDir, '', $physicalPath));
                $relativePath = '/' . \ltrim($relativePath, '/');

                $fullUrl = $baseUrl . $relativePath;

                // Trägt ein: "Originale URL" -> "URL mit Versionsstempel"
                $map['imports'][$fullUrl] = $fullUrl . '?v=' . $mtime;
            }
        }

        // Als sauberes JSON ausgeben
        return \json_encode($map, \JSON_UNESCAPED_SLASHES);
    }
}
