<?php
/**
 * Lädt den Bild-Cache und stellt eine Funktion zur Verfügung,
 * um versionierte Bildpfade effizient abzurufen.
 * Verwendet ein Singleton-Pattern, um sicherzustellen, dass die JSON-Cache-Datei
 * pro Anfrage nur einmal gelesen und verarbeitet wird.
 */

class ImageCache
{
    private static $instance = null;
    private $cacheData = [];
    private $projectRoot = ''; // Hinzugefügt, um den Root-Pfad zu speichern

    /**
     * Der Konstruktor ist privat, um eine direkte Instanziierung zu verhindern.
     * Er lädt die Cache-Daten aus der JSON-Datei.
     */
    private function __construct()
    {
        // Der Root-Pfad wird einmalig bestimmt (zwei Ebenen über dem aktuellen Verzeichnis /src/components/)
        $this->projectRoot = dirname(__DIR__, 2);

        $cachePath = $this->projectRoot . '/src/config/comic_image_cache.json';
        if (file_exists($cachePath)) {
            $content = file_get_contents($cachePath);
            $this->cacheData = json_decode($content, true);
            // Fallback, falls die JSON-Datei korrupt oder leer ist.
            if (!is_array($this->cacheData)) {
                $this->cacheData = [];
                error_log("BILD-CACHE WARNUNG: comic_image_cache.json ist korrupt oder leer.");
            }
        } else {
            error_log("BILD-CACHE WARNUNG: comic_image_cache.json nicht gefunden. Bitte im Admin-Bereich generieren.");
        }
    }

    /**
     * Stellt die Singleton-Instanz der Klasse bereit.
     * @return ImageCache Die einzige Instanz der ImageCache-Klasse.
     */
    public static function getInstance(): ImageCache
    {
        if (self::$instance === null) {
            self::$instance = new ImageCache();
        }
        return self::$instance;
    }

    /**
     * Ruft den gecachten Pfad für ein Bild anhand seiner ID und seines Typs ab
     * und überprüft, ob die Datei tatsächlich existiert.
     *
     * @param string $id Die Comic-ID oder der Basis-Dateiname des Bildes.
     * @param string $type Der Bildtyp (z.B. 'lowres', 'hires', 'thumbnails', 'socialmedia').
     * @return string|null Den relativen Pfad mit Cache-Buster oder null, wenn der Eintrag nicht gefunden oder die Datei nicht existent ist.
     */
    public function getPath(string $id, string $type): ?string
    {
        $relativePath = $this->cacheData[$id][$type] ?? null;

        if ($relativePath === null) {
            return null; // Eintrag nicht im Cache gefunden
        }

        // Entferne den Cache-Buster-Teil für die Dateisystem-Prüfung
        $pathWithoutQuery = strtok($relativePath, '?');
        $absolutePath = $this->projectRoot . '/' . $pathWithoutQuery;

        // Prüfe, ob die im Cache referenzierte Datei auch wirklich existiert
        if (file_exists($absolutePath)) {
            return $relativePath; // Wenn ja, gib den vollen Pfad mit Cache-Buster zurück
        }

        // Wenn die Datei nicht existiert, protokolliere einen Fehler und gib null zurück
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
