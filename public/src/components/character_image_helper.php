<?php
/**
 * Stellt eine zentrale Helferfunktion zur Verfügung, um Pfade zu Charakterbildern
 * mit Cache-Busting zu generieren.
 * 
 * @file      ROOT/public/src/components/char_image_helper.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.3.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten für Robustheit.
 * @since     1.1.1 Umstellung auf Konstanten und Suche nach mehreren Dateitypen.
 * @since     1.2.0 Vereinfachung der Pfadlogik zur Fehlerbehebung.
 * @since     1.2.1 Finale Korrektur zur Anpassung an neue Konstanten-Struktur ohne abschließenden Slash.
 * @since     1.3.0 Anpassung an die finale Benennung der Pfad-Konstanten (DIRECTORY_PUBLIC).
 */

/**
 * Holt den versionierten Pfad für ein Charakterbild.
 *
 * @param string $imageName Der Name der Bilddatei ohne Erweiterung (z.B. 'Trace2025').
 * @param string $type Der Typ des Bildes ('portrait' oder 'ref_sheet').
 * @return string Der vollständige, relative Pfad zum Bild mit Cache-Buster-Parameter.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

function get_char_image_path(string $imageName, string $type): string
{
    global $debugMode;

    $webBasePath = '';
    $extensions = ['webp', 'jpg', 'png', 'gif'];

    if ($type === 'portrait') {
        $webBasePath = 'assets/img/charaktere/characters_webp/';
    } elseif ($type === 'ref_sheet') {
        $webBasePath = 'assets/img/charaktere/ref_sheets_webp/';
    } else {
        if ($debugMode) {
            error_log("FEHLER in get_char_image_path: Unbekannter Bildtyp '{$type}'.");
        }
        return 'assets/img/placeholder.png'; // Relative path for consistency
    }

    // Durchsuche die Erweiterungen, um die korrekte Datei zu finden
    foreach ($extensions as $ext) {
        $fileName = $imageName . '.' . $ext;
        $relativeWebPath = $webBasePath . $fileName;

        // KORREKTUR: Verwendet die neue Konstante DIRECTORY_PUBLIC, um den absoluten Server-Pfad zu erstellen.
        $absoluteServerPath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeWebPath);

        if (file_exists($absoluteServerPath)) {
            // Füge Zeitstempel als Cache-Buster an und gib den relativen Web-Pfad zurück
            return $relativeWebPath . '?v=' . filemtime($absoluteServerPath);
        }
    }

    if ($debugMode) {
        error_log("WARNUNG in get_char_image_path: Kein Bild für '{$imageName}' vom Typ '{$type}' gefunden.");
    }

    // Finaler Fallback, wenn keine Datei gefunden wird
    return 'assets/img/placeholder.png';
}
?>