<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use RuntimeException;

trait SafeJsonWriterTrait
{
    /**
     * Schreibt ein Array sicher als JSON in eine Datei.
     * Wirft eine RuntimeException, falls der Speicherplatz voll ist oder Rechte fehlen.
     *
     * @param array<array-key, mixed> $data
     */
    protected function writeJsonSafely(string $path, array $data, int $flags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE): void
    {
        // ! Sicher gehen, dass der Nutzer keine Pfade sieht, aber eine Info. Die Pfade werden protokolliert
        $json = \json_encode($data, $flags);
        if ($json === false) {
            throw new RuntimeException("JSON-Encoding für $path fehlgeschlagen: " . \json_last_error_msg());
        }

        $result = @\file_put_contents($path, $json, \LOCK_EX);

        if ($result === false) {
            throw new RuntimeException("Kritischer Schreibfehler: Dateisystem voll oder keine Rechte auf $path");
        }
    }
}
