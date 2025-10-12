<?php
/**
 * Charakter-Seiten-Renderer
 *
 * Dieses Skript wird von den einzelnen Charakter-Seiten im /charaktere/ Verzeichnis aufgerufen.
 * Es extrahiert den Namen des Charakters aus dem Dateinamen, filtert die comic_var.json
 * nach allen Auftritten dieses Charakters und rendert eine Übersichtsseite im Lesezeichen-Design.
 * 
 * @file      ROOT/public/src/components/charcter_page_renderer.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.2.0
 * @since     2.0.0 Umstellung auf GET-Parameter und ID-basiertes Filtern.
 * @since     2.1.0 Liest Charakternamen aus Dateinamen, filtert aber nach ID.
 * @since     2.2.0 Umstellung auf globale Pfad-Konstanten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG ===
// Dieser Pfad MUSS relativ bleiben, da er die Konstanten erst lädt.
require_once __DIR__ . '/../../../src/components/public_init.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit Konstanten) ===
require_once LOAD_COMIC_DATA_PATH;
require_once IMAGE_CACHE_HELPER_PATH;

// === 3. CHARAKTER-NAMEN & ID ERMITTELN ===
// Liest den Charakternamen aus dem Dateinamen der aufrufenden PHP-Datei (z.B. "Trace" aus "Trace.php")
$characterName = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Lade Charakterdaten, um die ID anhand des Namens zu finden
$characterId = null;
if (file_exists(CHARAKTERE_JSON)) {
    $charaktereJsonContent = file_get_contents(CHARAKTERE_JSON);
    $charData = json_decode($charaktereJsonContent, true);
    $allCharacters = $charData['characters'] ?? [];

    // Finde die ID, die zum Namen passt (Groß-/Kleinschreibung ignorieren)
    foreach ($allCharacters as $id => $char) {
        if (strcasecmp($char['name'], $characterName) === 0) {
            $characterId = $id;
            break;
        }
    }
}

// === 4. COMICS FÜR DEN CHARAKTER FILTERN ===
$characterComics = [];
if ($characterId !== null && !empty($comicData) && is_array($comicData)) {
    foreach ($comicData as $comicId => $details) {
        // Filtere nach der gefundenen Charakter-ID
        if (isset($details['charaktere']) && is_array($details['charaktere']) && in_array($characterId, $details['charaktere'])) {
            $characterComics[$comicId] = $details;
        }
    }
}
// Sortiere die Comics in absteigender Reihenfolge nach Datum (ID)
krsort($characterComics);
$comicCount = count($characterComics);


// === 5. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Alle Auftritte von ' . htmlspecialchars($characterName);
$siteDescription = 'Eine Übersicht aller Comic-Seiten, auf denen der Charakter ' . htmlspecialchars($characterName) . ' erscheint.';
$viewportContent = 'width=1099';
$robotsContent = 'index, follow';

// Spezifisches Stylesheet nur für diese Seite laden (Pfade jetzt über Konstanten)
$characterPageCssPath = PUBLIC_CSS_ASSETS_PATH . DIRECTORY_SEPARATOR . 'character_page.min.css';
$characterPageCssWebUrl = $baseUrl . 'src/layout/css/character_page.min.css';
$characterPageCssCacheBuster = file_exists($characterPageCssPath) ? '?c=' . filemtime($characterPageCssPath) : '';
$additionalHeadContent = '<link nonce="' . htmlspecialchars($nonce) . '" rel="stylesheet" type="text/css" href="' . htmlspecialchars($characterPageCssWebUrl . $characterPageCssCacheBuster) . '">';

// === 6. HEADER EINBINDEN (Jetzt mit Konstante) ===
require_once TEMPLATE_HEADER;
?>

<div id="characterPage" class="bookmarks-page">
    <h2 class="page-header">
        <span>Alle Auftritte von <strong><?php echo htmlspecialchars($characterName); ?></strong></span>
        <?php if ($comicCount > 0): ?>
                <span class="comic-count-badge"><?php echo $comicCount; ?></span>
        <?php endif; ?>
    </h2>

    <?php if (empty($characterComics) || $characterId === null): ?>
            <div class="no-bookmarks">
                <p>Für den Charakter "<?php echo htmlspecialchars($characterName); ?>" wurden leider keine Comic-Auftritte in
                der Datenbank gefunden.</p>
        </div>
    <?php else: ?>
            <div class="bookmarks">
                <div class="chapter-links tag-page-links">
                <?php foreach ($characterComics as $comicId => $comicDetails): ?>
                    <?php
                    $thumbnailUrl = get_cached_image_path($comicId, 'thumbnails');
                    if (empty($thumbnailUrl)) {
                        $thumbnailUrl = get_cached_image_path('placeholder', 'thumbnails');
                    }
                    $fullThumbnailUrl = str_starts_with($thumbnailUrl, 'http') ? $thumbnailUrl : $baseUrl . ltrim($thumbnailUrl, './');
                    $formattedDate = date('d.m.Y', strtotime($comicId));

                    // Die Logik zur Namenserstellung bleibt unverändert
                    $pageName = 'Seite vom ' . $formattedDate;
                    if (!empty($comicDetails['name'])) {
                        $pageName .= ': ' . $comicDetails['name'];
                    }

                    $pageLink = $baseUrl . 'comic/' . $comicId . $dateiendungPHP;
                    ?>
                    <a href="<?php echo htmlspecialchars($pageLink); ?>" title="<?php echo htmlspecialchars($pageName); ?>">
                        <span><?php echo htmlspecialchars($pageName); ?></span>
                        <img src="<?php echo htmlspecialchars($fullThumbnailUrl); ?>"
                            alt="Thumbnail für '<?php echo htmlspecialchars($pageName); ?>'" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// === 7. FOOTER EINBINDEN (Jetzt mit Konstante) ===
require_once TEMPLATE_FOOTER;
?>