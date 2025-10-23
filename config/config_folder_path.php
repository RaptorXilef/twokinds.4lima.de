<?php
/**
 * @file      ROOT/config/config_folder_path.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.2.0
 * @since     1.0.0 Initiale Erstellung
 * @since     1.0.2 Konstanten für wichtige Dateien hinzugefügt.
 * @since     1.0.3 Pfadkonstanten robuster gestaltet: realpath() von abhängigen Pfaden entfernt und durch direkte String-Verkettung mit DIRECTORY_SEPARATOR ersetzt, um Existenzunabhängigkeit zu gewährleisten.
 * @since     2.0.0 Konstanten für relative Pfade von URL ausgehend hinzugefügt.
 * @since     2.0.1 Fehlende URL-Konstanten für einige Verzeichnisse ergänzt.
 * @since     2.0.2 Fehlende DIRECTORY_PUBLIC_ASSETS_URL Konstante ergänzt.
 * @since     2.0.3 DIRECTORY_PUBLIC_IMG_HEADERFOOTER_URL zu DIRECTORY_PUBLIC_IMG_BANNER_URL umbenannt.
 * @since     2.0.4 Ersetzt DIRECTORY_SEPARATOR innerhalb von URL-Spezifischen Konstanten durch '/' damit die Trenner auch unter WindowsServern korrekt funktionieren.
 * @since     2.1.0 Anpassung der Pfade innerhalb der KONSTANTEN auf Grundlage der neuen Verzeichnisstrukturen.
 * @since     2.1.1 Konstanten für die Ordner in resources hinzugefügt.
 * @since     2.2.0 Anpassung der Comicbilder-Pfade an die neue Struktur.
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
define('DIRECTORY_PUBLIC_ADMIN_URL', DIRECTORY_PUBLIC_URL . '/' . 'admin');
define('DIRECTORY_PUBLIC_COMIC_URL', DIRECTORY_PUBLIC_URL . '/' . 'comic');
define('DIRECTORY_PUBLIC_CHARAKTERE_URL', DIRECTORY_PUBLIC_URL . '/' . 'charaktere');


// ###################################################################
// --- ÖFFENTLICHE ASSETS Pfade ---
// ASSETS Pfad
define('DIRECTORY_PUBLIC_ASSETS', DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'assets');
// URLs
define('DIRECTORY_PUBLIC_ASSETS_URL', DIRECTORY_PUBLIC_URL . '/' . 'assets');

// JS und CSS Pfad
define('DIRECTORY_PUBLIC_JS', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'js');
define('DIRECTORY_PUBLIC_CSS', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'css');
// URLs
define('DIRECTORY_PUBLIC_JS_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'js');
define('DIRECTORY_PUBLIC_CSS_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'css');

// ADMIN JS und CSS Pfad
define('DIRECTORY_PUBLIC_ADMIN_CSS', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin');
define('DIRECTORY_PUBLIC_ADMIN_JS', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin');
// URLs
define('DIRECTORY_PUBLIC_ADMIN_CSS_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/css/admin');
define('DIRECTORY_PUBLIC_ADMIN_JS_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/js/admin');


// ###################################################################
// --- ÖFFENTLICHE IMAGES Pfade ---

define('DIRECTORY_PUBLIC_IMAGES', DIRECTORY_PUBLIC_ASSETS . DIRECTORY_SEPARATOR . 'images');
// URLs
define('DIRECTORY_PUBLIC_IMAGES_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'images');





######################################################################
// --- COMIC-IMAGES Pfade ---
define('DIRECTORY_PUBLIC_IMG_COMIC', DIRECTORY_PUBLIC_IMAGES . DIRECTORY_SEPARATOR . 'comic');
// URLs
define('DIRECTORY_PUBLIC_IMG_COMIC_URL', DIRECTORY_PUBLIC_IMAGES_URL . '/' . 'comic');

// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_COMIC_HIRES', DIRECTORY_PUBLIC_IMG_COMIC . DIRECTORY_SEPARATOR . 'hires');
define('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES', DIRECTORY_PUBLIC_IMG_COMIC . DIRECTORY_SEPARATOR . 'lowres');
define('DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA', DIRECTORY_PUBLIC_IMG_COMIC . DIRECTORY_SEPARATOR . 'socialmedia');
define('DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS', DIRECTORY_PUBLIC_IMG_COMIC . DIRECTORY_SEPARATOR . 'thumbnails');
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



// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_COMIC_HIRES_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'hires');
define('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'lowres');
define('DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'socialmedia');
define('DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'thumbnails');
// IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_ICONS_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'icons');
define('DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'lesezeichen');
define('DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'navigation');
define('DIRECTORY_PUBLIC_IMG_SVG_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'svg');
// IMG Pfad
define('DIRECTORY_PUBLIC_IMG_BANNER_URL', DIRECTORY_PUBLIC_ASSETS_URL . '/' . 'img');
// ABOUT Pfad
define('DIRECTORY_PUBLIC_IMG_ABOUT_URL', DIRECTORY_PUBLIC_IMG_BANNER_URL . '/' . 'about');
// CHARAKTERE Pfad
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL', DIRECTORY_PUBLIC_IMG_BANNER_URL . '/' . 'charaktere');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . 'characters_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_PROFILE_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . 'charaktere_1x1_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_FACES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . 'faces');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . 'ref_sheets_webp');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERE_SWATCHES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . 'swatches');





######################################################################
// --- COMIC-IMAGES Pfade ---
define('DIRECTORY_PUBLIC_IMG_LAYOUT', DIRECTORY_PUBLIC_IMAGES . DIRECTORY_SEPARATOR . 'layout');
// URLs
define('DIRECTORY_PUBLIC_IMG_LAYOUT_URL', DIRECTORY_PUBLIC_IMAGES_URL . '/' . 'layout');

// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_LAYOUT_HIRES', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'hires');
define('DIRECTORY_PUBLIC_IMG_LAYOUT_LOWRES', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'lowres');

// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_LAYOUT_HIRES_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'hires');
define('DIRECTORY_PUBLIC_IMG_LAYOUT_LOWRES_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'lowres');





// ###################################################################
// --- PRIVATE Pfade ---
// CONFIG Pfad
define('DIRECTORY_PRIVATE_CONFIG', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'config');
define('DIRECTORY_PRIVATE_SECRETS', DIRECTORY_PRIVATE_CONFIG . DIRECTORY_SEPARATOR . 'secrets');
// DATA Pfad
define('DIRECTORY_PRIVATE_DATA', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'data');
define('DIRECTORY_PRIVATE_CACHE', DIRECTORY_PRIVATE_DATA . DIRECTORY_SEPARATOR . 'cache');
// Resources
define('DIRECTORY_PRIVATE_RESOURCES', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'resources');
define('DIRECTORY_PRIVATE_RES_JS', DIRECTORY_PRIVATE_RESOURCES . DIRECTORY_SEPARATOR . 'js');
define('DIRECTORY_PRIVATE_RES_JS_ADMIN', DIRECTORY_PRIVATE_RES_JS . DIRECTORY_SEPARATOR . 'admin');
define('DIRECTORY_PRIVATE_RES_SCSS', DIRECTORY_PRIVATE_RESOURCES . DIRECTORY_SEPARATOR . 'scss');
define('DIRECTORY_PRIVATE_RES_SCSS_ADMIN', DIRECTORY_PRIVATE_RES_SCSS . DIRECTORY_SEPARATOR . 'admin');
// SRC Pfad
define('DIRECTORY_PRIVATE_SRC', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'src');
define('DIRECTORY_PRIVATE_COMPONENTS', DIRECTORY_PRIVATE_SRC . DIRECTORY_SEPARATOR . 'components');
define('DIRECTORY_PRIVATE_COMPONENTS_ADMIN', DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'admin');
define('DIRECTORY_PRIVATE_RENDERER', DIRECTORY_PRIVATE_SRC . DIRECTORY_SEPARATOR . 'renderer');
// templates Pfad
define('DIRECTORY_PRIVATE_TEMPLATES', DIRECTORY_ROOT . DIRECTORY_SEPARATOR . 'templates');
define('DIRECTORY_PRIVATE_PARTIAL_TEMPLATES', DIRECTORY_PRIVATE_TEMPLATES . DIRECTORY_SEPARATOR . 'partials');
define('DIRECTORY_PRIVATE_PARTIAL_TEMPLATES_ADMIN', DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . 'admin');

?>