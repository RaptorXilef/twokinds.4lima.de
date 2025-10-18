<?php
/**
 * @file      ROOT/src/components/Path.php
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
 * @since     2.1.0 Fehlende Pfad-Methoden ergänzt und Klasse neu sortiert.
 * @since     2.3.0 Methoden auf Englisch umgestellt für internationale Konventionen.
 */

class Path
{
    // =================================================================
    // --- PRIVATE FILE PATHS (for server-side operations) ---
    // =================================================================

    // --- System, Config & Data ---
    public static function getConfigPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_CONFIG . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getSecretPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_SECRETS . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getDataPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_DATA . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getCachePath(string $filename): string
    {
        return DIRECTORY_PRIVATE_CACHE . DIRECTORY_SEPARATOR . $filename;
    }

    // --- Source Code ---
    public static function getSrcPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_SRC . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getComponentPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . $filename;
    }
    public static function getAdminComponentPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_COMPONENTS_ADMIN . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getRendererPath(string $filename): string
    {
        return DIRECTORY_PRIVATE_RENDERER . DIRECTORY_SEPARATOR . $filename;
    }

    // --- Templates ---
    public static function getTemplatePath(string $filename): string
    {
        return DIRECTORY_PRIVATE_TEMPLATES . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getPartialTemplatePath(string $filename): string
    {
        return DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getAdminPartialTemplatePath(string $filename): string
    {
        return DIRECTORY_PRIVATE_PARTIAL_TEMPLATES_ADMIN . DIRECTORY_SEPARATOR . $filename;
    }

    // =================================================================
    // --- PUBLIC FILE PATHS (rarely needed) ---
    // =================================================================

    // --- Asset Paths ---
    public static function getCssPath(string $filename): string
    {
        return DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getJsPath(string $filename): string
    {
        return DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . $filename;
    }

    // --- Image Paths ---
    public static function getImgComicHiresPath(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_COMIC_HIRES . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getImgCharacterPath(string $filename): string
    {
        return DIRECTORY_PUBLIC_IMG_CHARAKTERE . DIRECTORY_SEPARATOR . $filename;
    }
}



/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * include_once Path::getComponent('renderer_comic_page.php');
 *
 * $charaktereData = file_get_contents(Path::getDataPath('charaktere.json'));
 *
 * $iconPath = Path::getImgIcons('favicon.ico');
 *
 */
?>