<?php
/**
 * Diese Datei zeigt alle vom Benutzer gespeicherten Lesezeichen an.
 * Die Lesezeichen werden aus dem Browser-spezifischen localStorage gelesen.
 */

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
// Dies ist notwendig, um die Titel der Lesezeichen korrekt anzuzeigen.
require_once __DIR__ . '/src/components/load_comic_data.php';

// === Dynamische Basis-URL Bestimmung für die gesamte Anwendung ===
// Diese Logik wird hier dupliziert, um sicherzustellen, dass $baseUrl
// verfügbar ist, bevor $additionalScripts und $additionalHeadContent definiert werden,
// die ihrerseits im header.php verwendet werden.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
// Ermittle das Basisverzeichnis des Skripts relativ zum Document Root
$scriptDir = rtrim(dirname($scriptName), '/');

// Wenn das Skript im Root-Verzeichnis liegt, ist $scriptDir leer.
// In diesem Fall ist $baseUrl einfach das Protokoll und der Host.
// Andernfalls ist es Protokoll + Host + Skriptverzeichnis.
$baseUrl = $protocol . $host . ($scriptDir === '' ? '/' : $scriptDir . '/');


// Setze Parameter für den Header. Der Seitentitel wird im Header automatisch mit Präfix versehen.
$pageTitle = 'Deine Lesezeichen';
$pageHeader = 'Deine Lesezeichen'; // Dieser Wert wird im Hauptinhaltsbereich angezeigt.

// Füge die comic.js als zusätzliches Skript hinzu, da sie die Lesezeichen-Logik enthält.
// Der Cache-Buster (c=20250722) sollte beibehalten werden, um Browser-Caching zu umgehen.
$additionalScripts = "<script type='text/javascript' src='" . htmlspecialchars($baseUrl) . "src/layout/js/comic.js?c=20250722'></script>";

// Die allgemeine Seitenbeschreibung für SEO und Social Media.
$siteDescription = 'Verwalte und zeige deine gespeicherten Comic-Lesezeichen an.';

// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099'; // Konsistent mit Original für das Design.

// Binde den gemeinsamen Header ein.
include __DIR__ . "/src/layout/header.php";
?>

<div id="bookmarksPage" class="bookmarks-page">
    <!-- Bookmark example as seen in the original screenshot -->
    <div class="bookmark-example">
        <span>Klicke auf das Lesezeichen-Symbol auf jeder Comic-Seite, um sie hier hinzuzufügen.</span>
        <div class="ribbon"></div>
    </div>

    <h2 class="page-header"><?php echo htmlspecialchars($pageHeader); ?></h2>
    <noscript>Entschuldigung, diese Funktion erfordert aktiviertes JavaScript.</noscript>

    <div>
        <div id="bookmarksWrapper" class="bookmarks">
            <!-- Lesezeichen werden hier von JavaScript eingefügt -->
            <div class="no-bookmarks">Du hast noch keine Lesezeichen!</div>
        </div>
        <hr>
        <button type="button" id="removeAll">Alle Lesezeichen entfernen</button>
        <button type="button" id="export">Exportieren</button>
        <button type="button" id="importButton">Importieren</button>
        <input type="file" id="import" accept=".json,application/json" style="display: none;">
    </div>

    <!-- Templates for JavaScript to clone -->
    <template id="noBookmarks">
        <div class="no-bookmarks">Du hast noch keine Lesezeichen!</div>
    </template>

    <template id="pageBookmarkWrapper">
        <div class="chapter-links">
            <!-- Bookmarks are dynamically inserted here by JS -->
        </div>
    </template>

    <template id="pageBookmark">
        <a href="">
            <span>
                <!-- Der Text (Seitenzahl/Titel) wird hier von comic.js eingefügt -->
                <button type="button" class="delete" title="Lesezeichen entfernen">
                    <!-- SVG für das Mülleimersymbol -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 41.336 41.336">
                        <path
                            d="M36.335 5.668h-8.167V1.5a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v4.168H5.001a2 2 0 000 4h2.001v29.168a2.5 2.5 0 002.5 2.5h22.332a2.5 2.5 0 002.5-2.5V9.668h2.001a2 2 0 000-4zM14.168 35.67a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm8 0a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm3-30.002h-9V3h9v2.668zm5 30.002a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21z"
                            fill="currentColor"></path>
                    </svg>
                </button>
            </span>
            <img src="" alt="Comic Thumbnail">
        </a>
    </template>
</div>


<?php include __DIR__ . "/src/layout/footer.php"; ?>