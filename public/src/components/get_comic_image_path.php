<?php
/**
 * Helper-Funktion zum Auffinden des korrekten Bildpfads und der Dateierweiterung für Comic-Bilder.
 * Sucht nach .webp, .jpg, .jpeg, .png und .gif in der angegebenen Reihenfolge im Basisverzeichnis.
 * 
 * @file      /src/components/get_comic_image_path.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * 
 * 
 *
 * @param string $comicId Die ID des Comics (typischerweise das Datum, z.B. '20250312').
 * @param string $baseDir Das Basisverzeichnis (z.B. './assets/comic_lowres/' oder './assets/comic_hires/').
 * @param string $suffix Ein optionaler Suffix für den Dateinamen (z.B. '_preview' für Thumbnails).
 * @return string Der vollständige relative Pfad zum Comic-Bild, falls gefunden, andernfalls ein leerer String.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

function getComicImagePath(string $comicId, string $baseDir, string $suffix = ''): string
{
    // Bevorzugte Reihenfolge der Dateierweiterungen, in der gesucht wird.
    // Falls ein Suffix vorhanden ist (z.B. '_preview'), ist es wahrscheinlicher, dass es sich um ein PNG handelt.
    // Daher wird die Reihenfolge angepasst, um .png zuerst zu prüfen, falls ein Suffix verwendet wird.
    $extensions = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
    if (empty($suffix)) { // Wenn kein Suffix, ist .jpg oft das Hauptformat
        $extensions = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
    }

    foreach ($extensions as $ext) {
        $filePath = $baseDir . htmlspecialchars($comicId) . $suffix . '.' . $ext;
        // file_exists benötigt einen absoluten Pfad. __DIR__ ist das Verzeichnis dieser Datei.
        // '/../../' geht von 'src/components/' zwei Ebenen hoch ins Hauptverzeichnis, um dann $filePath anzuhängen.
        if (file_exists(__DIR__ . '/../../' . $filePath)) {
            return $filePath;
        }
    }
    // Wenn kein passendes Bild gefunden wurde, gib einen leeren String zurück.
    return '';
}