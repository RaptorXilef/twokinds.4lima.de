<?php
/**
 * Lädt und verarbeitet die Comic-Daten aus der comic_var.json.
 *
 * @file      ROOT/public/src/components/load_comic_data.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.0.0 Fügt Kompatibilität für Schema-Version 2 hinzu.
 * @since     2.0.1 Stellt die ksort-Sortierung wieder her, um die korrekte Reihenfolge sicherzustellen.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

$comicData = [];
$comicVarJsonPath = Path::getData('comic_var.json');

if (file_exists($comicVarJsonPath)) {
    $comicJsonContent = file_get_contents($comicVarJsonPath);
    if ($comicJsonContent !== false) {
        $decodedData = json_decode($comicJsonContent, true);

        // Überprüfen, ob das Dekodieren erfolgreich war
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($debugMode) {
                error_log("Fehler beim Dekodieren von 'comic_var.json': " . json_last_error_msg());
            }
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
    if ($debugMode) {
        error_log("Fehler: 'comic_var.json' wurde nicht gefunden unter " . $comicVarJsonPath);
    }
    $comicData = []; // Leeres Array, wenn Datei nicht existiert
}
?>

