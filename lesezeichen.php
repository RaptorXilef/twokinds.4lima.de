<?php
/**
 * Diese Datei zeigt alle vom Benutzer gespeicherten Lesezeichen an.
 * Die Lesezeichen werden aus dem Browser-spezifischen localStorage gelesen.
 * NEU: Verwendet das modernisierte Design der Charakter-Seiten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
require_once __DIR__ . '/src/components/public_init.php';

// === 2. LADE-SKRIPTE & DATEN ===
// Die Comic-Daten werden benötigt, um die Titel der Lesezeichen korrekt anzuzeigen.
require_once __DIR__ . '/src/components/load_comic_data.php';

// === 3. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Deine Lesezeichen';
$siteDescription = 'Verwalte und zeige deine gespeicherten Comic-Lesezeichen an, um schnell zu deinen Lieblingsseiten zurückzukehren.';
$viewportContent = 'width=1099';
$robotsContent = 'noindex, follow';

// Automatischer Cache-Buster für comic.js
$comicJsPath = $baseUrl . 'src/layout/js/comic.js?c=' . filemtime(__DIR__ . '/src/layout/js/comic.js');
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="' . htmlspecialchars($comicJsPath) . '"></script>';

// Spezifisches Stylesheet der Charakter-Seite nur hier laden
$characterPageCssPath = __DIR__ . '/src/layout/css/character_page.min.css';
$characterPageCssWebUrl = $baseUrl . 'src/layout/css/character_page.min.css';
$characterPageCssCacheBuster = file_exists($characterPageCssPath) ? '?c=' . filemtime($characterPageCssPath) : '';
$additionalHeadContent = '<link nonce="' . htmlspecialchars($nonce) . '" rel="stylesheet" type="text/css" href="' . htmlspecialchars($characterPageCssWebUrl . $characterPageCssCacheBuster) . '">';

// === 4. HEADER EINBINDEN ===
require_once __DIR__ . '/src/layout/header.php';
?>
<div id="bookmarksPage" class="bookmarks-page">
    <div class="bookmark-example">
        <span>Klicke auf das Lesezeichen-Symbol auf jeder Comic-Seite, um sie hier hinzuzufügen.</span>
        <div class="ribbon"></div>
    </div>

    <h2 class="page-header">Deine Lesezeichen</h2>
    <noscript>Entschuldigung, diese Funktion erfordert aktiviertes JavaScript.</noscript>

    <div id="bookmarksWrapper" class="bookmarks">
        <!-- Dieser Bereich wird von comic.js dynamisch mit Lesezeichen gefüllt -->
    </div>

    <div class="bookmarks-controls">
        <hr>
        <button type="button" id="removeAll">Alle Lesezeichen entfernen</button>
        <button type="button" id="export">Exportieren</button>
        <button type="button" id="importButton">Importieren</button>
        <input type="file" id="import" accept=".json,application/json" class="hidden-file-input">
    </div>

    <template id="noBookmarks">
        <div class="no-bookmarks">Du hast noch keine Lesezeichen!</div>
    </template>

    <template id="pageBookmarkWrapper">
        <div class="chapter-links tag-page-links">
        </div>
    </template>

    <template id="pageBookmark">
        <a href="">
            <span>
                <button type="button" class="delete" title="Lesezeichen entfernen">X
                    <!--<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 41.336 41.336">
                        <path
                            d="M36.335 5.668h-8.167V1.5a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v4.168H5.001a2 2 0 000 4h2.001v29.168a2.5 2.5 0 002.5 2.5h22.332a2.5 2.5 0 002.5-2.5V9.668h2.001a2 2 0 000-4zM14.168 35.67a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm8 0a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm3-30.002h-9V3h9v2.668zm5 30.002a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21z"
                            fill="currentColor"></path>
                    </svg>-->
                </button>
            </span>
            <img src="" alt="Comic Thumbnail">
        </a>
    </template>
</div>

<?php
// === 5. FOOTER EINBINDEN ===
require_once __DIR__ . '/src/layout/footer.php';
?>