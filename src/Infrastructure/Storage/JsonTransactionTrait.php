<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

/**
 * Kapselt die komplexe Logik für sichere, atomare Lese- und Schreibvorgänge
 * auf JSON-Dateien (File Locking, Race-Condition Protection).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
trait JsonTransactionTrait
{
    /**
     * Führt eine Operation auf einer JSON-Datei mit exklusivem File-Lock (LOCK_EX) aus.
     *
     * @param string   $path      Der Pfad zur JSON Datei.
     * @param callable $operation Eine Funktion, die das Array als Referenz (&$data) modifiziert.
     *
     * @return mixed Die Rückgabe der ausgeführten Operation oder false bei Datei-Fehlern.
     */
    protected function executeJsonTransaction(string $path, callable $operation): mixed
    {
        $fp = @\fopen($path, 'c+');
        if (! $fp) {
            return false;
        }

        $returnValue = null;

        if (\flock($fp, \LOCK_EX)) {
            $stat = \fstat($fp);
            $size = $stat['size'] ?? 0;
            $raw  = $size > 0 ? \fread($fp, $size) : '';
            $data = $raw === '' ? [] : $this->jsonHelper->decode((string) $raw);

            // Führe die Business-Logik aus (Array wird per Referenz übergeben und modifiziert)
            $returnValue = $operation($data);

            // Nur speichern, wenn die Logik nicht explizit false zurückgibt
            if ($returnValue !== false) {
                \ftruncate($fp, 0);
                \fseek($fp, 0);
                $jsonStr = \json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
                if (\fwrite($fp, $jsonStr) === false) {
                    throw new \RuntimeException("Kritischer Schreibfehler in JSON Transaktion: $path");
                }
                \fflush($fp);
            }
            \flock($fp, \LOCK_UN);
        }
        \fclose($fp);

        return $returnValue;
    }
}
