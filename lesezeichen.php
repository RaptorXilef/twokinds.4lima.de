<?php
/**
 * Diese Datei zeigt alle vom Benutzer gespeicherten Lesezeichen an.
 * Die Lesezeichen werden aus dem Browser-spezifischen localStorage gelesen.
 */

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
// Dies ist notwendig, um die Titel der Lesezeichen korrekt anzuzeigen.
require_once __DIR__ . '/src/components/load_comic_data.php';

// Die baseUrl wird nun zentral im header.php bestimmt und ist hier verfügbar.
// Falls diese Datei direkt aufgerufen werden könnte, müsste die baseUrl hier neu bestimmt werden,
// aber da sie immer durch eine Seite mit Header inkludiert wird, ist das nicht nötig.


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

<section id="bookmarksPage" class="bookmarks-page">
    <h2 class="page-header">Deine gespeicherten Lesezeichen</h2>

    <div class="flex justify-center gap-4 mb-4">
        <button id="removeAll" class="button delete">Alle Lesezeichen entfernen</button>
        <button id="export" class="button">Lesezeichen exportieren</button>
        <input type="file" id="import" accept=".json" class="hidden">
        <button id="importButton" class="button">Lesezeichen importieren</button>
    </div>

    <!-- Container für die Lesezeichen-Anzeige -->
    <div id="bookmarksWrapper" class="flex flex-wrap justify-center gap-4 mt-4">
        <!-- Lesezeichen werden hier von comic.js eingefügt -->
    </div>

    <!-- Templates für die Lesezeichen-Anzeige (von comic.js verwendet) -->
    <template id="noBookmarks">
        <p class="text-center text-gray-600 mt-8">Du hast noch keine Lesezeichen gespeichert. Besuche eine Comic-Seite
            und klicke auf das Lesezeichen-Symbol, um eine Seite hinzuzufügen.</p>
    </template>

    <template id="pageBookmarkWrapper">
        <div class="chapter-links flex flex-wrap justify-center gap-4 mt-4">
            <!-- Lesezeichen-Elemente werden hier eingefügt -->
        </div>
    </template>

    <template id="pageBookmark">
        <a href="#"
            class="bookmark-item relative flex flex-col items-center justify-center p-2 bg-gray-100 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 ease-in-out group">
            <!-- src wird von comic.js gesetzt, aber hier ist ein Fallback für den Fall, dass es nicht geladen wird -->
            <img src="https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Aaktuell%0Anicht%0Averf%C3%BCgbar"
                alt="Comic Thumbnail" class="w-24 h-auto rounded-md mb-2">
            <span class="text-xs font-semibold text-gray-700 group-hover:text-blue-600"></span>
            <button type="button"
                class="delete absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                &times;
            </button>
        </a>
    </template>
</section>

<!-- Custom Confirmation Modal -->
<div id="customConfirmModal" class="modal">
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