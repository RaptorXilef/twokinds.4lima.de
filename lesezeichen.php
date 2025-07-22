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

<style>
    /* Felix's Custom CSS für Lesezeichencontainer und Bilder */
    /* Stellt sicher, dass der Container immer als Flexbox angezeigt wird */
    .bookmarks-flex-container {
        display: flex !important;
        /* Überschreibt alle display: none; */
        flex-wrap: wrap;
        justify-content: flex-start;
        /* Richtet die Lesezeichen-Elemente als Gruppe linksbündig aus */
        gap: 1rem;
        /* Entspricht Tailwind 'gap-4' */
        margin-top: 1rem;
        /* Entspricht Tailwind 'mt-4' */
    }

    /* Stellt sicher, dass das Bild sichtbar ist und eine Mindestgröße hat */
    .bookmark-thumbnail-image {
        width: 100%;
        /* Füllt die Breite des Elternelements (bookmark-item) */
        height: auto;
        /* Behält das Seitenverhältnis bei */
        /*min-width: 96px;*/
        /* Entspricht Tailwind 'w-24' (96px) */
        /*min-height: 96px;*/
        /* Eine Mindesthöhe, um es sichtbar zu machen */
        /*border-radius: 0.5rem;*/
        /* Abgerundete Ecken */
        margin-bottom: 0.5rem;
        /* Abstand nach unten */
        display: block !important;
        /* Stellt sicher, dass es ein Blockelement ist und überschreibt ggf. display: none; */
        object-fit: cover;
        /* Bildausschnitt, der den Bereich füllt */
        background-color: #e0e0e0;
        /* Ein leichter Grauton als Platzhalter/Hintergrund */
        /*border: 1px solid #ccc;*/
        /* Rand zur besseren Sichtbarkeit des Kastens */
        opacity: 1 !important;
        /* WICHTIG: Überschreibt opacity: 0 aus main.css */
        position: static !important;
        /* WICHTIG: Überschreibt absolute Positionierung */
        transform: none !important;
        /* WICHTIG: Entfernt jegliche Transformation */
    }

    /* Stil für das Lesezeichen-Element selbst, um die Breite zu fixieren und Inhalt linksbündig zu machen */
    .bookmark-item {
        /*width: 96px;*/
        /* Entspricht Tailwind 'w-24' */
        flex-shrink: 0;
        /* Verhindert, dass Elemente schrumpfen */
        /* Inhalt linksbündig ausrichten */
        align-items: flex-start;
        /* Für Flexbox-Container (bookmark-item ist flex-col) */
        text-align: left;
        /* Für Text innerhalb des Elements */
    }
</style>

<section id="bookmarksPage" class="bookmarks-page">
    <h2 class="page-header">Deine gespeicherten Lesezeichen</h2>

    <div class="flex justify-center gap-4 mb-4">
        <button id="removeAll" class="button delete">Alle Lesezeichen entfernen</button>
        <button id="export" class="button">Lesezeichen exportieren</button>
        <input type="file" id="import" accept=".json" class="hidden">
        <button id="importButton" class="button">Lesezeichen importieren</button>
    </div>

    <!-- Container für die Lesezeichen-Anzeige -->
    <div id="bookmarksWrapper">
        <!-- Lesezeichen werden hier von comic.js eingefügt -->
    </div>

    <!-- Templates für die Lesezeichen-Anzeige (von comic.js verwendet) -->
    <template id="noBookmarks">
        <p class="text-center text-gray-600 mt-8">Du hast noch keine Lesezeichen gespeichert. Besuche eine Comic-Seite
            und klicke auf das Lesezeichen-Symbol, um eine Seite hinzuzufügen.</p>
    </template>

    <template id="pageBookmarkWrapper">
        <!-- Die Klasse "chapter-links" wird beibehalten, da comic.js sie als Selektor verwendet. -->
        <!-- Zusätzliche Klasse "bookmarks-flex-container" für spezifisches Styling. -->
        <div class="chapter-links bookmarks-flex-container">
            <!-- Lesezeichen-Elemente werden hier eingefügt -->
        </div>
    </template>

    <template id="pageBookmark">
        <a href="#"
            class="bookmark-item relative flex flex-col p-2 bg-gray-100 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 ease-in-out group">
            <!-- src wird von comic.js gesetzt -->
            <img src="" alt="Comic Thumbnail" class="bookmark-thumbnail-image">
            <span class="text-xs font-semibold text-gray-700 group-hover:text-blue-600"></span>
            <button type="button"
                class="delete absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                &times;
            </button>
        </a>
    </template>
</section>

<!-- Custom Confirmation Modal -->
<div id="customConfirmModal" class="modal" style="display: none;">
    <div class="modal-content">
        <p id="confirmMessage"></p>
        <button id="confirmYes" class="button">Ja</button>
        <button id="confirmNo" class="button delete">Nein</button>
    </div>
</div>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . "/src/layout/footer.php";
?>