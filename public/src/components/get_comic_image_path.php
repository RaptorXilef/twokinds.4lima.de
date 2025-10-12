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
 * @version   4.0.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten für Robustheit.
 * @since     4.0.0 Umstellung auf die neue Konfigurationsstruktur mit `Path`-Klasse und spezifischen Verzeichnis-Konstanten.
 *
 * @param string $comicId Die ID des Comics (z.B. '20250312').
 * @param string $type Der Typ des Bildes ('lowres', 'hires', 'thumbnails', 'socialmedia').
 * @param string $suffix Ein optionaler Suffix für den Dateinamen.
 * @return string Der relative Web-Pfad zum Bild mit Cache-Buster, falls gefunden, sonst leer.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

if (!function_exists('getComicImagePath')) {
    function getComicImagePath(string $comicId, string $type, string $suffix = ''): string
    {
        global $debugMode;

        $serverBasePath = '';
        $webBasePath = ''; // Relativer Pfad vom /public-Ordner aus

        // Weise die korrekten Server- und Web-Pfade basierend auf dem Typ zu
        switch ($type) {
            case 'lowres':
                $serverBasePath = DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;
                $webBasePath = 'assets/comic_lowres/';
                break;
            case 'hires':
                $serverBasePath = DIRECTORY_PUBLIC_IMG_COMIC_HIRES;
                $webBasePath = 'assets/comic_hires/';
                break;
            case 'thumbnails':
                $serverBasePath = DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS;
                $webBasePath = 'assets/comic_thumbnails/';
                break;
            case 'socialmedia':
                $serverBasePath = DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA;
                $webBasePath = 'assets/comic_socialmedia/';
                break;
            default:
                if ($debugMode) {
                    error_log("FEHLER in getComicImagePath: Unbekannter Bildtyp '{$type}'.");
                }
                return '';
        }

        $extensions = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
        if (empty($suffix)) {
            $extensions = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
        }

        foreach ($extensions as $ext) {
            $fileName = htmlspecialchars($comicId) . $suffix . '.' . $ext;
            $absoluteServerPath = $serverBasePath . DIRECTORY_SEPARATOR . $fileName;

            if (file_exists($absoluteServerPath)) {
                // Gib den relativen Web-Pfad mit Cache-Buster zurück
                $relativeWebPath = $webBasePath . $fileName;
                return $relativeWebPath . '?v=' . filemtime($absoluteServerPath);
            }
        }

        if ($debugMode) {
            error_log("WARNUNG in getComicImagePath: Kein Bild für '{$comicId}' ('{$type}') in '{$serverBasePath}' gefunden.");
        }

        // Wenn kein passendes Bild gefunden wurde, gib einen leeren String zurück.
        return '';
    }
}
?>