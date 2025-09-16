<?php
/**
 * Comic-Seiten-Renderer
 *
 * Dieses Skript wird von den einzelnen Comic-Seiten im /comic/ Verzeichnis aufgerufen.
 * Es extrahiert die Comic-ID aus dem Dateinamen, lädt die entsprechenden Daten
 * und rendert die vollständige HTML-Seite für den jeweiligen Comic.
 */

// === 1. ZENTRALE INITIALISIERUNG ===
require_once __DIR__ . '/public_init.php';

// === 2. LADE-SKRIPTE & DATEN ===
require_once __DIR__ . '/load_comic_data.php';
require_once __DIR__ . '/image_cache_helper.php';

// === 3. AKTUELLE COMIC-ID ERMITTELN ===
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$urlOriginalbildFilename = '';

// === 4. DATENVALIDIERUNG UND 404-HANDLING ===
if (!isset($comicData[$currentComicId])) {
    http_response_code(404);
    $pageTitle = 'Seite nicht gefunden (404)';
    $siteDescription = 'Der gesuchte Comic konnte leider nicht gefunden werden.';
    require_once __DIR__ . '/../layout/header.php';
    echo '<h1>404 - Seite nicht gefunden</h1>';
    echo '<p>Leider existiert unter dieser Adresse kein Comic. Möglicherweise haben Sie sich vertippt oder die Seite wurde verschoben.</p>';
    echo '<p><a href="' . htmlspecialchars($baseUrl) . '">Zurück zur neuesten Comicseite</a></p>';
    require_once __DIR__ . '/../layout/footer.php';
    exit();
}

// === 5. COMIC-DATEN LADEN ===
$comicTyp = $comicData[$currentComicId]['type'];
$comicName = $comicData[$currentComicId]['name'];
$comicTranscript = $comicData[$currentComicId]['transcript'];
$urlOriginalbildFilename = $comicData[$currentComicId]['url_originalbild'] ?? '';

// === 6. BILD-PFADE & FALLBACKS ===
$comicImagePath = get_cached_image_path($currentComicId, 'lowres');
$comicHiresPath = get_cached_image_path($currentComicId, 'hires');
$socialMediaPreviewUrl = get_cached_image_path($currentComicId, 'socialmedia');
$bookmarkThumbnailUrl = get_cached_image_path($currentComicId, 'thumbnails');
$urlOriginalbildFromCache = get_cached_image_path($currentComicId, 'url_originalbild');
$urlOriginalsketchFromCache = get_cached_image_path($currentComicId, 'url_originalsketch');

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
    $socialMediaPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Vorschau+fehlt';
}
if (empty($bookmarkThumbnailUrl)) {
    $bookmarkThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Afehlt';
}

// Konvertiere die Comic-ID (Datum) ins deutsche Format
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));

// === 7. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = htmlspecialchars($comicTyp) . ': ' . htmlspecialchars($comicName);
$siteDescription = 'TwoKinds auf Deutsch - ' . htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman . ': ' . htmlspecialchars($comicName);
$ogImage = str_starts_with($socialMediaPreviewUrl, 'http') ? $socialMediaPreviewUrl : $baseUrl . ltrim($socialMediaPreviewUrl, './');
$comicJsPathOnServer = __DIR__ . '/../layout/js/comic.min.js';
$comicJsWebUrl = $baseUrl . 'src/layout/js/comic.min.js';
$cacheBuster = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
$additionalScripts = "<script nonce='" . htmlspecialchars($nonce) . "' type='text/javascript' src='" . htmlspecialchars($comicJsWebUrl . $cacheBuster) . "'></script>";
$viewportContent = 'width=1099';
$robotsContent = 'index, follow'; // Einzelne Comicseiten sollen indexiert werden
$canonicalUrl = $baseUrl . 'comic/' . $currentComicId . '.php';

// === 8. HEADER EINBINDEN ===
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
        <h1><?php echo htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman; ?>:
            <?php echo htmlspecialchars($comicName); ?>
        </h1>
    </header>

    <div class='comicnav'>
        <?php
        $comicKeys = array_keys($comicData);
        $latestComicId = !empty($comicKeys) ? end($comicKeys) : '';
        if ($currentComicId === $latestComicId) {
            $isCurrentPageLatest = true;
        }
        include __DIR__ . '/../layout/comic_navigation.php';
        if (isset($isCurrentPageLatest)) {
            unset($isCurrentPageLatest);
        }
        ?>
        <button type="button" id="add-bookmark" class="bookmark" title="Diese Seite mit Lesezeichen versehen"
            data-id="<?php echo htmlspecialchars($currentComicId); ?>"
            data-page="<?php echo htmlspecialchars($comicName); ?>"
            data-permalink="<?php echo htmlspecialchars($canonicalUrl); ?>"
            data-thumb="<?php echo htmlspecialchars(str_starts_with($bookmarkThumbnailUrl, 'http') ? $bookmarkThumbnailUrl : $baseUrl . ltrim($bookmarkThumbnailUrl, './')); ?>">
            Seite merken
        </button>
    </div>

    <a id="comic-image-link"
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : $baseUrl . ltrim($comicHiresPath, './')); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : $baseUrl . ltrim($comicImagePath, './')); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page" fetchpriority="high">
    </a>

    <div class='comicnav bottomnav'>
        <?php
        if ($currentComicId === $latestComicId) {
            $isCurrentPageLatest = true;
        }
        include __DIR__ . '/../layout/comic_navigation.php';
        if (isset($isCurrentPageLatest)) {
            unset($isCurrentPageLatest);
        }
        ?>
        <!-- NEUER SPRACHUMSCHALTER-BUTTON -->
        <?php if (!empty($urlOriginalbildFilename)): ?>
            <button type="button" id="toggle-language-btn" class="navarrow nav-lang-toggle" title="Sprache umschalten"
                data-german-src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : $baseUrl . ltrim($comicImagePath, './')); ?>"
                data-german-href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : $baseUrl . ltrim($comicHiresPath, './')); ?>"
                data-english-filename="<?php echo htmlspecialchars($urlOriginalbildFilename); ?>"
                data-english-url-from-cache="<?php echo htmlspecialchars($urlOriginalbildFromCache ?? ''); ?>"
                data-english-sketch-url-from-cache="<?php echo htmlspecialchars($urlOriginalsketchFromCache ?? ''); ?>">
                <span class="nav-wrapper">
                    <span class="nav-text" id="lang-toggle-text">EN</span>
                </span>
            </button>
        <?php endif; ?>
    </div>

    <div class="below-nav jsdep">
        <div class="nav-instruction">
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K
                navigieren.</span>
        </div>
        <div class="permalink">
            <a href="#" id="copy-comic-url" title="Klicke, um den Link zu dieser Comicseite zu kopieren">URL zur
                aktuellen Seite kopieren</a>
        </div>
    </div>

    <aside class="transcript">
        <div class="transcript-header">
            <h2>Transkript</h2>
            <!-- DER ALTE BUTTON WURDE HIER ENTFERNT -->
        </div>
        <div class="transcript-content">
            <?php echo $comicTranscript; ?>
        </div>
    </aside>

    <?php
    // NEU: Binde das Modul zur Anzeige der Charaktere ein
    require_once __DIR__ . '/character_display.php';
    ?>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const copyLink = document.getElementById('copy-comic-url');
        if (copyLink) {
            copyLink.addEventListener('click', function (event) {
                event.preventDefault();
                const urlToCopy = '<?php echo $canonicalUrl; ?>';
                const originalText = this.textContent;
                navigator.clipboard.writeText(urlToCopy).then(() => {
                    this.textContent = 'Kopiert!';
                    setTimeout(() => { this.textContent = originalText; }, 2000);
                }).catch(err => {
                    console.error('Fehler beim Kopieren der URL: ', err);
                    this.textContent = 'Fehler beim Kopieren';
                });
            });
        }

        // --- ANGEPASSTE LOGIK FÜR SPRACHUMSCHALTUNG ---
        const toggleBtn = document.getElementById('toggle-language-btn');
        if (toggleBtn) {
            const comicLink = document.getElementById('comic-image-link');
            const comicImage = document.getElementById('comic-image');
            const langToggleText = document.getElementById('lang-toggle-text'); // NEU
            let isGerman = true;
            let englishSrc = '', englishHref = '';
            const originalImageUrlBase = 'https://cdn.twokinds.keenspot.com/comics/';
            const sketchImageUrlBase = 'https://twokindscomic.com/images/';
            const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

            function checkUrl(url) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(url);
                    img.onerror = () => reject(`Bild unter ${url} nicht gefunden.`);
                    img.src = url;
                });
            }

            function findEnglishUrl(filename) {
                return new Promise((resolve, reject) => {
                    let found = false, attempts = 0;
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
                    let found = false, attempts = 0;
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

            function setEnglishImage(mainUrl) {
                englishSrc = mainUrl;
                comicImage.src = englishSrc;
                const sketchUrlFromCache = toggleBtn.dataset.englishSketchUrlFromCache;
                const setHref = (url) => {
                    englishHref = url;
                    comicLink.href = englishHref;
                    if (langToggleText) langToggleText.textContent = 'DE'; // Geändert
                    isGerman = false;
                };
                const runSketchProbingLogic = () => {
                    findEnglishSketchUrl(toggleBtn.dataset.englishFilename)
                        .then(setHref)
                        .catch(err => { console.warn(err); setHref(mainUrl); });
                };

                if (sketchUrlFromCache) {
                    checkUrl(sketchUrlFromCache).then(setHref).catch(err => {
                        console.warn(err);
                        runSketchProbingLogic();
                    });
                } else {
                    runSketchProbingLogic();
                }
            }

            function runOriginalProbingLogic() {
                const originalText = langToggleText ? langToggleText.textContent : 'EN';
                if (langToggleText) langToggleText.textContent = 'Lade...';
                findEnglishUrl(toggleBtn.dataset.englishFilename)
                    .then(setEnglishImage)
                    .catch(err => {
                        console.error(err);
                        if (langToggleText) langToggleText.textContent = 'Original nicht gefunden';
                        setTimeout(() => { if (langToggleText) langToggleText.textContent = originalText; }, 2000);
                    });
            }

            toggleBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (isGerman) {
                    if (englishSrc && englishHref) {
                        comicImage.src = englishSrc;
                        comicLink.href = englishHref;
                        if (langToggleText) langToggleText.textContent = 'DE'; // Geändert
                        isGerman = false;
                        return;
                    }
                    const urlFromCache = toggleBtn.dataset.englishUrlFromCache;
                    if (urlFromCache) {
                        if (langToggleText) langToggleText.textContent = 'Lade...'; // Geändert
                        checkUrl(urlFromCache).then(setEnglishImage).catch(err => {
                            console.warn(err);
                            runOriginalProbingLogic();
                        });
                    } else {
                        runOriginalProbingLogic();
                    }
                } else {
                    comicImage.src = toggleBtn.dataset.germanSrc;
                    comicLink.href = toggleBtn.dataset.germanHref;
                    if (langToggleText) langToggleText.textContent = 'EN'; // Geändert
                    isGerman = true;
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>