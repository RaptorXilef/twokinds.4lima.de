<?php
/**
 * Zentrale Konfiguration für das Cache Busting.
 * Definiert die Konstante ENABLE_CACHE_BUSTING und eine Helferfunktion.
 * 
 * @file      /src/components/cache_config.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

// Setze auf true, um Cache Busting zu aktivieren (Produktivmodus).
// Setze auf false, um es zu deaktivieren (Entwicklungsmodus/Debugging).
define('ENABLE_CACHE_BUSTING', true);

/**
 * Hängt den Zeitstempel der letzten Dateiänderung als Query-String an einen Bild-Asset-Pfad an.
 * Diese Funktion ist robust und funktioniert unabhängig vom Aufrufort des Skripts.
 *
 * @param string $path Der relative Pfad zur Bilddatei vom Projekt-Root aus (z.B. 'assets/img/bild.webp').
 * @return string Der Pfad mit dem angehängten Versions-Query-String, falls die Datei existiert.
 */
function versioniere_bild_asset(string $path): string
{
    // Wenn Cache Busting deaktiviert ist, den Originalpfad zurückgeben.
    if (!defined('ENABLE_CACHE_BUSTING') || ENABLE_CACHE_BUSTING === false) {
        return $path;
    }

    // Erstelle einen absoluten Pfad zur Datei. __DIR__ ist hier '.../src/components'.
    // Wir gehen zwei Ebenen hoch zum Projekt-Root.
    $projectRoot = dirname(__DIR__, 2);
    $absolutePath = $projectRoot . '/' . ltrim($path, '/\\');

    // Prüfe, ob die Datei unter dem absoluten Pfad existiert.
    if (file_exists($absolutePath)) {
        $timestamp = filemtime($absolutePath);
        return $path . '?v=' . $timestamp;
    }

    // Wenn die Datei nicht gefunden wird, gib den Originalpfad zurück, um Fehler zu vermeiden.
    return $path;
}
?>