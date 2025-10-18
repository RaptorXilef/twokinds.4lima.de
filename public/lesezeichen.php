<?php
/**
 * Diese Datei zeigt alle vom Benutzer gespeicherten Lesezeichen an.
 * Die Lesezeichen werden aus dem Browser-spezifischen localStorage gelesen.
 * V3: Reparierte Version, die die ursprüngliche Template-Struktur beibehält,
 * aber das neue Kachel-Design über CSS und die verbesserte Hover-Logik nutzt.
 * 
 * @file      ROOT/public/lesezeichen.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit der Path-Klasse) ===
// Die Comic-Daten werden benötigt, um die Titel der Lesezeichen korrekt anzuzeigen.
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'load_comic_data.php';

// === 3. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Deine Lesezeichen';
$siteDescription = 'Verwalte und zeige deine gespeicherten Comic-Lesezeichen an, um schnell zu deinen Lieblingsseiten zurückzukehren.';
$viewportContent = 'width=1099';
$robotsContent = 'noindex, follow';

// Automatischer Cache-Buster für comic.js
$comicJsPathOnServer = DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'comic.js';
$comicJsWebUrl = Url::getJsUrl('comic.js');
$cacheBusterJs = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="' . htmlspecialchars($comicJsWebUrl . $cacheBusterJs) . '"></script>';

// Spezifisches Stylesheet der Charakter-Seite nur hier laden
$characterPageCssPathOnServer = DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'character_page.min.css';
$characterPageCssWebUrl = Url::getCssUrl('character_page.min.css');
$cacheBusterCss = file_exists($characterPageCssPathOnServer) ? '?c=' . filemtime($characterPageCssPathOnServer) : '';
$additionalHeadContent = '<link nonce="' . htmlspecialchars($nonce) . '" rel="stylesheet" type="text/css" href="' . htmlspecialchars($characterPageCssWebUrl . $cacheBusterCss) . '">';

// === 4. HEADER EINBINDEN (Jetzt mit Path-Klasse) ===
require_once Path::getPartialTemplatePath('header.php');
?>

<!-- Stelle die Comic-Daten für comic.js zur Verfügung, damit die Hover-Texte korrekt generiert werden können -->
<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    window.comicData = <?php echo json_encode($comicData); ?>;
</script>

<div id="bookmarksPage" class="bookmarks-page">
    <div class="bookmark-example">
        <span>Klicke auf das Lesezeichen-Symbol auf jeder Comic-Seite, um sie hier hinzuzufügen.</span>
        <div class="ribbon"></div>
    </div>

    <h2 class="page-header">Deine Lesezeichen</h2>
    <noscript>Entschuldigung, diese Funktion erfordert aktiviertes JavaScript.</noscript>

    <div id="bookmarksWrapper">
        <!-- Lesezeichen werden hier von comic.js dynamisch eingefügt -->
    </div>

    <div class="bookmarks-controls">
        <hr>
        <button type="button" id="removeAll">Alle Lesezeichen entfernen</button>
        <button type="button" id="export">Exportieren</button>
        <button type="button" id="importButton">Importieren</button>
        <input type="file" id="import" accept=".json,application/json" class="hidden-file-input">
    </div>

    <!-- Diese Templates werden von comic.js verwendet, um die Lesezeichen zu erstellen -->
    <template id="noBookmarks">
        <div class="no-bookmarks">
            <p>Du hast noch keine Lesezeichen!</p>
        </div>
    </template>

    <template id="pageBookmarkWrapper">
        <div class="chapter-links tag-page-links">
            <!-- Die einzelnen Lesezeichen-Links werden hier eingefügt -->
        </div>
    </template>

    <template id="pageBookmark">
        <a href="">
            <span>
                <!-- Der Text wird hier von comic.js eingefügt -->
                <button type="button" class="delete" title="Lesezeichen entfernen">X <?php /*
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 41.336 41.336">
<path
d="M36.335 5.668h-8.167V1.5a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v4.168H5.001a2 2 0 000 4h2.001v29.168a2.5 2.5 0 002.5 2.5h22.332a2.5 2.5 0 002.5-2.5V9.668h2.001a2 2 0 000-4zM14.168 35.67a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm8 0a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm3-30.002h-9V3h9v2.668zm5 30.002a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21z"
fill="currentColor"></path>
</svg> */ ?>
                </button>
            </span>
            <img src="" alt="Comic Thumbnail">
        </a>
    </template>
</div>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>