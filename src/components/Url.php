<?php
/**
 * @file      ROOT/src/components/hUrl.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.3.0
 * @since     1.0.0 Initiale Erstellung
 * @comment   Diese Klasse ersetzt die Notwendigkeit, für jede Datei eine eigene Konstante zu definieren.
 * Sie nutzt die Basis-Verzeichniskonstanten aus 'config_folder_path.php', um dynamisch
 * Pfade zu Dateien und URLs zu generieren.
 * @since     1.1.0 Methoden für Bild-URL-Pfade hinzugefügt.
 * @since     1.1.1 getConfig hinzugefügt.
 * @since     1.1.2 Charakterordner und Bilderordner hinzugefügt.
 * @since     1.2.0 Fehlende URL-Methoden (getComicUrl, getCharaktereUrl) hinzugefügt und Fehler in getImg korrigiert.
 * @since     1.2.1 Kleinere Fixes
 * @since     1.3.0 Neue private Pfade ergänzt, einige Pfade gelöscht und einige umbenannt
 * @since     2.0.0 helper_path.php wurde in Path.php und Url.php aufgespalten.
 */

class URL
{

    // --- ÖFFENTLICHE URL-PFADE (für <link>, <script>, <img> etc.) ---

    public static function getComic(string $filename): string
    {
        return DIRECTORY_PUBLIC_COMIC_URL . '/' . $filename;
    }

    public static function getCharaktere(string $filename): string
    {
        return DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $filename;
    }

    public static function getCss(string $filename): string
    {
        return DIRECTORY_PUBLIC_CSS_URL . '/' . $filename;
    }

    public static function getAdminCSS(string $filename): string
    {
        return DIRECTORY_PUBLIC_ADMIN_CSS_URL . '/' . $filename;
    }

    public static function getJs(string $filename): string
    {
        return DIRECTORY_PUBLIC_JS_URL . '/' . $filename;
    }

    public static function getAdminJs(string $filename): string
    {
        return DIRECTORY_PUBLIC_ADMIN_JS_URL . '/' . $filename;
    }

    // --- BILD-URLS --- // TODO: Ummer IMG anfügen oder anderen eindeutigen TAG

    public static function getImg(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_BANNER_URL . '/' . $filename;
    }

    public static function getAboutImg(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_ABOUT_URL . '/' . $filename;
    }

    public static function getChar(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . $filename;
    }

    public static function getCharProfile(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_PROFILE_URL . '/' . $filename;
    }

    public static function getCharImg(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_URL . '/' . $filename;
    }

    public static function getCharFaces(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_FACES_URL . '/' . $filename;
    }

    public static function getCharRefSheets(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS_URL . '/' . $filename;
    }

    public static function getCharSwatches(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_SWATCHES_URL . '/' . $filename;
    }

    public static function getIcon(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_ICONS_URL . '/' . $filename;
    }
    public static function getLeseIcon(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON_URL . '/' . $filename;
    }
    public static function getNaviIcon(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON_URL . '/' . $filename;
    }
    public static function getSVG(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_SVG_URL . '/' . $filename;
    }

    // Comicordner
    public static function getComicHires(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_HIRES_URL . '/' . $filename;
    }
    public static function getComicLowres(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL . '/' . $filename;
    }
    public static function getSocialMedia(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA_URL . '/' . $filename;
    }
    public static function getThumbnails(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS_URL . '/' . $filename;
    }
}
/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * <link rel="stylesheet" href="<?php echo Url::getCss('main.min.css'); ?>">
 *
 * --------------------------------------------------------------------
 *
 * Für Bilder (in HTML/PHP):
 * * Für assets/img/placeholder.png:
 * <img src="<?php echo Url::getImg('placeholder.png'); ?>">
 * * Für assets/img/charaktere/ref_sheets_webp/flora_ref.webp:
 * <img src="<?php echo Url::getCharRefSheet('flora_ref.webp'); ?>">
 *
 */
?>