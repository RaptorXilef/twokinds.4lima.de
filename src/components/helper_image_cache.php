<?php
/**
 * Lädt den Bild-Cache und stellt eine Funktion zur Verfügung,
 * um versionierte Bildpfade effizient abzurufen.
 * Verwendet ein Singleton-Pattern, um sicherzustellen, dass die JSON-Cache-Datei
 * pro Anfrage nur einmal gelesen und verarbeitet wird.
 * 
 * @file      ROOT/src/components/helper_image_cache.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

class ImageCache
{
    private static $instance = null;
    private $cacheData = [];

    /**
     * Der Konstruktor ist privat, um eine direkte Instanziierung zu verhindern.
     * Er lädt die Cache-Daten aus der JSON-Datei mithilfe der Path-Klasse.
     */
    private function __construct()
    {
        $cacheFilePath = Path::getCachePath('comic_image_cache.json');

        if (file_exists($cacheFilePath)) {
            $content = file_get_contents($cacheFilePath);
            $this->cacheData = json_decode($content, true);
            // Fallback, falls die JSON-Datei korrupt oder leer ist.
            if (!is_array($this->cacheData)) {
                $this->cacheData = [];
                error_log("BILD-CACHE WARNUNG: 'comic_image_cache.json' ist korrupt oder leer.");
            }
        } else {
            error_log("BILD-CACHE FEHLER: 'comic_image_cache.json' nicht gefunden unter: " . $cacheFilePath);
        }
    }

    /**
     * Stellt die Singleton-Instanz der Klasse bereit.
     * @return ImageCache Die Singleton-Instanz.
     */
    public static function getInstance(): ImageCache
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Ruft den Pfad für einen bestimmten Bildtyp und eine ID aus dem Cache ab.
     * Prüft, ob die referenzierte lokale Datei existiert, bevor der Pfad zurückgegeben wird.
     * @param string $id Die Comic-ID oder der Basis-Dateiname (z.B. '20250312', 'in_translation').
     * @param string $type Der Bildtyp (z.B. 'lowres', 'hires', 'socialmedia', 'url_originalbild').
     * @return string|null Den relativen Pfad mit Cache-Buster oder null, wenn der Eintrag nicht gefunden oder die Datei nicht existent ist.
     */
    public function getPath(string $id, string $type): ?string
    {
        $pathValue = $this->cacheData[$id][$type] ?? null;

        if ($pathValue === null) {
            return null; // Eintrag nicht im Cache gefunden
        }

        // Wenn der Wert eine vollständige URL ist, gib sie direkt zurück.
        if (str_starts_with($pathValue, 'https://') || str_starts_with($pathValue, 'http://')) {
            return $pathValue;
        }

        // Für lokale Pfade: Erstelle den absoluten Server-Pfad zur Überprüfung.
        $pathWithoutQuery = strtok($pathValue, '?');
        $absolutePath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pathWithoutQuery);

        if (file_exists($absolutePath)) {
            return $pathValue; // Wenn ja, gib den vollen Pfad mit Cache-Buster zurück
        }

        error_log("BILD-CACHE FEHLER: Datei für '$id' ('$type') in JSON gefunden, aber nicht auf dem Server: " . $absolutePath);
        return null;
    }
}

/**
 * Globale Helferfunktion für einen einfachen und direkten Zugriff auf die Cache-Pfade.
 *
 * @param string $id Die Comic-ID oder der Basis-Dateiname.
 * @param string $type Der Bildtyp.
 * @return string|null Den relativen Pfad mit Cache-Buster oder null.
 */
function get_cached_image_path(string $id, string $type): ?string
{
    return ImageCache::getInstance()->getPath($id, $type);
}