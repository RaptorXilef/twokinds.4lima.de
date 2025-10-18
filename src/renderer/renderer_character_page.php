<?php
/**
 * Charakter-Seiten-Renderer
 *
 * Dieses Skript wird von den einzelnen Charakter-Seiten im /charaktere/ Verzeichnis aufgerufen.
 * Es extrahiert den Namen des Charakters aus dem Dateinamen, filtert die comic_var.json
 * nach allen Auftritten dieses Charakters und rendert eine Übersichtsseite im Lesezeichen-Design.
 * 
 * @file      ROOT/src/renderer/charcter_page_renderer.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.0.0 Umstellung auf GET-Parameter und ID-basiertes Filtern.
 * @since     2.1.0 Liest Charakternamen aus Dateinamen, filtert aber nach ID.
 * @since     2.2.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit der Path-Klasse) ===
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'load_comic_data.php';
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'helper_image_cache.php';

// === 3. CHARAKTER-NAMEN & ID ERMITTELN ===
$characterName = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$characterId = null;

$charaktereJsonPath = Path::getDataPath('charaktere.json');
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $charData = json_decode($charaktereJsonContent, true);
    $allCharacters = $charData['characters'] ?? [];

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
        if (isset($details['charaktere']) && is_array($details['charaktere']) && in_array($characterId, $details['charaktere'])) {
            $characterComics[$comicId] = $details;
        }
    }
}
krsort($characterComics);
$comicCount = count($characterComics);

// === 5. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Alle Auftritte von ' . htmlspecialchars($characterName);
$siteDescription = 'Eine Übersicht aller Comic-Seiten, auf denen der Charakter ' . htmlspecialchars($characterName) . ' erscheint.';
$viewportContent = 'width=1099';
$robotsContent = 'index, follow';

// Spezifisches Stylesheet nur für diese Seite laden
$cssServerPath = DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'character_page.min.css';
$cssWebUrl = Url::getCssUrl(filename: 'character_page.min.css');
$cacheBuster = file_exists($cssServerPath) ? '?c=' . filemtime($cssServerPath) : '';
$additionalHeadContent = '<link nonce="' . htmlspecialchars($nonce) . '" rel="stylesheet" type="text/css" href="' . htmlspecialchars($cssWebUrl . $cacheBuster) . '">';

// === 6. HEADER EINBINDEN ===
require_once Path::getPartialTemplatePath('header.php');
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
                    $fullThumbnailUrl = str_starts_with($thumbnailUrl, 'http') ? $thumbnailUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($thumbnailUrl, '/');
                    $formattedDate = date('d.m.Y', strtotime($comicId));

                    $pageName = 'Seite vom ' . $formattedDate;
                    if (!empty($comicDetails['name'])) {
                        $pageName .= ': ' . $comicDetails['name'];
                    }

                    $pageLink = DIRECTORY_PUBLIC_COMIC_URL . '/' . $comicId . $dateiendungPHP;
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

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>