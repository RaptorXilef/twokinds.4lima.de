<?php
/**
 * Dies ist der zentrale Renderer für alle Comicseiten.
 * Es lädt Comic-Metadaten und zeigt das Comic-Bild, Navigation und Transkript an.
 * Das Design ist an das Original von Tom Fischbach angepasst.
 * Die Bookmark-Funktion und externe Analytics-Dienste wurden entfernt.
 * Datum und Comic-Titel werden dynamisch aus der JSON im deutschen Format geladen.
 * Der Seitentitel im Browser-Tab ist für eine bessere Sortierbarkeit formatiert.
 * Die Bildpfade unterstützen nun verschiedene Dateiformate (.jpg, .png, .gif).
 * Es wird ein Lückenfüller-Bild angezeigt, falls das Original nicht existiert.
 *
 * Diese Datei wird von den einzelnen Comic-PHP-Dateien (z.B. comic/20250604.php) inkludiert.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

// === ANGEPASST: Lade zentrale Helfer anstelle der alten Skripte ===
// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
require_once __DIR__ . '/load_comic_data.php';
// Lade den neuen Helfer, der die Bildpfade aus dem Cache bereitstellt.
require_once __DIR__ . '/image_cache_helper.php';
// Lade die Hilfsfunktion zum Rendern der Navigationsbuttons.
require_once __DIR__ . '/nav_link_helper.php';


// Die ID der aktuellen Comic-Seite wird aus dem Dateinamen der AUFRUFENDEN Datei extrahiert.
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$urlOriginalbildFilename = ''; // NEU: Initialisieren der Variable

if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];
    $urlOriginalbildFilename = $comicData[$currentComicId]['url_originalbild'] ?? ''; // NEU: Variable aus JSON lesen
} else {
    // Fallback-Werte, falls keine Comic-Daten für die aktuelle Seite gefunden werden.
    error_log("FEHLER: Daten für Comic ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Fehler auf Seite';
    $comicName = 'Comic nicht gefunden';
    $comicTranscript = '<p>Dieser Comic konnte leider nicht geladen werden.</p>';
}

// === NEUE LOGIK: Bildpfade direkt aus dem Cache abrufen ===
$comicImagePath = get_cached_image_path($currentComicId, 'lowres');
$comicHiresPath = get_cached_image_path($currentComicId, 'hires');
$socialMediaPreviewUrl = get_cached_image_path($currentComicId, 'socialmedia');
$bookmarkThumbnailUrl = get_cached_image_path($currentComicId, 'thumbnails');


// --- FALLBACK-LOGIK ---

// Fallback für das Haupt-Comicbild
if (empty($comicImagePath)) {
    // Versuche, das "in_translation"-Bild aus dem Cache als Fallback zu laden.
    $comicImagePath = get_cached_image_path('in_translation', 'lowres');
    $comicHiresPath = get_cached_image_path('in_translation', 'hires');
}
// Wenn auch das nicht verfügbar ist, wird ein externer Platzhalter verwendet.
if (empty($comicImagePath)) {
    $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
    $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
    if ($debugMode)
        error_log("DEBUG: Fallback auf externen Placeholder für Hauptcomicbild (Renderer).");
}

// Fallbacks für Vorschau- und Thumbnail-Bilder
if (empty($socialMediaPreviewUrl)) {
    $socialMediaPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Vorschau+fehlt';
}
if (empty($bookmarkThumbnailUrl)) {
    $bookmarkThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Afehlt';
}


// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ.
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));
// Die allgemeine Seitenbeschreibung, die in header.php verwendet wird.
$siteDescription = 'Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.';

// === Dynamische Basis-URL Bestimmung ===
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
if ($isLocal) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Beispiel: /twokinds/default-website/twokinds/comic/20250604.php
    // Wir wollen die Basis-URL des Projekts: /twokinds/default-website/twokinds/
    $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
    array_pop($pathParts); // Entfernt '20250604.php'
    array_pop($pathParts); // Entfernt 'comic'
    $basePath = implode('/', $pathParts);
    $baseUrl = $protocol . $host . $basePath . '/';
    if ($debugMode)
        error_log("DEBUG: Lokale Basis-URL (Renderer): " . $baseUrl);
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
    if ($debugMode)
        error_log("DEBUG: Live Basis-URL (Renderer): " . $baseUrl);
}

// Setze Parameter für den Header.
// Seitentitel für den Browser-Tab: JJJJ.MM.DD
$pageTitle = 'Comic ' . substr($currentComicId, 0, 4) . '.' . substr($currentComicId, 4, 2) . '.' . substr($currentComicId, 6, 2) . ': ' . $comicName;
// H1-Header auf der Seite: TT.MM.JJJJ
// Hinzufügen von " vom " zwischen Comic-Typ und Datum
$pageHeader = htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman . ': ' . htmlspecialchars($comicName);

// --- Automatischer Cache-Buster für comic.js ---
// Pfad zur JS-Datei auf dem Server (von /src/components/ aus gesehen)
$comicJsPathOnServer = __DIR__ . '/../layout/js/comic.js';
// Web-URL zur JS-Datei (von der Comic-Seite wie /comic/seite.php aus gesehen)
$comicJsWebUrl = '../src/layout/js/comic.js';
// Erstelle den Cache-Buster, falls die Datei existiert
$cacheBuster = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
// Füge die comic.js mit automatischem Cache-Buster als zusätzliches Skript hinzu.
$additionalScripts = "<script type='text/javascript' src='" . htmlspecialchars($comicJsWebUrl . $cacheBuster) . "'></script>";

// Erstelle absolute URLs für Social Media und Lesezeichen
$absoluteSocialPreviewUrl = str_starts_with($socialMediaPreviewUrl, 'http') ? $socialMediaPreviewUrl : $baseUrl . $socialMediaPreviewUrl;
$absoluteBookmarkThumbnailUrl = str_starts_with($bookmarkThumbnailUrl, 'http') ? $bookmarkThumbnailUrl : $baseUrl . $bookmarkThumbnailUrl;

$additionalHeadContent = '
    <link rel="canonical" href="' . $baseUrl . 'comic/' . htmlspecialchars($currentComicId) . '.php">
    <meta property="og:title" content="TwoKinds auf Deutsch - Comic ' . htmlspecialchars($formattedDateGerman) . ': ' . htmlspecialchars($comicName) . '">
    <meta property="og:description" content="' . htmlspecialchars($siteDescription) . '">
    <meta property="og:image" content="' . htmlspecialchars($absoluteSocialPreviewUrl) . '">
    <meta property="og:type" content="article">
    <meta property="og:url" content="' . $baseUrl . 'comic/' . htmlspecialchars($currentComicId) . '.php">
';
// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099'; // Konsistent mit Original für das Design.


// Binde den gemeinsamen Header ein.
// Pfad von src/components/ zu src/layout/
$robotsContent = 'noindex, follow';
include __DIR__ . '/../layout/header.php';
?>

<style>
    /* Passt die Größe des Comic-Bildes an die Containerbreite an */
    #comic-image {
        width: 100%;
        height: auto;
    }
</style>

<article class="comic">
    <header style="position: relative;">
        <!-- H1-Tag im Format des Originals, Datum und Titel werden aus der JSON geladen. -->
        <h1><?php echo $pageHeader; ?></h1>
        <!-- Der Lesezeichen-Button wird nun im comicnav-Container platziert,
            damit die CSS-Regeln aus main.css korrekt angewendet werden können. -->
    </header>

    <div class='comicnav'>
        <!-- Binde die obere Comic-Navigation ein. -->
        <?php include __DIR__ . '/../layout/comic_navigation.php'; ?>
        <!-- Lesezeichen-Button mit der Original-Klasse und den Datenattributen -->
        <button type="button" id="add-bookmark" class="bookmark" title="Diese Seite mit Lesezeichen versehen"
            data-id="<?php echo htmlspecialchars($currentComicId); ?>"
            data-page="<?php echo htmlspecialchars($currentComicId); ?>"
            data-permalink="<?php echo htmlspecialchars($baseUrl . 'comic/' . $currentComicId . '.php'); ?>"
            data-thumb="<?php echo htmlspecialchars($absoluteBookmarkThumbnailUrl); ?>">
            Bookmark this page
        </button>
    </div>

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version. Pfade werden relativ zum comic/ Verzeichnis gesetzt. -->
    <a id="comic-image-link"
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : '../' . $comicHiresPath); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : '../' . $comicImagePath); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page">
    </a>

    <div class='comicnav bottomnav'>
        <!-- Binde die untere Comic-Navigation ein (identisch zur oberen Navigation). -->
        <?php include __DIR__ . '/../layout/comic_navigation.php'; ?>
    </div>

    <div class="below-nav jsdep">
        <div class="nav-instruction">
            <!-- Hinweis zur Navigation mit Tastaturpfeilen auf Deutsch. -->
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K
                navigieren.</span>
        </div>
        <div class="permalink">
            <a href="#" id="copy-comic-url" title="Klicke, um den Link zu dieser Comicseite zu kopieren">URL zur
                aktuellen Seite kopieren</a>
        </div>
    </div>

    <aside class="transcript">
        <!-- NEUE STRUKTUR: Flex-Container für Überschrift und Button -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
            <h2>Transkript</h2>
            <?php if (!empty($urlOriginalbildFilename)): ?>
                <a href="#" class="button" id="toggle-language-btn"
                    data-german-src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : '../' . $comicImagePath); ?>"
                    data-german-href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : '../' . $comicHiresPath); ?>"
                    data-english-filename="<?php echo htmlspecialchars($urlOriginalbildFilename); ?>">Seite auf englisch
                    anzeigen</a>
            <?php endif; ?>
        </div>
        <div class="transcript-content">
            <?php echo $comicTranscript; ?>
        </div>
    </aside>
</article>

<!-- NEU: JavaScript zum Kopieren der URL -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // URL Kopieren Logik
        const copyLink = document.getElementById('copy-comic-url');
        if (copyLink) {
            copyLink.addEventListener('click', function (event) {
                event.preventDefault();
                const urlToCopy = window.location.href;
                const originalText = this.textContent;
                navigator.clipboard.writeText(urlToCopy).then(() => {
                    this.textContent = 'Kopiert!';
                    setTimeout(() => { this.textContent = originalText; }, 2000);
                }).catch(err => {
                    console.error('Fehler beim Kopieren der URL: ', err);
                    // Fallback oder Fehlermeldung
                    this.textContent = 'Fehler beim Kopieren';
                    setTimeout(() => { this.textContent = originalText; }, 2000);
                });
            });
        }

        // Sprache Umschalten Logik
        const toggleBtn = document.getElementById('toggle-language-btn');
        if (toggleBtn) {
            const comicLink = document.getElementById('comic-image-link');
            const comicImage = document.getElementById('comic-image');
            let isGerman = true;
            let englishSrc = '';
            let englishHref = '';

            const originalImageUrlBase = 'https://cdn.twokinds.keenspot.com/comics/';
            const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

            function findEnglishUrl(filename) {
                return new Promise((resolve, reject) => {
                    let found = false;
                    let attempts = 0;
                    imageExtensions.forEach(ext => {
                        const url = originalImageUrlBase + filename + '.' + ext;
                        const img = new Image();
                        img.onload = () => {
                            if (!found) {
                                found = true;
                                resolve(url);
                            }
                        };
                        img.onerror = () => {
                            attempts++;
                            if (attempts === imageExtensions.length && !found) {
                                reject('Kein englisches Bild gefunden');
                            }
                        };
                        img.src = url;
                    });
                });
            }

            toggleBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (isGerman) {
                    // Auf Englisch umschalten
                    if (englishSrc) {
                        comicImage.src = englishSrc;
                        comicLink.href = englishHref;
                        toggleBtn.textContent = 'Seite auf deutsch anzeigen';
                        isGerman = false;
                    } else {
                        const originalText = toggleBtn.textContent;
                        toggleBtn.textContent = 'Lade...';
                        findEnglishUrl(toggleBtn.dataset.englishFilename).then(foundUrl => {
                            englishSrc = foundUrl;
                            englishHref = foundUrl; // Für Hi-Res nehmen wir dieselbe URL
                            comicImage.src = englishSrc;
                            comicLink.href = englishHref;
                            toggleBtn.textContent = 'Seite auf deutsch anzeigen';
                            isGerman = false;
                        }).catch(error => {
                            console.error(error);
                            toggleBtn.textContent = 'Original nicht gefunden';
                            setTimeout(() => { toggleBtn.textContent = originalText; }, 2000);
                        });
                    }
                } else {
                    // Zurück auf Deutsch umschalten
                    comicImage.src = toggleBtn.dataset.germanSrc;
                    comicLink.href = toggleBtn.dataset.germanHref;
                    toggleBtn.textContent = 'Seite auf englisch anzeigen';
                    isGerman = true;
                }
            });
        }
    });
</script>

<!-- Binde den gemeinsamen Footer ein. -->
<?php include __DIR__ . '/../layout/footer.php'; ?>