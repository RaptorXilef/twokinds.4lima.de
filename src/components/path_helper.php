<?php
/**
 * @file      ROOT/src/components/path_helper.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.1
 * @since     1.0.0 Initiale Erstellung
 * @since     1.1.0 Methoden für Bild-URL-Pfade hinzugefügt.
 * @since     1.1.1 getConfig hinzugefügt
 * @comment   Diese Klasse ersetzt die Notwendigkeit, für jede Datei eine eigene Konstante zu definieren.
 * Sie nutzt die Basis-Verzeichniskonstanten aus 'config_folder_path.php', um dynamisch
 * Pfade zu Dateien und URLs zu generieren.
 */

class Path
{
    // --- PRIVATE DATEIPFADE (für includes, file_get_contents etc.) ---

    /**
     * Gibt den vollständigen Pfad zu einer Datei im 'data' Verzeichnis zurück.
     * @param string $filename Der Dateiname (z.B. 'version.json').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getData(string $filename): string
    {
        return DIRECTORY_PRIVATE_DATA . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gibt den vollständigen Pfad zu einer Datei im 'cache' Verzeichnis zurück.
     * @param string $filename Der Dateiname (z.B. 'comic_image_cache.json').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getCache(string $filename): string
    {
        return DIRECTORY_PRIVATE_CACHE . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gibt den vollständigen Pfad zu einer Datei im 'config' Verzeichnis zurück.
     * @param string $filename Der Dateiname (z.B. 'generator_settings.json').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getConfig(string $filename): string
    {
        return DIRECTORY_PRIVATE_CONFIG . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gibt den vollständigen Pfad zu einer Datei im 'secrets' Verzeichnis zurück.
     * @param string $filename Der Dateiname (z.B. 'admin_users.json').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getSecret(string $filename): string
    {
        return DIRECTORY_PRIVATE_SECRETS . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gibt den vollständigen Pfad zu einer PHP-Komponente zurück.
     * @param string $filename Der Dateiname (z.B. 'comic_page_renderer.php').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getComponent(string $filename): string
    {
        // TODO: Dies sollte auf DIRECTORY_PRIVATE_COMPONENTS zeigen, sobald du die Struktur anpasst.
        return DIRECTORY_PUBLIC_COMPONENTS . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gibt den vollständigen Pfad zu einer Admin-PHP-Komponente zurück.
     * @param string $filename Der Dateiname (z.B. 'session_timeout_modal.php').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getAdminComponent(string $filename): string
    {
        // TODO: Dies sollte auf einen privaten Admin-Komponenten-Ordner zeigen.
        return DIRECTORY_PUBLIC_ADMIN_COMPONENTS . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Gibt den vollständigen Pfad zu einem Template-Partial zurück.
     * @param string $filename Der Dateiname (z.B. 'header.php').
     * @return string Der absolute Server-Pfad zur Datei.
     */
    public static function getTemplatePartial(string $filename): string
    {
        return DIRECTORY_PRIVATE_PARTIAL_TEMLATES . DIRECTORY_SEPARATOR . $filename;
    }


    // --- ÖFFENTLICHE URL-PFADE (für <link>, <script>, <img> etc.) ---

    /**
     * Gibt die vollständige URL zu einer CSS-Datei zurück.
     * @param string $filename Der Dateiname (z.B. 'main.min.css').
     * @return string Die absolute URL zur CSS-Datei.
     */
    public static function getCssUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_CSS_URL . '/' . $filename;
    }

    /**
     * Gibt die vollständige URL zu einer JavaScript-Datei zurück.
     * @param string $filename Der Dateiname (z.B. 'common.min.js').
     * @return string Die absolute URL zur JS-Datei.
     */
    public static function getJsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_JS_URL . '/' . $filename;
    }

    /**
     * Gibt die vollständige URL zu einer Admin-JavaScript-Datei zurück.
     * @param string $filename Der Dateiname (z.B. 'session_timeout.min.js').
     * @return string Die absolute URL zur Admin-JS-Datei.
     */
    public static function getAdminJsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_ADMIN_JS_URL . '/' . $filename;
    }

    // --- BILD-URLS ---

    /**
     * Gibt die URL zu einem Bild im allgemeinen 'img'-Verzeichnis zurück.
     * @param string $filename Dateiname (z.B. 'placeholder.png')
     * @return string Absolute URL zum Bild
     */
    public static function getImg(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_HEADERFOOTER_URL . '/' . $filename;
    }

    /**
     * Gibt die URL zu einem Charakter-Referenzbogen zurück.
     * @param string $filename Dateiname (z.B. 'flora_ref.webp')
     * @return string Absolute URL zum Bild
     */
    public static function getCharRefSheet(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS_URL . '/' . $filename;
    }

    /**
     * Gibt die URL zu einem Charakter-Profilbild zurück.
     * @param string $filename Dateiname (z.B. 'trace.webp')
     * @return string Absolute URL zum Bild
     */
    public static function getCharProfile(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_PROFILE_URL . '/' . $filename;
    }

    /**
     * Gibt die URL zu einem Icon zurück.
     * @param string $filename Dateiname (z.B. 'favicon.ico')
     * @return string Absolute URL zum Bild
     */
    public static function getIcon(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_ICONS_URL . '/' . $filename;
    }
}

/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * Statt:
 * include_once COMIC_PAGE_RENDERER_FILE;
 *
 * Jetzt:
 * include_once Path::getComponent('comic_page_renderer.php');
 *
 * --------------------------------------------------------------------
 *
 * Statt:
 * $charaktereData = file_get_contents(CHARAKTERE_JSON_FILE);
 *
 * Jetzt:
 * $charaktereData = file_get_contents(Path::getData('charaktere.json'));
 *
 * --------------------------------------------------------------------
 *
 * Statt (in HTML/PHP):
 * <link rel="stylesheet" href="<?php echo MAIN_MIN_CSS_FILE_URL; ?>">
 *
 * Jetzt:
 * <link rel="stylesheet" href="<?php echo Path::getCssUrl('main.min.css'); ?>">
 *
 * --------------------------------------------------------------------
 *
 * NEU für Bilder (in HTML/PHP):
 * * Für assets/img/placeholder.png:
 * <img src="<?php echo Path::getImg('placeholder.png'); ?>">
 * * Für assets/img/charaktere/ref_sheets_webp/flora_ref.webp:
 * <img src="<?php echo Path::getCharRefSheet('flora_ref.webp'); ?>">
 *
 */
?>