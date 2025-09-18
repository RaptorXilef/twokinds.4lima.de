<?php
/**
 * Charakter-Seiten-Renderer
 *
 * Dieses Skript wird von den einzelnen Charakter-Seiten im /charaktere/ Verzeichnis aufgerufen.
 * Es extrahiert den Namen des Charakters aus dem Dateinamen, filtert die comic_var.json
 * nach allen Auftritten dieses Charakters und rendert eine Übersichtsseite im Lesezeichen-Design.
 */

// === 1. ZENTRALE INITIALISIERUNG ===
require_once __DIR__ . '/public_init.php';

// === 2. LADE-SKRIPTE & DATEN ===
require_once __DIR__ . '/load_comic_data.php';
require_once __DIR__ . '/image_cache_helper.php';

// === 3. CHARAKTER-NAMEN ERMITTELN ===
$characterName = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// === 4. COMICS FÜR DEN CHARAKTER FILTERN ===
$characterComics = [];
if (!empty($comicData) && is_array($comicData)) {
    foreach ($comicData as $comicId => $details) {
        if (isset($details['charaktere']) && is_array($details['charaktere']) && in_array($characterName, $details['charaktere'])) {
            $characterComics[$comicId] = $details;
        }
    }
}
krsort($characterComics);
// Zähle die Anzahl der gefundenen Comics
$comicCount = count($characterComics);

// === 5. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Alle Auftritte von ' . htmlspecialchars($characterName);
$siteDescription = 'Eine Übersicht aller Comic-Seiten, auf denen der Charakter ' . htmlspecialchars($characterName) . ' erscheint.';
$viewportContent = 'width=1099';
$robotsContent = 'index, follow';

// Spezifisches Stylesheet nur für diese Seite laden
$characterPageCssPath = __DIR__ . '/../layout/css/character_page.min.css';
$characterPageCssWebUrl = $baseUrl . 'src/layout/css/character_page.min.css';
$characterPageCssCacheBuster = file_exists($characterPageCssPath) ? '?c=' . filemtime($characterPageCssPath) : '';
$additionalHeadContent = '<link nonce="' . htmlspecialchars($nonce) . '" rel="stylesheet" type="text/css" href="' . htmlspecialchars($characterPageCssWebUrl . $characterPageCssCacheBuster) . '">';

// === 6. HEADER EINBINDEN ===
require_once __DIR__ . '/../layout/header.php';
?>

<div id="characterPage" class="bookmarks-page">
    <h2 class="page-header">
        <span>Alle Auftritte von <strong><?php echo htmlspecialchars($characterName); ?></strong></span>
        <?php if ($comicCount > 0): ?>
            <span class="comic-count-badge"><?php echo $comicCount; ?></span>
        <?php endif; ?>
    </h2>

    <?php if (empty($characterComics)): ?>
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

                    // --- NEUE LOGIK ZUR NAMENSERSTELLUNG ---
                    // Beginnt immer mit "Seite vom [Datum]"
                    $pageName = 'Seite vom ' . $formattedDate;
                    // Wenn ein Name vorhanden ist, wird er angehängt
                    if (!empty($comicDetails['name'])) {
                        $pageName .= ': ' . $comicDetails['name'];
                    }

                    $pageLink = $baseUrl . 'comic/' . $comicId . '.php';
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
// === 7. FOOTER EINBINDEN ===
require_once __DIR__ . '/../layout/footer.php';
?>