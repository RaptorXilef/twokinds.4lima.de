<?php
/**
 * Stellt eine zentrale Helferfunktion zur Verfügung, um Pfade zu Charakterbildern
 * mit Cache-Busting zu generieren.
 *
 * @file      ROOT/public/src/components/character_image_helper.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.1.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten für Robustheit.
 * @since     1.1.1 Umstellung auf Konstanten und Suche nach mehreren Dateitypen.
 * @since     1.2.0 Vereinfachung der Pfadlogik zur Fehlerbehebung.
 * @since     1.2.1 Finale Korrektur zur Anpassung an neue Konstanten-Struktur ohne abschließenden Slash.
 * @since     1.3.0 Anpassung an die finale Benennung der Pfad-Konstanten (DIRECTORY_PUBLIC).
 * @since     2.0.0 Komplette Überarbeitung für die neue Konfigurationsstruktur mit `Path`-Klasse.
 * @since     2.1.0 Entfernung der letzten hartcodierten Pfade durch Nutzung der URL-Konstanten.
 */

/**
 * Holt die versionierte, absolute URL für ein Charakterbild.
 *
 * @param string $imageName Der Name der Bilddatei ohne Erweiterung (z.B. 'Trace2025').
 * @param string $type Der Typ des Bildes ('portrait' oder 'ref_sheet').
 * @return string Die vollständige, absolute URL zum Bild mit Cache-Buster-Parameter.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

if (!function_exists('get_char_image_path')) {
    function get_char_image_path(string $imageName, string $type): string
    {
        global $debugMode;

        $serverBasePath = '';
        $urlBasePath = ''; // URL-Pfade verwenden immer '/'
        $extensions = ['webp', 'jpg', 'png', 'gif'];

        if ($type === 'portrait') {
            $serverBasePath = DIRECTORY_PUBLIC_IMG_CHARAKTERE;
            $urlBasePath = DIRECTORY_PUBLIC_IMG_CHARAKTERE_URL;
        } elseif ($type === 'ref_sheet') {
            $serverBasePath = DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS;
            $urlBasePath = DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS_URL;
        } else {
            if ($debugMode) {
                error_log("FEHLER in get_char_image_path: Unbekannter Bildtyp '{$type}'.");
            }
            return Path::getImg('placeholder.png');
        }

        // Durchsuche die Erweiterungen, um die korrekte Datei zu finden.
        foreach ($extensions as $ext) {
            $fileName = $imageName . '.' . $ext;
            $absoluteServerPath = $serverBasePath . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($absoluteServerPath)) {
                // Erstelle die vollständige URL und hänge den Cache-Buster an.
                return $urlBasePath . '/' . $fileName . '?v=' . filemtime($absoluteServerPath);
            }
        }

        if ($debugMode) {
            error_log("WARNUNG in get_char_image_path: Kein Bild für '{$imageName}' vom Typ '{$type}' in '{$serverBasePath}' gefunden.");
        }

        // Finaler Fallback, wenn keine Datei gefunden wird.
        return Path::getImg('placeholder.png');
    }
}
?>