<?php
/**
 * @file      ROOT/src/components/Url.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.3.0
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
 * @since     2.1.0 Fehlende URL-Methoden ergänzt und Klasse neu sortiert, Klasse in Url umbenannt.
 * @since     2.3.0 Methoden auf Englisch umgestellt für internationale Konventionen.
 */

class Url
{
    // =================================================================
    // --- BASE & PAGE URLS ---
    // =================================================================

    public static function getBaseUrl(string $path = ''): string
    {
        return DIRECTORY_PUBLIC_URL . ($path ? '/' . ltrim($path, '/') : '');
    }

    public static function getAdminPageUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_ADMIN_URL . '/' . $filename;
    }

    public static function getComicPageUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_COMIC_URL . '/' . $filename;
    }

    public static function getCharacterPageUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $filename;
    }


    // =================================================================
    // --- ASSET URLS (for CSS, JS, Images etc.) ---
    // =================================================================

    // --- CSS & JS ---
    public static function getCssUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_CSS_URL . '/' . $filename;
    }

    public static function getJsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_JS_URL . '/' . $filename;
    }

    public static function getAdminCssUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_ADMIN_CSS_URL . '/' . $filename;
    }

    public static function getAdminJsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_ADMIN_JS_URL . '/' . $filename;
    }

    // --- Images: Comic ---
    public static function getImgComicHiresUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_HIRES_URL . '/' . $filename;
    }

    public static function getImgComicLowresUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL . '/' . $filename;
    }

    public static function getImgComicSocialMediaUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA_URL . '/' . $filename;
    }

    public static function getImgComicThumbnailsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS_URL . '/' . $filename;
    }

    // --- Images: General & Icons ---
    public static function getImgIconUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_ICONS_URL . '/' . $filename;
    }

    public static function getImgBookmarkIconUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_LESEZEICHEN_ICON_URL . '/' . $filename;
    }

    public static function getImgNavigationIconUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_NAVIGATION_ICON_URL . '/' . $filename;
    }

    public static function getImgSvgUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_SVG_URL . '/' . $filename;
    }

    public static function getImgBannerUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_BANNER_URL . '/' . $filename;
    }

    public static function getImgAboutUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_ABOUT_URL . '/' . $filename;
    }

    // --- Images: Characters ---
    public static function getImgCharacterAssetsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_ASSETS_URL . '/' . $filename;
    }

    public static function getImgCharacterUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_URL . '/' . $filename;
    }

    public static function getImgCharacterProfileUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_PROFILE_URL . '/' . $filename;
    }

    public static function getImgCharacterFacesUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_FACES_URL . '/' . $filename;
    }

    public static function getImgCharacterRefsheetsUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_REFSHEETS_URL . '/' . $filename;
    }

    public static function getImgCharacterSwatchesUrl(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE_SWATCHES_URL . '/' . $filename;
    }
}

/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * <link rel="stylesheet" href="<?php echo Url::getCssUrl('main.min.css'); ?>">
 *
 * --------------------------------------------------------------------
 *
 * <img src="<?php echo Url::getImgBanner('header.webp'); ?>">
 *
 * <img src="<?php echo Url::getImgCharaktereRefsheets('flora_ref.webp'); ?>">
 *
 */
?>