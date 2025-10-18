<?php
/**
 * Dies ist die Startseite des Comic-Bereichs.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an und verweist
 * mittels Canonical-Tag auf die Haupt-Startseite, um Duplicate Content zu vermeiden.
 * 
 * @file      ROOT/public/comic/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.1
 * @since     2.2.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 * @since     4.0.1 Korrektur des URL-Kopierens auf der neuesten Comicseite im JS-Teil des Codes.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../../src/components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit der Path-Klasse) ===
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'load_comic_data.php';
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'helper_image_cache.php';

// Ermittle die ID des neuesten Comics
$comicKeys = array_keys($comicData);
$latestComicId = !empty($comicKeys) ? end($comicKeys) : '';

// Setze die aktuelle Comic-ID für diese Seite auf die ID des neuesten Comics.
$currentComicId = $latestComicId;

// Hole die Daten für den neuesten Comic
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
    error_log("Fehler: Daten für den neuesten Comic (ID '{$currentComicId}') nicht in 'comic_var.json' gefunden.");
    $comicTyp = 'Comicseite';
    $comicName = 'Willkommen';
    $comicTranscript = '<p>Willkommen auf TwoKinds auf Deutsch! Leider konnte der neueste Comic nicht geladen werden.</p>';
}

// === 3. BILD-PFADE & FALLBACKS ===
$comicImagePath = get_cached_image_path($currentComicId, 'lowres');
$comicHiresPath = get_cached_image_path($currentComicId, 'hires');
$socialMediaPreviewUrl = get_cached_image_path($currentComicId, 'socialmedia');
$bookmarkThumbnailUrl = get_cached_image_path($currentComicId, 'thumbnails');
$urlOriginalbildFromCache = get_cached_image_path($currentComicId, 'url_originalbild');
$urlOriginalsketchFromCache = get_cached_image_path($currentComicId, 'url_originalsketch');

// --- FALLBACK-LOGIK ---
if (empty($comicImagePath)) {
    $comicImagePath = get_cached_image_path('in_translation', 'lowres');
    $comicHiresPath = get_cached_image_path('in_translation', 'hires');
}
if (empty($comicImagePath)) {
    $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
    $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
}
if (empty($socialMediaPreviewUrl)) {
    $socialMediaPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Vorschau+fehlt';
}
if (empty($bookmarkThumbnailUrl)) {
    $bookmarkThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Afehlt';
}

$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Neueste Comicseite';
$siteDescription = 'Die neueste Comicseite von TwoKinds in deutscher Übersetzung. ' . htmlspecialchars($comicName);
$ogImage = str_starts_with($socialMediaPreviewUrl, 'http') ? $socialMediaPreviewUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($socialMediaPreviewUrl, './');

$comicJsPathOnServer = DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'comic.min.js';
$comicJsWebUrl = Url::getJs('comic.min.js');
$cacheBuster = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
$additionalScripts = "<script nonce='" . htmlspecialchars($nonce) . "' type='text/javascript' src='" . htmlspecialchars($comicJsWebUrl . $cacheBuster) . "'></script>";
$viewportContent = 'width=1099';
$robotsContent = 'noindex, follow';
$canonicalUrl = DIRECTORY_PUBLIC_URL;

// === 5. HEADER EINBINDEN (mit Path-Klasse) ===
require_once Path::getTemplatePartial('header.php');
?>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    #comic-image {
        width: 100%;
        height: auto;
    }

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
        $isCurrentPageLatest = true;
        include DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . 'navigation_comic.php';
        unset($isCurrentPageLatest);
        ?>
        <button type="button" id="add-bookmark" class="bookmark" title="Diese Seite mit Lesezeichen versehen"
            data-id="<?php echo htmlspecialchars($currentComicId); ?>"
            data-page="<?php echo htmlspecialchars($comicName); ?>"
            data-permalink="<?php echo htmlspecialchars(DIRECTORY_PUBLIC_URL . '/comic/' . $currentComicId . $dateiendungPHP); ?>"
            data-thumb="<?php echo htmlspecialchars(str_starts_with($bookmarkThumbnailUrl, 'http') ? $bookmarkThumbnailUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($bookmarkThumbnailUrl, './')); ?>">
            Seite merken
        </button>
    </div>

    <a id="comic-image-link"
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicHiresPath, './')); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicImagePath, './')); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page" fetchpriority="high">
    </a>

    <div class='comicnav bottomnav'>
        <?php
        $isCurrentPageLatest = true;
        include DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . 'navigation_comic.php';
        unset($isCurrentPageLatest);
        ?>
        <!-- NEUER SPRACHUMSCHALTER-BUTTON -->
        <?php if (!empty($urlOriginalbildFilename)): ?>
            <button type="button" id="toggle-language-btn" class="navarrow nav-lang-toggle" title="Sprache umschalten"
                data-german-src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicImagePath, './')); ?>"
                data-german-href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicHiresPath, './')); ?>"
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
        </div>
        <div class="transcript-content">
            <?php echo $comicTranscript; ?>
        </div>
    </aside>

    <?php
    require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'display_character.php';
    ?>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const copyLink = document.getElementById('copy-comic-url');
        if (copyLink) {
            copyLink.addEventListener('click', function (event) {
                event.preventDefault();
                // KORREKTUR: $baseUrl durch DIRECTORY_PUBLIC_URL ersetzt
                const urlToCopy = '<?php echo DIRECTORY_PUBLIC_COMIC_URL . '/' . $latestComicId . $dateiendungPHP; ?>';
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

<?php require_once Path::getTemplatePartial('footer.php'); ?>