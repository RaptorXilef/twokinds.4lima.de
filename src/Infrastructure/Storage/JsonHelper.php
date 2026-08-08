<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\System\JsonHelperInterface;
use JsonException;
use RuntimeException;

/**
 * Hilfsklasse für sichere und erweiterte JSON-Operationen.
 * Unterstützt nativ das JSONC-Format (JSON mit ein- und mehrzeiligen Kommentaren).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class JsonHelper implements JsonHelperInterface
{
    /**
     * Dekodiert einen JSON- oder JSONC-String in ein assoziatives PHP-Array.
     * Schützt vor Code-Injections und filtert Kommentare vor dem Parsing heraus.
     *
     * @param string $json Der rohe JSON-String.
     *
     * @return array<array-key, mixed> Assoziatives Daten-Array.
     */
    public function decode(string $json): array
    {
        if (\trim($json) === '') {
            return [];
        }

        /**
         * Bulletproof Regex für JSONC:
         * Gruppe 1: Matcht gültige JSON-Strings ("...") und bewahrt sie.
         * Gruppe 3: Matcht Block- (/*...* /) und Zeilenkommentare (//...) außerhalb von Strings und entfernt sie.
         */
        $pattern = '/("([^"\\\\]*|\\\\.)*")|(\/\*[\s\S]*?\*\/|\/\/.*)/';
        $jsonWithoutComments = \preg_replace($pattern, '$1', $json);

        try {
            /** @var array<array-key, mixed> $decoded */
            $decoded = \json_decode(
                (string) $jsonWithoutComments,
                true,
                512,
                \JSON_THROW_ON_ERROR,
            );

            return \is_array($decoded) ? $decoded : [];
        } catch (JsonException $e) {
            throw new RuntimeException('Kritischer Fehler: JSON-Datenstruktur ist korrupt (' .
                $e->getMessage() .
                '). System-Halt zum Schutz vor Datenverlust.', $e->getCode(), $e);
        }
    }

    /**
     * Liest eine JSON/JSONC-Datei vom Datenträger ein.
     *
     * @param string $path Vollständiger Dateipfad.
     *
     * @return array<array-key, mixed> Assoziatives Daten-Array.
     */
    public function read(string $path): array
    {
        if (!\file_exists($path) || \is_dir($path)) {
            return [];
        }

        $content = \file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Kritischer Fehler: Datei konnte nicht gelesen werden: {$path}");
        }

        return $this->decode($content);
    }
}
