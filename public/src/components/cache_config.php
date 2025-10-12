<?php
/**
 * Zentrale Konfiguration für das Cache Busting.
 * Definiert die Konstante ENABLE_CACHE_BUSTING und eine Helferfunktion.
 * 
 * @file      ROOT/public/src/components/cache_config.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @version   2.0.0
 * @since     2.0.0 Umstellung auf globale Pfad-Konstanten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Setze auf true, um Cache Busting zu aktivieren (Produktivmodus).
// Setze auf false, um es zu deaktivieren (Entwicklungsmodus/Debugging).
define('ENABLE_CACHE_BUSTING', true);

/**
 * Hängt den Zeitstempel der letzten Dateiänderung als Query-String an einen Asset-Pfad an.
 * Diese Funktion nutzt die globale Konstante DIRECTORY_PUBLIC für einen robusten Pfadaufbau.
 *
 * @param string $path Der relative Pfad zur Datei vom /public-Verzeichnis aus (z.B. 'assets/img/bild.webp').
 * @return string Der Pfad mit dem angehängten Versions-Query-String, falls die Datei existiert.
 */
function versioniere_bild_asset(string $path): string
{
    // Wenn Cache Busting deaktiviert ist, den Originalpfad zurückgeben.
    if (!defined('ENABLE_CACHE_BUSTING') || ENABLE_CACHE_BUSTING === false) {
        return $path;
    }

    // Erstelle einen absoluten Server-Pfad zur Datei, basierend auf dem public-Verzeichnis.
    $absolutePath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($path, '/\\');

    // Prüfe, ob die Datei unter dem absoluten Pfad existiert.
    if (file_exists($absolutePath)) {
        $timestamp = filemtime($absolutePath);
        return $path . '?v=' . $timestamp;
    }

    // Wenn die Datei nicht gefunden wird, gib den Originalpfad zurück, um Fehler zu vermeiden.
    if ($debugMode) {
        error_log("CACHE_CONFIG WARNUNG: Datei für Versionierung nicht gefunden: " . $absolutePath);
    }
    return $path;
}
?>