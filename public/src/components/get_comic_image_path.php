<?php
/**
 * Helper-Funktion zum Auffinden des korrekten Bildpfads und der Dateierweiterung für Comic-Bilder.
 * Sucht nach .webp, .jpg, .jpeg, .png und .gif in der angegebenen Reihenfolge im Basisverzeichnis.
 * 
 * @file      ROOT/public/src/components/get_comic_image_path.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten für Robustheit.
 *
 * @param string $comicId Die ID des Comics (typischerweise das Datum, z.B. '20250312').
 * @param string $baseDir Der relative Web-Pfad zum Basisverzeichnis (z.B. './assets/comic_lowres/').
 * @param string $suffix Ein optionaler Suffix für den Dateinamen (z.B. '_preview').
 * @return string Der vollständige relative Web-Pfad zum Comic-Bild, falls gefunden, andernfalls ein leerer String.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

function getComicImagePath(string $comicId, string $webBaseDir, string $suffix = '', bool $debugMode): string
{
    $serverBaseDir = '';

    // Map web path to absolute server path constant
    if (str_contains($webBaseDir, 'comic_lowres')) {
        $serverBaseDir = PUBLIC_IMG_COMIC_LOWRES_PATH;
    } elseif (str_contains($webBaseDir, 'comic_hires')) {
        $serverBaseDir = PUBLIC_IMG_COMIC_HIRES_PATH;
    } elseif (str_contains($webBaseDir, 'comic_thumbnails')) {
        $serverBaseDir = PUBLIC_IMG_COMIC_THUMBNAILS_PATH;
    } elseif (str_contains($webBaseDir, 'comic_socialmedia')) {
        $serverBaseDir = PUBLIC_IMG_COMIC_SOCIALMEDIA_PATH;
    } else {
        if ($debugMode) {
            error_log("FEHLER in getComicImagePath: Unbekanntes Basisverzeichnis '{$webBaseDir}'.");
        }
        return '';
    }


    $extensions = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
    if (empty($suffix)) {
        $extensions = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
    }

    foreach ($extensions as $ext) {
        $fileName = htmlspecialchars($comicId) . $suffix . '.' . $ext;
        $absolutePath = $serverBaseDir . $fileName;

        if (file_exists($absolutePath)) {
            // Return the web path, not the server path
            return $webBaseDir . $fileName;
        }
    }

    // Wenn kein passendes Bild gefunden wurde, gib einen leeren String zurück.
    return '';
}
?>