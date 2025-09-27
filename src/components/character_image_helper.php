<?php
/**
 * Stellt eine zentrale Helferfunktion zur Verfügung, um Pfade zu Charakterbildern
 * mit Cache-Busting zu generieren.
 * 
 * @file      /src/components/char_image_helper.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

/**
 * Holt den versionierten Pfad für ein Charakterbild.
 *
 * @param string $imageName Der Name der Bilddatei ohne Erweiterung (z.B. 'Trace2025').
 * @param string $type Der Typ des Bildes ('portrait' oder 'ref_sheet').
 * @return string Der vollständige, relative Pfad zum Bild mit Cache-Buster-Parameter.
 */
function get_char_image_path(string $imageName, string $type): string
{
    $basePath = '';
    $extension = '.webp'; // Standard-Erweiterung

    if ($type === 'portrait') {
        $basePath = 'assets/img/charaktere/characters_webp/';
    } elseif ($type === 'ref_sheet') {
        $basePath = 'assets/img/charaktere/ref_sheets_webp/';
    } else {
        // Fallback für unbekannte Typen
        return 'assets/img/placeholder.png';
    }

    $relativePath = $basePath . $imageName . $extension;
    $absolutePath = __DIR__ . '/../../' . $relativePath; // Pfad vom /src/components/ Verzeichnis aus

    if (file_exists($absolutePath)) {
        // Hänge Zeitstempel als Cache-Buster an
        return $relativePath . '?v=' . filemtime($absolutePath);
    }

    // Fallback, wenn die Datei nicht gefunden wird
    // Sie können hier einen spezifischeren Placeholder pro Typ definieren if needed
    return 'assets/img/placeholder.png';
}
?>