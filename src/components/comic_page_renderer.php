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
 * Er wird also von den einzelnen PHP-Dateien im /comic/ Verzeichnis eingebunden.
 *
 * Diese Version ist an die neue, sichere Architektur mit public_init.php
 * und einem intelligenten Header angepasst.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
require_once __DIR__ . '/public_init.php';

// === 2. LADE-SKRIPTE & DATEN ===
require_once __DIR__ . '/load_comic_data.php';
// Lade den neuen Helfer, der die Bildpfade aus dem Cache bereitstellt.
require_once __DIR__ . '/image_cache_helper.php';

// Die ID der aktuellen Comic-Seite wird aus dem Dateinamen der AUFRUFENDEN Datei extrahiert.
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$urlOriginalbildFilename = '';

if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];
    $urlOriginalbildFilename = $comicData[$currentComicId]['url_originalbild'] ?? '';
} else {
    // Fallback-Werte, falls keine Comic-Daten für die aktuelle Seite gefunden werden.
    error_log("FEHLER: Daten für Comic ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Fehler auf Seite';
    $comicName = 'Comic nicht gefunden';
    $comicTranscript = '<p>Dieser Comic konnte leider nicht geladen werden.</p>';
}

// === 3. BILD-PFADE & FALLBACKS ===
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
}

// Fallbacks für Vorschau- und Thumbnail-Bilder
if (empty($socialMediaPreviewUrl)) {
    $socialMediaPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Vorschau+fehlt';
}
if (empty($bookmarkThumbnailUrl)) {
    $bookmarkThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Afehlt';
}

// Konvertiere die Comic-ID (Datum) ins deutsche Format
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Comic ' . substr($currentComicId, 0, 4) . '.' . substr($currentComicId, 4, 2) . '.' . substr($currentComicId, 6, 2) . ': ' . $comicName;
$siteDescription = 'TwoKinds Comic vom ' . $formattedDateGerman . ' - ' . htmlspecialchars($comicName) . '. ' . strip_tags($comicTranscript);
$ogImage = str_starts_with($socialMediaPreviewUrl, 'http') ? $socialMediaPreviewUrl : $baseUrl . ltrim($socialMediaPreviewUrl, './');

// Cache-Buster für comic.js
$comicJsPathOnServer = __DIR__ . '/../layout/js/comic.js';
$comicJsWebUrl = $baseUrl . 'src/layout/js/comic.js';
$cacheBuster = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
$additionalScripts = "<script nonce=\"" . htmlspecialchars($nonce) . "\" type='text/javascript' src='" . htmlspecialchars($comicJsWebUrl . $cacheBuster) . "'></script>";

$viewportContent = 'width=1099';
$robotsContent = 'index, follow'; // Einzelne Comicseiten sollen indexiert werden

// === 5. HEADER EINBINDEN ===
require_once __DIR__ . '/../layout/header.php';
?>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    /* Passt die Größe des Comic-Bildes an die Containerbreite an */
    #comic-image {
        width: 100%;
        height: auto;
    }

    /* Ersetzt die Inline-Stile für CSP-Konformität */
    .comic-header {
        position: relative;
    }

    .transcript-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
</style>

<article class="comic">
    <header class="comic-header">
        <!-- H1-Tag im Format des Originals, Datum und Titel werden aus der JSON geladen. -->
        <h1><?php echo htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman . ': ' . htmlspecialchars($comicName); ?>
        </h1>
    </header>

    <div class='comicnav'>
        <!-- Binde die obere Comic-Navigation ein. -->
        <?php include __DIR__ . '/../layout/comic_navigation.php'; ?>
        <!-- Lesezeichen-Button mit der Original-Klasse und den Datenattributen -->
        <button type="button" id="add-bookmark" class="bookmark" title="Diese Seite mit Lesezeichen versehen"
            data-id="<?php echo htmlspecialchars($currentComicId); ?>"
            data-page="<?php echo htmlspecialchars($currentComicId); ?>"
            data-permalink="<?php echo htmlspecialchars($baseUrl . 'comic/' . $currentComicId); ?>"
            data-thumb="<?php echo htmlspecialchars(str_starts_with($bookmarkThumbnailUrl, 'http') ? $bookmarkThumbnailUrl : $baseUrl . ltrim($bookmarkThumbnailUrl, './')); ?>">
            Bookmark this page
        </button>
    </div>

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version. Pfade werden relativ zum comic/ Verzeichnis gesetzt. -->
    <a id="comic-image-link"
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : '../' . $comicHiresPath); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : '../' . $comicImagePath); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page" fetchpriority="high">
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
        <!-- Flex-Container für Überschrift und Button -->
        <div class="transcript-header">
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

<!-- JavaScript zum Kopieren der URL -->
<script nonce="<?php echo htmlspecialchars($nonce); ?>">
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
            const sketchImageUrlBase = 'https://twokindscomic.com/images/';
            const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

            function findEnglishUrl(filename) {
                return new Promise((resolve, reject) => {
                    let found = false;
                    let attempts = 0;
                    imageExtensions.forEach(ext => {
                        const url = originalImageUrlBase + filename + '.' + ext;
                        const img = new Image();
                        img.onload = () => { if (!found) { found = true; resolve(url); } };
                        img.onerror = () => {
                            attempts++;
                            if (attempts === imageExtensions.length && !found) reject('Kein englisches Bild gefunden');
                        };
                        img.src = url;
                    });
                });
            }

            function findEnglishSketchUrl(baseFilename) {
                return new Promise((resolve, reject) => {
                    const sketchFilename = baseFilename.substring(0, 8) + '_sketch';
                    let found = false;
                    let attempts = 0;
                    imageExtensions.forEach(ext => {
                        const url = sketchImageUrlBase + sketchFilename + '.' + ext;
                        const img = new Image();
                        img.onload = () => { if (!found) { found = true; resolve(url); } };
                        img.onerror = () => {
                            attempts++;
                            if (attempts === imageExtensions.length && !found) reject('Kein englisches Sketch-Bild gefunden');
                        };
                        img.src = url;
                    });
                });
            }

            toggleBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (isGerman) {
                    // Auf Englisch umschalten
                    if (englishSrc && englishHref) {
                        comicImage.src = englishSrc;
                        comicLink.href = englishHref;
                        toggleBtn.textContent = 'Seite auf deutsch anzeigen';
                        isGerman = false;
                    } else {
                        const originalText = toggleBtn.textContent;
                        toggleBtn.textContent = 'Lade...';
                        const englishFilename = toggleBtn.dataset.englishFilename;

                        const mainImagePromise = findEnglishUrl(englishFilename);
                        const sketchImagePromise = findEnglishSketchUrl(englishFilename);

                        mainImagePromise.then(mainUrl => {
                            englishSrc = mainUrl;
                            comicImage.src = englishSrc;

                            sketchImagePromise.then(sketchUrl => {
                                englishHref = sketchUrl;
                                comicLink.href = englishHref;
                            }).catch(sketchError => {
                                console.warn(sketchError);
                                englishHref = mainUrl;
                                comicLink.href = englishHref;
                            }).finally(() => {
                                toggleBtn.textContent = 'Seite auf deutsch anzeigen';
                                isGerman = false;
                            });

                        }).catch(mainError => {
                            console.error(mainError);
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
<?php require_once __DIR__ . '/../layout/footer.php'; ?>