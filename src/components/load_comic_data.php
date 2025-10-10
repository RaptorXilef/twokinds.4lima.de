<?php
/**
 * Lädt und verarbeitet die Comic-Daten aus der comic_var.json.
 *
 * @file      /src/components/load_comic_data.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.1
 * @since     2.0.0 Fügt Kompatibilität für Schema-Version 2 hinzu.
 * @since     2.0.1 Stellt die ksort-Sortierung wieder her, um die korrekte Reihenfolge sicherzustellen.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

$comicDataJsonPath = __DIR__ . '/../config/comic_var.json';
$comicData = [];

if (file_exists($comicDataJsonPath)) {
    $comicJsonContent = file_get_contents($comicDataJsonPath);
    if ($comicJsonContent !== false) {
        $decodedData = json_decode($comicJsonContent, true);

        // Überprüfen, ob das Dekodieren erfolgreich war
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Optional: Fehlerprotokollierung für den Admin
            // error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
            $comicData = []; // Leeres Array im Fehlerfall
        } else if (is_array($decodedData)) {
            // Prüfe auf die neue, versionierte Struktur
            if (isset($decodedData['schema_version']) && $decodedData['schema_version'] >= 2 && isset($decodedData['comics'])) {
                $comicData = $decodedData['comics'];
            } else {
                // Fallback für die alte, flache Struktur
                $comicData = $decodedData;
            }
            // Sortiere die Comic-Daten nach Datum (dem Schlüssel), um die Navigation sicherzustellen
            ksort($comicData);
        }
    }
} else {
    // Optional: Fehlerprotokollierung für den Admin
    // error_log("Fehler: comic_var.json wurde nicht gefunden unter " . $comicDataJsonPath);
    $comicData = []; // Leeres Array, wenn Datei nicht existiert
}
?>