<?php
/**
 * Diese Datei zeigt alle vom Benutzer gespeicherten Lesezeichen an.
 * Die Lesezeichen werden aus dem Browser-spezifischen localStorage gelesen.
 */

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
// Dies ist notwendig, um die Titel der Lesezeichen korrekt anzuzeigen.
require_once __DIR__ . '/src/components/load_comic_data.php';

// Setze Parameter für den Header. Der Seitentitel wird im Header automatisch mit Präfix versehen.
$pageTitle = 'Deine Lesezeichen';
$pageHeader = 'Deine Lesezeichen'; // Dieser Wert wird im Hauptinhaltsbereich angezeigt.

// Füge die comic.js als zusätzliches Skript hinzu, da sie die Lesezeichen-Logik enthält.
// Der Cache-Buster (c=20250531) sollte beibehalten werden, um Browser-Caching zu umgehen.
$additionalScripts = "<script type='text/javascript' src='src/layout/js/comic.js?c=20250722'></script>";

// Die allgemeine Seitenbeschreibung für SEO und Social Media.
$siteDescription = 'Verwalte und zeige deine gespeicherten Comic-Lesezeichen an.';

// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099'; // Konsistent mit Original für das Design.

// Binde den gemeinsamen Header ein.
include __DIR__ . "/src/layout/header.php";
?>

<section id="bookmarksPage" class="bookmarks-page">
    <h2 class="page-header">Gespeicherte Comic-Lesezeichen</h2>

    <p>Hier siehst du alle Comic-Seiten, die du als Lesezeichen gespeichert hast. Du kannst sie einzeln entfernen oder
        alle auf einmal löschen.</p>

    <div id="bookmarksWrapper" class="flex flex-wrap gap-4 p-4 justify-center">
        <!-- Lesezeichen werden hier von comic.js dynamisch eingefügt -->
    </div>

    <div class="bookmark-actions mt-8 flex flex-col sm:flex-row justify-center items-center gap-4">
        <button id="removeAll"
            class="button bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition-colors duration-200 ease-in-out"
            disabled>
            Alle Lesezeichen entfernen
        </button>
        <button id="export"
            class="button bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition-colors duration-200 ease-in-out"
            disabled>
            Lesezeichen exportieren
        </button>
        <input type="file" id="import" accept=".json" class="hidden" />
        <button id="importButton"
            class="button bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition-colors duration-200 ease-in-out">
            Lesezeichen importieren
        </button>
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
            <img src="" alt="Comic Thumbnail" class="w-24 h-auto rounded-md mb-2">
            <span class="text-xs font-semibold text-gray-700 group-hover:text-blue-600"></span>
            <button type="button"
                class="delete absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                title="Lesezeichen entfernen">
                &times;
            </button>
        </a>
    </template>

</section>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . "/src/layout/footer.php";
?>