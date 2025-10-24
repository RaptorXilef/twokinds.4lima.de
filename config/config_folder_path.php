<?php
/**
 * @file      ROOT/config/config_folder_path.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.2.1
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
 * @since     2.2.1 Anpassung der ICON und UI Pfade
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
// URLs
// COMIC-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_COMIC_HIRES_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'hires');
define('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'lowres');
define('DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'socialmedia');
define('DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS_URL', DIRECTORY_PUBLIC_IMG_COMIC_URL . '/' . 'thumbnails');





######################################################################
// --- LAYOUT-IMAGES Pfade ---
define('DIRECTORY_PUBLIC_IMG_LAYOUT', DIRECTORY_PUBLIC_IMAGES . DIRECTORY_SEPARATOR . 'layout');
// URLs
define('DIRECTORY_PUBLIC_IMG_LAYOUT_URL', DIRECTORY_PUBLIC_IMAGES_URL . '/' . 'layout');

// ERROR-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_LAYOUT_HIRES', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'hires');
define('DIRECTORY_PUBLIC_IMG_LAYOUT_LOWRES', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'lowres');
// URLs
define('DIRECTORY_PUBLIC_IMG_LAYOUT_HIRES_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'hires');
define('DIRECTORY_PUBLIC_IMG_LAYOUT_LOWRES_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'lowres');

// ICON-IMAGES Pfad
define('DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'ui');
define('DIRECTORY_PUBLIC_IMG_UI', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'ui');
define('DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON', DIRECTORY_PUBLIC_IMG_LAYOUT . DIRECTORY_SEPARATOR . 'navigation');
// URLs
define('DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'ui');
define('DIRECTORY_PUBLIC_IMG_UI_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'ui');
define('DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON_URL', DIRECTORY_PUBLIC_IMG_LAYOUT_URL . '/' . 'navigation');





######################################################################
// --- PAGES-IMAGES Pfade ---
define('DIRECTORY_PUBLIC_IMG_PAGES', DIRECTORY_PUBLIC_IMAGES . DIRECTORY_SEPARATOR . 'pages');
// URLs
define('DIRECTORY_PUBLIC_IMG_PAGES_URL', DIRECTORY_PUBLIC_IMAGES_URL . '/' . 'pages');


// ABOUT Pfad
define('DIRECTORY_PUBLIC_IMG_ABOUT', DIRECTORY_PUBLIC_IMG_PAGES . DIRECTORY_SEPARATOR . 'about');
// URLs
define('DIRECTORY_PUBLIC_IMG_ABOUT_URL', DIRECTORY_PUBLIC_IMG_PAGES_URL . '/' . 'about');





######################################################################
// --- ADMIN-IMAGES Pfade ---
define('DIRECTORY_PUBLIC_IMG_ADMIN', DIRECTORY_PUBLIC_IMAGES . DIRECTORY_SEPARATOR . 'admin');
// URLs
define('DIRECTORY_PUBLIC_IMG_ADMIN_URL', DIRECTORY_PUBLIC_IMAGES_URL . '/' . 'admin');

define('DIRECTORY_PUBLIC_IMG_ADMIN_UI', DIRECTORY_PUBLIC_IMG_ADMIN . DIRECTORY_SEPARATOR . 'ui');
// URLs
define('DIRECTORY_PUBLIC_IMG_ADMIN_UI_URL', DIRECTORY_PUBLIC_IMG_ADMIN_URL . '/' . 'ui');





######################################################################
// --- CHARACTER-IMAGES Pfade ---
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS', DIRECTORY_PUBLIC_IMAGES . DIRECTORY_SEPARATOR . 'characters');
// URLs
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_URL', DIRECTORY_PUBLIC_IMAGES_URL . '/' . 'characters');


// CHARAKTERE Pfad
//define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_ASSETS', DIRECTORY_PUBLIC_IMG_HEADERFOOTER . DIRECTORY_SEPARATOR . 'charaktere');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_MAIN', DIRECTORY_PUBLIC_IMG_CHARAKTERS . DIRECTORY_SEPARATOR . 'main');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES', DIRECTORY_PUBLIC_IMG_CHARAKTERS . DIRECTORY_SEPARATOR . 'profiles');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_FACES', DIRECTORY_PUBLIC_IMG_CHARAKTERS . DIRECTORY_SEPARATOR . 'faces');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_REFSHEETS', DIRECTORY_PUBLIC_IMG_CHARAKTERS . DIRECTORY_SEPARATOR . 'refsheets');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_REFSHEETS_THUMBNAILS', DIRECTORY_PUBLIC_IMG_CHARAKTERS_REFSHEETS . DIRECTORY_SEPARATOR . 'thumbnails');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_SWATCHES', DIRECTORY_PUBLIC_IMG_CHARAKTERS . DIRECTORY_SEPARATOR . 'swatches');
// URLs
// CHARAKTERE Pfad
//define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_ASSETS_URL', DIRECTORY_PUBLIC_IMG_BANNER_URL . '/' . 'charaktere');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_MAIN_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERS_URL . '/' . 'main');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERS_URL . '/' . 'profiles');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_FACES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERS_URL . '/' . 'faces');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_REFSHEETS_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERS_URL . '/' . 'refsheets');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_REFSHEETS_THUMBNAILS_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERS_REFSHEETS_URL . '/' . 'thumbnails');
define('DIRECTORY_PUBLIC_IMG_CHARAKTERS_SWATCHES_URL', DIRECTORY_PUBLIC_IMG_CHARAKTERS_URL . '/' . 'swatches');






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