<?php
/**
 * @file      ROOT/config/config_folder_path.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.2
 * @since     1.0.0 Initiale Erstellung
 * @since     1.0.2 Konstanten für wichtige Dateien hinzugefügt.
 * @since     1.0.3 Pfadkonstanten robuster gestaltet: realpath() von abhängigen Pfaden entfernt und durch direkte String-Verkettung mit DIRECTORY_SEPARATOR ersetzt, um Existenzunabhängigkeit zu gewährleisten.
 * @since     2.0.0 Konstanten für relative Pfade von URL ausgehend hinzugefügt.
 * @since     2.0.1 Fehlende URL-Konstanten für einige Verzeichnisse ergänzt.
 * @since     2.0.2 Fehlende DIRECTORY_PUBLIC_ASSETS_URL Konstante ergänzt.
 *
 */

// Die Konstante DIRECTORY_SEPARATOR wird von PHP automatisch mit dem korrekten Trennzeichen (\ oder /) befüllt.

#####################################################################
// --- WICHTIG ---
// Dieser Teil darf NICHT verändert werden, da alle anderen Pfad-Konstanten davon abhängen.
// Der Root-Pfad wird mit realpath() bestimmt, um einen gültigen, absoluten Pfad zu gewährleisten.
// --- Dynamische Basis-URL Bestimmung ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$appRootAbsPath = str_replace('\\', '/', dirname(dirname(__DIR__))); // Bestimme den absoluten Pfad zum Anwendungs-Root und normalisiere die Slashes.
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')); // Bestimme den Document-Root des Servers und normalisiere die Slashes.
$subfolderPath = '';
if (stripos($appRootAbsPath, $documentRoot) === 0) { // Prüfe (case-insensitive), ob der App-Root-Pfad mit dem Document-Root beginnt.
    $subfolderPath = substr($appRootAbsPath, strlen($documentRoot)); // Extrahiere den Teil, der nach dem Document-Root kommt (der Unterordner).
}
$normalizedSubfolder = '/' . ltrim(trim($subfolderPath, '/'), '/'); // Normalisiere den Unterordner-Pfad, sodass er immer mit einem Slash beginnt, aber nicht mit zwei. Wenn das Projekt direkt im Document-Root liegt, ist der subfolderPath leer und wird zu "/".
if (strlen($normalizedSubfolder) > 1) {
    $normalizedSubfolder .= '/'; // Füge einen abschließenden Slash hinzu, wenn der Pfad nicht nur aus "/" besteht.
}
define('BASE_URL', $protocol . $host . rtrim($normalizedSubfolder, '/')); // NICHT IM PROJEKT VERWENDEN! STATDTESSEN -> DIRECTORY_PUBLIC_URL <- IM PROJEKT VERWENDEN!


######################################################################
// AB HIER KÖNNEN DIE PFAD-KONSTANTEN BEARBEITET WERDEN
// ###################################################################
// --- ROOT Pfade ---
// ROOT Pfade
// realpath() wird hier beibehalten, um einen gültigen, absoluten Startpunkt zu gewährleisten.
define('DIRECTORY_ROOT', realpath(__DIR__ . '/..'));
// --- PUBLIC Pfade ---
define('DIRECTORY_PUBLIC', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'public');
define('DIRECTORY_PUBLIC_URL', BASE_URL);


// ###################################################################
// --- ÖFFENTLICHE Pfade ---
// USER SEITEN
define('DIRECTORY_PUBLIC_ADMIN', DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'admin');
define('DIRECTORY_PUBLIC_COMIC', DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'comic');
define('DIRECTORY_PUBLIC_CHARAKTERE', DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'charaktere');
// URLs
define('DIRECTORY_PUBLIC_ADMIN_URL', DIRECTORY_PUBLIC_URL . DIRECTORY_SEPARATOR . 'admin');
define('DIRECTORY_PUBLIC_COMIC_URL', DIRECTORY_PUBLIC_URL . DIRECTORY_SEPARATOR . 'comic');
define('DIRECTORY_PUBLIC_CHARAKTERE_URL', DIRECTORY_PUBLIC_URL . DIRECTORY_SEPARATOR . 'charaktere');

// --- ÖFFENTLICHE SRC Pfade ---
// PUBLIC SRC Pfad
define('DIRECTORY_PUBLIC_SRC', DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'src');
define('DIRECTORY_PUBLIC_LAYOUT', DIRECTORY_PUBLIC_SRC . DIRECTORY_SEPARATOR . 'layout'); // assets // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_JS', DIRECTORY_PUBLIC_LAYOUT . DIRECTORY_SEPARATOR . 'js'); // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_CSS', DIRECTORY_PUBLIC_LAYOUT . DIRECTORY_SEPARATOR . 'css'); // TODO: Pfade anpassen
// TODO: SCSS und JS-ASSETS anlegen
define('DIRECTORY_PUBLIC_COMPONENTS', DIRECTORY_PUBLIC_SRC . DIRECTORY_SEPARATOR . 'components'); // TODO: Pfade anpassen auf PRIVAT
// URLs
define('DIRECTORY_PUBLIC_SRC_URL', DIRECTORY_PUBLIC_URL . DIRECTORY_SEPARATOR . 'src');
define('DIRECTORY_PUBLIC_LAYOUT_URL', DIRECTORY_PUBLIC_SRC_URL . DIRECTORY_SEPARATOR . 'layout'); // assets // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_JS_URL', DIRECTORY_PUBLIC_LAYOUT_URL . DIRECTORY_SEPARATOR . 'js'); // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_CSS_URL', DIRECTORY_PUBLIC_LAYOUT_URL . DIRECTORY_SEPARATOR . 'css'); // TODO: Pfade anpassen


// ADMIN SRC Pfad
define('DIRECTORY_PUBLIC_ADMIN_SRC', DIRECTORY_PUBLIC_ADMIN . DIRECTORY_SEPARATOR . 'src'); // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_ADMIN_JS', DIRECTORY_PUBLIC_ADMIN_SRC . DIRECTORY_SEPARATOR . 'js'); // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_ADMIN_COMPONENTS', DIRECTORY_PUBLIC_ADMIN_SRC . DIRECTORY_SEPARATOR . 'components'); // TODO: Pfade anpassen auf PRIVAT
// URLs
define('DIRECTORY_PUBLIC_ADMIN_SRC_URL', DIRECTORY_PUBLIC_ADMIN_URL . DIRECTORY_SEPARATOR . 'src'); // TODO: Pfade anpassen
define('DIRECTORY_PUBLIC_ADMIN_JS_URL', DIRECTORY_PUBLIC_ADMIN_SRC_URL . DIRECTORY_SEPARATOR . 'js'); // TODO: Pfade anpassen




// ###################################################################
// --- ASSETS Pfade ---
// ASSETS Pfad
define('DIRECTORY_PUBLIC_ASSETS', DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'assets');
// define('DIRECTORY_PUBLIC_ASSETS_IMAGES', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'images');

// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_COMIC_HIRES', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'comic_hires');
define('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'comic_lowres');
define('DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'comic_socialmedia');
define('DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'comic_thumbnails');
// IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_ICONS', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'icons');
define('DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'lesezeichen');
define('DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'navigation');
define('DIRECTORY_PUBLIC_IMG_SVG', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'svg');
// IMG Pfad
define('DIRECTORY_PUBLIC_IMG_HEADERFOOTER', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'img');
// ABOUT Pfad
define('DIRECTORY_PUBLIC_IMG_ABOUT', DIRECTORY_PUBLIC_IMG_HEADERFOOTER . DIRECTORY_SEPARATOR . 'about');
// CHARAKTERE Pfad
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS', DIRECTORY_PUBLIC_IMG_HEADERFOOTER . DIRECTORY_SEPARATOR . 'charaktere');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS . DIRECTORY_SEPARATOR . 'characters_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_PROFILE', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS . DIRECTORY_SEPARATOR . 'charaktere_1x1_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_FACES', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS . DIRECTORY_SEPARATOR . 'faces');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS . DIRECTORY_SEPARATOR . 'ref_sheets_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_SWATCHES', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS . DIRECTORY_SEPARATOR . 'swatches');

// URLs
// ASSETS Pfad
define('DIRECTORY_PUBLIC_ASSETS_URL', DIRECTORY_PUBLIC_URL . DIRECTORY_SEPARATOR . 'assets');

// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_COMIC_HIRES_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'comic_hires');
define('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'comic_lowres');
define('DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'comic_socialmedia');
define('DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'comic_thumbnails');
// IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_ICONS_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'icons');
define('DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'lesezeichen');
define('DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'navigation');
define('DIRECTORY_PUBLIC_IMG_SVG_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'svg');
// IMG Pfad
define('DIRECTORY_PUBLIC_IMG_HEADERFOOTER_URL', DIRECTORY_PUBLIC_ASSETS_URL . DIRECTORY_SEPARATOR . 'img');
// ABOUT Pfad
define('DIRECTORY_PUBLIC_IMG_ABOUT_URL', DIRECTORY_PUBLIC_IMG_HEADERFOOTER_URL . DIRECTORY_SEPARATOR . 'about');
// CHARAKTERE Pfad
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL', DIRECTORY_PUBLIC_IMG_HEADERFOOTER_URL . DIRECTORY_SEPARATOR . 'charaktere');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . DIRECTORY_SEPARATOR . 'characters_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_PROFILE_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . DIRECTORY_SEPARATOR . 'charaktere_1x1_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_FACES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . DIRECTORY_SEPARATOR . 'faces');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . DIRECTORY_SEPARATOR . 'ref_sheets_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_SWATCHES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . DIRECTORY_SEPARATOR . 'swatches');



// ###################################################################
// --- PRIVATE Pfade ---
// CONFIG Pfad
define('DIRECTORY_PRIVATE_CONFIG', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'config');
define('DIRECTORY_PRIVATE_SECRETS', DIRECTORY_PRIVATE_CONFIG . DIRECTORY_SEPARATOR . 'secrets');
// DATA Pfad
define('DIRECTORY_PRIVATE_DATA', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'data');
define('DIRECTORY_PRIVATE_CACHE', DIRECTORY_PRIVATE_DATA . DIRECTORY_SEPARATOR . 'cache');
// SRC Pfad
define('DIRECTORY_PRIVATE_SRC', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'src');
define('DIRECTORY_PRIVATE_COMPONENTS', DIRECTORY_PRIVATE_SRC . DIRECTORY_SEPARATOR . 'components');
// templates Pfad
define('DIRECTORY_PRIVATE_TEMPLATES', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'templates');
define('DIRECTORY_PRIVATE_PARTIAL_TEMLATES', DIRECTORY_PRIVATE_TEMPLATES . DIRECTORY_SEPARATOR . 'partials');

?>