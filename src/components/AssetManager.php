<?php
/**
 * Helfer-Klasse zur Verwaltung und Optimierung von Web-Assets.
 * Bietet Cache Busting, SCSS-Kompilierung, Asset-Kombinierung/Minifizierung und WebP-Auslieferung.
 *
 * BENÖTIGT EXTERNE BIBLIOTHEKEN (Installation via Composer empfohlen):
 * - scssphp/scssphp: Für die SCSS-Kompilierung.
 * - matthiasmullie/minify: Für CSS- und JS-Minifizierung.
 *
 * @file      ROOT/src/components/AssetManager.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.0
 * @since     1.0.0 Initiale Erstellung als Klasse.
 * @since     2.0.0 Erweiterung um SCSS-Kompilierung, Minifizierung und WebP-Auslieferung.
 */

// --- BENÖTIGTE BIBLIOTHEKEN (Beispiel für Composer Autoload) ---
require_once __DIR__ . '/../../vendor/autoload.php';
use ScssPhp\ScssPhp\Compiler;
use MatthiasMullie\Minify;

class AssetManager
{
    // =================================================================
    // --- KONFIGURATION ---
    // =================================================================

    /**
     * Steuert, ob Optimierungen aktiv sind. Im Entwicklungsmodus (false) werden
     * Änderungen sofort und ohne Caching verarbeitet.
     * Im Produktionsmodus (true) werden Assets nur bei Bedarf neu generiert.
     */
    private const PRODUCTION_MODE = false;

    /**
     * Steuert, ob Cache Busting aktiv ist (empfohlen für Produktion).
     */
    private const ENABLE_CACHE_BUSTING = true;

    /**
     * Steuert, ob die automatische WebP-Auslieferung aktiv ist.
     */
    private const ENABLE_WEBP_DELIVERY = true;

    /**
     * Steuert, ob Warnungen für nicht gefundene Dateien geloggt werden.
     */
    private const DEBUG_MODE = false;


    // =================================================================
    // --- ÖFFENTLICHE METHODEN ---
    // =================================================================

    /**
     * Verarbeitet eine SCSS-Datei: kompiliert sie zu CSS und versioniert das Ergebnis.
     *
     * @param string $scssFile Der Name der SCSS-Quelldatei (z.B. 'main.scss').
     * @param bool $isAdmin Ob es sich um ein Admin-Asset handelt.
     * @return string Die versionierte URL zur fertigen CSS-Datei.
     */
    public static function scss(string $scssFile, bool $isAdmin = false): string
    {
        $sourcePath = $isAdmin
            ? Path::getResSCSSAdminPath($scssFile)
            : Path::getgetResSCSSPath($scssFile);

        $cssFile = pathinfo($scssFile, PATHINFO_FILENAME) . '.css';
        $destinationPath = $isAdmin
            ? Path::getAdminCssPath($cssFile) // Korrekter Ziel-Pfad
            : Path::getCssPath($cssFile);   // Korrekter Ziel-Pfad

        // Prüfen, ob eine Neukompilierung nötig ist
        if (!self::PRODUCTION_MODE || !file_exists($destinationPath) || filemtime($sourcePath) > filemtime($destinationPath)) {
            self::_compileScss($sourcePath, $destinationPath);
        }

        $url = $isAdmin ? Url::getAdminCssUrl($cssFile) : Url::getCssUrl($cssFile);
        return self::version($url);
    }

    /**
     * Verarbeitet ein Bild: Liefert WebP-Version, falls möglich, und versioniert das Ergebnis.
     *
     * @param string $imageUrl Die ursprüngliche URL zum Bild.
     * @return string Die optimierte und versionierte Bild-URL.
     */
    public static function image(string $imageUrl): string
    {
        if (self::ENABLE_WEBP_DELIVERY && self::_supportsWebp()) {
            $webpUrl = pathinfo($imageUrl, PATHINFO_DIRNAME) . '/' . pathinfo($imageUrl, PATHINFO_FILENAME) . '.webp';

            // Konvertiere URL zu Server-Pfad für die Existenzprüfung
            $urlPath = parse_url($webpUrl, PHP_URL_PATH);
            $absolutePath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($urlPath, '/');

            if (file_exists($absolutePath)) {
                return self::version($webpUrl); // Wenn WebP-Version existiert, diese versionieren
            }
        }

        return self::version($imageUrl); // Ansonsten das Originalbild versionieren
    }

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

    // =================================================================
    // --- PRIVATE HELFERMETHODEN ---
    // =================================================================

    /**
     * Kompiliert eine SCSS-Datei zu CSS.
     * HINWEIS: Benötigt `scssphp/scssphp`.
     */
    private static function _compileScss(string $sourcePath, string $destinationPath): void
    {
        if (!class_exists('ScssPhp\ScssPhp\Compiler')) {
            if (self::DEBUG_MODE)
                error_log("AssetManager ERROR: SCSS-Compiler-Klasse nicht gefunden. Bitte 'scssphp/scssphp' installieren.");
            return;
        }

        try {
            $compiler = new \ScssPhp\ScssPhp\Compiler();
            $sourceCode = file_get_contents($sourcePath);
            $compiledCss = $compiler->compileString($sourceCode)->getCss();
            file_put_contents($destinationPath, $compiledCss);
        } catch (\Exception $e) {
            if (self::DEBUG_MODE)
                error_log("AssetManager SCSS Error: " . $e->getMessage());
        }
    }

    /**
     * Prüft, ob der Browser des Clients WebP-Bilder unterstützt.
     */
    private static function _supportsWebp(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }

    // TODO: Methoden für JS/CSS Combining & Minifying könnten hier hinzugefügt werden.
    // public static function css(array $files, string $outputFile) { ... }
    // public static function js(array $files, string $outputFile) { ... }
}

/*
 * --- ANWENDUNGSBEISPIELE ---
 *
 * 1. SCSS-Datei kompilieren und einbinden:
 * <link rel="stylesheet" href="<?= AssetManager::scss('main.scss') ?>">
 *
 * 2. Admin SCSS-Datei kompilieren und einbinden:
 * <link rel="stylesheet" href="<?= AssetManager::scss('dashboard.scss', true) ?>">
 *
 * 3. Bild mit automatischer WebP-Auswahl und Versionierung anzeigen:
 * <img src="<?= AssetManager::image(Url::getImgCharacterUrl('flora.png')) ?>" alt="Flora">
 *
 * 4. Einfaches Cache Busting für eine JS-Datei:
 * <script src="<?= AssetManager::version(Url::getJsUrl('app.js')) ?>"></script>
 *
 */

