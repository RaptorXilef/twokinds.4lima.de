<?php
/**
 * Helfer-Klasse zur Verwaltung von Web-Assets, insbesondere für Cache Busting.
 *
 * @file      ROOT/src/components/AssetManager.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung als Klasse.
 */

class AssetManager
{
    /**
     * Steuert, ob Cache Busting aktiv ist.
     * true: Zeitstempel wird an URLs angehängt (für Produktion).
     * false: URLs bleiben unverändert (für Entwicklung).
     */
    private const ENABLE_CACHE_BUSTING = true;

    /**
     * Steuert, ob Warnungen für nicht gefundene Dateien geloggt werden.
     */
    private const DEBUG_MODE = false;

    /**
     * Hängt einen Zeitstempel zur Versionskontrolle an eine Asset-URL an (Cache Busting).
     *
     * Diese Methode nimmt eine URL (generiert durch die Url-Klasse), findet die
     * entsprechende Datei auf dem Server und hängt den Zeitstempel der letzten
     * Änderung an. Das zwingt den Browser, die neuste Version zu laden.
     *
     * @param string $relativeUrl Der URL-Pfad zum Asset (z.B. /assets/css/style.css).
     * @return string Die URL mit angehängtem Zeitstempel (z.B. /assets/css/style.css?v=1672531200).
     */
    public static function version(string $relativeUrl): string
    {
        if (!self::ENABLE_CACHE_BUSTING) {
            return $relativeUrl;
        }

        // Entferne einen eventuellen Query-String aus der URL, um den reinen Dateipfad zu erhalten.
        $urlPath = parse_url($relativeUrl, PHP_URL_PATH);

        // Erstelle den absoluten Server-Pfad zur Datei.
        // ltrim entfernt den führenden Slash, damit der Pfad korrekt zusammengebaut wird.
        $absolutePath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($urlPath, '/');

        if (file_exists($absolutePath)) {
            $timestamp = filemtime($absolutePath);
            return $urlPath . '?v=' . $timestamp;
        }

        // Logge eine Warnung, wenn die Datei im Debug-Modus nicht gefunden wird.
        if (self::DEBUG_MODE) {
            error_log("AssetManager WARNING: File for versioning not found: " . $absolutePath);
        }

        // Gib die Original-URL zurück, wenn die Datei nicht existiert, um Fehler zu vermeiden.
        return $relativeUrl;
    }
}

/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * Die Url-Klasse generiert die Basis-URL und die AssetManager-Klasse fügt die Version hinzu.
 *
 * 1. CSS-Datei einbinden:
 * <link rel="stylesheet" href="<?= AssetManager::version(Url::getCssUrl('main.min.css')) ?>">
 *
 * 2. JavaScript-Datei einbinden:
 * <script src="<?= AssetManager::version(Url::getJsUrl('app.js')) ?>"></script>
 *
 * 3. Bild anzeigen:
 * <img src="<?= AssetManager::version(Url::getImgCharacterUrl('flora.webp')) ?>" alt="Flora">
 *
 */
