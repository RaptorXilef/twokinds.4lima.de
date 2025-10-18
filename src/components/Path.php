<?php
/**
 * @file      ROOT/src/components/Path.php
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

    public static function getCache(string $filename): string
    {
        return DIRECTORY_PRIVATE_CACHE . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getConfig(string $filename): string
    {
        return DIRECTORY_PRIVATE_CONFIG . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getSecret(string $filename): string
    {
        return DIRECTORY_PRIVATE_SECRETS . DIRECTORY_SEPARATOR . $filename;
    }



















    // templates

    public static function getTemplate(string $filename): string
    {
        return DIRECTORY_PRIVATE_TEMPLATES . DIRECTORY_SEPARATOR . $filename;
    }

    public static function getTemplatePartial(string $filename): string
    {
        return DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . $filename;
    }


    public static function getTemplatePartialAdmin(string $filename): string
    {
        return DIRECTORY_PRIVATE_PARTIAL_TEMPLATES_ADMIN . DIRECTORY_SEPARATOR . $filename;
    }

}

/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * include_once Path::getComponent('renderer_comic_page.php');
 *
 * --------------------------------------------------------------------
 *
 * $charaktereData = file_get_contents(Path::getData('charaktere.json'));
 *
 */
?>