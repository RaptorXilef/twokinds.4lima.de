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
 * @version   2.0.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten für Robustheit.
 * @since     1.1.1 Umstellung auf Konstanten und Suche nach mehreren Dateitypen.
 * @since     1.2.0 Vereinfachung der Pfadlogik zur Fehlerbehebung.
 * @since     1.2.1 Finale Korrektur zur Anpassung an neue Konstanten-Struktur ohne abschließenden Slash.
 * @since     1.3.0 Anpassung an die finale Benennung der Pfad-Konstanten (DIRECTORY_PUBLIC).
 * @since     2.0.0 Komplette Überarbeitung für die neue Konfigurationsstruktur mit `Path`-Klasse und spezifischen Verzeichnis-Konstanten.
 */

/**
 * Holt den versionierten Pfad für ein Charakterbild.
 *
 * @param string $imageName Der Name der Bilddatei ohne Erweiterung (z.B. 'Trace2025').
 * @param string $type Der Typ des Bildes ('portrait' oder 'ref_sheet').
 * @return string Der vollständige, relative Pfad zum Bild mit Cache-Buster-Parameter, relativ zum Web-Root.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

if (!function_exists('get_char_image_path')) {
    function get_char_image_path(string $imageName, string $type): string
    {
        global $debugMode;

        $serverBasePath = '';
        $webBasePath = ''; // Web-Pfade sollten immer '/' als Trennzeichen verwenden.
        $extensions = ['webp', 'jpg', 'png', 'gif'];

        if ($type === 'portrait') {
            $serverBasePath = DIRECTORY_PUBLIC_IMG_CHARAKTERE;
            // Der relative Pfad für die URL, ausgehend vom /public Ordner.
            $webBasePath = 'assets/img/charaktere/characters_webp/';
        } elseif ($type === 'ref_sheet') {
            $serverBasePath = DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS;
            $webBasePath = 'assets/img/charaktere/ref_sheets_webp/';
        } else {
            if ($debugMode) {
                error_log("FEHLER in get_char_image_path: Unbekannter Bildtyp '{$type}'.");
            }
            return 'assets/img/placeholder.png'; // Konsistenter relativer Pfad
        }

        // Durchsuche die Erweiterungen, um die korrekte Datei zu finden.
        foreach ($extensions as $ext) {
            $fileName = $imageName . '.' . $ext;

            // Erstelle den absoluten Server-Pfad für die file_exists() Prüfung.
            $absoluteServerPath = $serverBasePath . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($absoluteServerPath)) {
                // Erstelle den relativen Web-Pfad, der zurückgegeben wird.
                $relativeWebPath = $webBasePath . $fileName;

                // Hänge den Zeitstempel als Cache-Buster an und gib den relativen Web-Pfad zurück.
                return $relativeWebPath . '?v=' . filemtime($absoluteServerPath);
            }
        }

        if ($debugMode) {
            error_log("WARNUNG in get_char_image_path: Kein Bild für '{$imageName}' vom Typ '{$type}' in '{$serverBasePath}' gefunden.");
        }

        // Finaler Fallback, wenn keine Datei gefunden wird.
        return 'assets/img/placeholder.png';
    }
}
?>