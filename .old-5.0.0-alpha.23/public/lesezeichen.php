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
 *
 * @since     2.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 * @since     5.0.0
 *  - refactor(CSS): Separates Stylesheet entfernt (jetzt in main.css integriert).
 *  - fix(HTML): Template um Klasse 'loaded' erweitert für Sichtbarkeit im neuen Grid.
 *  - perf(HTML): Native 'loading="lazy"' Attribut zum Template hinzugefügt.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN ===
// Die Comic-Daten werden benötigt, um die Titel der Lesezeichen korrekt anzuzeigen.
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'load_comic_data.php';

// === 3. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Deine Lesezeichen';
$siteDescription = 'Verwalte und zeige deine gespeicherten Comic-Lesezeichen an, um schnell zu deinen Lieblingsseiten zurückzukehren.';
//$viewportContent = 'width=1099';
$robotsContent = 'noindex, follow';

// Automatischer Cache-Buster für comic.min.js
$comicJsFileName = 'comic.min.js';
$comicJsPathOnServer = DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . $comicJsFileName;
$comicJsWebUrl = Url::getJsUrl($comicJsFileName);
$cacheBusterJs = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="' . htmlspecialchars($comicJsWebUrl . $cacheBusterJs) . '"></script>';

// Stylesheet-Logik entfernt, da jetzt global in main.css

// === 4. HEADER EINBINDEN ===
require_once Path::getPartialTemplatePath('header.php');
?>

<!-- Stelle die Comic-Daten für comic.min.js zur Verfügung, damit die Hover-Texte korrekt generiert werden können -->
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
        <!-- Lesezeichen werden hier von comic.min.js dynamisch eingefügt -->
    </div>

    <div class="bookmarks-controls">
        <hr>
        <button type="button" id="removeAll">Alle Lesezeichen entfernen</button>
        <button type="button" id="export">Exportieren</button>
        <button type="button" id="importButton">Importieren</button>
        <input type="file" id="import" accept=".json,application/json" class="hidden-file-input">
    </div>

    <!-- Diese Templates werden von comic.min.js verwendet, um die Lesezeichen zu erstellen -->
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
        <a href="" class="loaded">
            <span>
                <!-- Der Text wird hier von comic.min.js eingefügt -->
                <button type="button" class="delete" title="Lesezeichen entfernen">X</button>
            </span>
            <img src="" alt="Comic Thumbnail" loading="lazy">
        </a>
    </template>
</div>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
