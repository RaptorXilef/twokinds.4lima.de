<?php
/**
 * Dies ist die Startseite der TwoKinds-Webseite.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an.
 * Der Seitentitel und Open Graph Metadaten werden spezifisch für die Startseite gesetzt.
 *
 * @file      ROOT/public/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.1.1
 * @since     2.2.0 Umstellung auf globale Pfad-Konstanten.
 * @since     3.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     4.0.1 Umstellung von BASE_URL auf DIRECTORY_PUBLIC_URL für interne Konsistenz.
 * @since     4.0.2 Fehler-Button eingebunden und unnötige CSS Elemente entfernt, welche bereits in main.css stehen.
 * @since     4.1.0 Lädt jQuery und Summernote-Bibliotheken für das Report-Modal (WYSIWYG-Editor).
 * @since     4.1.1 Logik für Bildsuche (checkUrlExists, etc.) nach comic.js verschoben.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

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
    // Fallback-Werte, falls keine Comic-Daten oder der neueste Comic nicht gefunden wird.
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
    // Versuche, das "in_translation"-Bild aus dem Cache als Fallback zu laden.
    //$comicImagePath = get_cached_image_path('in_translation', 'lowres');
    //$comicHiresPath = get_cached_image_path('in_translation', 'hires');
    $comicImagePath = URL::getImgLayoutLowresUrl('in_translation.webp?c=20251101');
    $comicHiresPath = URL::getImgLayoutHiresUrl('in_translation.webp?c=20251101');
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

// === 4. VARIABLEN FÜR DEN HEADER SETZEN (mit Path-Klasse und DIRECTORY_PUBLIC_URL) ===
$pageTitle = 'Startseite der Fan-Übersetzung';
$siteDescription = 'Tauche ein in die Welt von Twokinds – dem beliebten Fantasy-Webcomic von Tom Fischbach, jetzt komplett auf Deutsch verfügbar. Erlebe die spannende Geschichte von Trace und Flora und entdecke die Rassenkonflikte zwischen Menschen und Keidran.';
$ogImage = str_starts_with($socialMediaPreviewUrl, 'http') ? $socialMediaPreviewUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($socialMediaPreviewUrl, './');

$comicJsPathOnServer = DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'comic.min.js';
$comicJsWebUrl = Url::getJsUrl('comic.min.js');
$cacheBuster = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';

// --- KORREKTUR V4.1.1: jQuery und Summernote für Report-Modal hinzugefügt ---
$jsDebug = $debugMode ? 'true' : 'false';
$additionalScripts = "<script nonce=\"{$nonce}\" src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>"; // jQuery ZUERST
$additionalScripts .= "<link href=\"https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css\" rel=\"stylesheet\" nonce=\"{$nonce}\">";
$additionalScripts .= "<script nonce=\"{$nonce}\" src=\"https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js\"></script>";
$additionalScripts .= "<script nonce='" . htmlspecialchars($nonce) . "'>window.phpDebugMode = {$jsDebug};</script>"; // DEBUG
// WICHTIG: comic.js MUSS NACH jQuery/Summernote geladen werden
$additionalScripts .= "<script nonce='" . htmlspecialchars($nonce) . "' type='text/javascript' src='" . htmlspecialchars($comicJsWebUrl . $cacheBuster) . "'></script>";

$viewportContent = 'width=1099';
$robotsContent = 'index, follow'; // Die Startseite soll indexiert werden
$canonicalUrl = DIRECTORY_PUBLIC_URL;

// === 5. HEADER EINBINDEN (mit Path-Klasse) ===
$isComicPage = true;
require_once Path::getPartialTemplatePath('header.php');
?>

    <header class="comic-header">
        <h1><?php echo htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman; ?>:
            <?php echo htmlspecialchars($comicName); ?>
        </h1>
    </header>

    <div class='comicnav'>
        <?php
        $isCurrentPageLatest = true;
        // Pfad zur Navigation jetzt über Path-Klasse
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

        <!-- FEHLER MELDEN BUTTON -->
        <button type="button" id="open-report-modal" class="navarrow nav-report-issue"
            title="Fehler auf dieser Seite melden">
            <span class="nav-wrapper">
                <span class="nav-text" id="report-issue-text">Fehler</span>
            </span>
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
        // Pfad zur Navigation jetzt über Path-Klasse
        include DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . 'navigation_comic.php';
        unset($isCurrentPageLatest);
        ?>
        <!-- SPRACHUMSCHALTER-BUTTON -->
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
    // Binde das Modul zur Anzeige der Charaktere ein (Jetzt mit Path-Klasse)
    require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'display_character.php';
    ?>

<?php
// NEU: Modal einbinden
include_once Path::getPartialTemplatePath('report_modal.php');
?>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    // === GEÄNDERT V4.1.1: Alle Funktionsdefinitionen (checkUrlExists, etc.)
    // wurden nach comic.js verschoben. Dieses Skript ruft nur noch
    // die globalen Funktionen auf (window.findExistingUrl). ===

    document.addEventListener('DOMContentLoaded', function () {
        const debugJs = window.phpDebugMode || false; // Verwende die PHP Debug-Variable

        // --- URL-Kopieren Logik ---
        const copyLink = document.getElementById('copy-comic-url');
        if (copyLink) {
            copyLink.addEventListener('click', function (event) {
                event.preventDefault();
                const urlToCopy = '<?php echo DIRECTORY_PUBLIC_COMIC_URL . DIRECTORY_SEPARATOR . $latestComicId . '.php'; ?>';
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

        // --- Logik für Sprachumschaltung (Nutzt jetzt globale Funktionen) ---
        const toggleBtn = document.getElementById('toggle-language-btn');
        if (toggleBtn) {
            const comicLink = document.getElementById('comic-image-link');
            const comicImage = document.getElementById('comic-image');
            const langToggleText = document.getElementById('lang-toggle-text');
            let isGerman = true;
            let englishSrc = '', englishHref = ''; // Lokaler Cache

            // Prüfen, ob die globalen Funktionen von comic.js geladen wurden
            if (typeof window.checkUrlExists !== 'function' || typeof window.runOriginalProbingLogic !== 'function') {
                console.error("Fehler: Globale Bild-Suchfunktionen (von comic.js) nicht gefunden!");
                if (langToggleText) langToggleText.textContent = "Skript-Fehler";
                return;
            }

            toggleBtn.addEventListener('click', async function (event) {
                event.preventDefault();
                if (isGerman) {
                    if (englishSrc && englishHref) {
                        comicImage.src = englishSrc;
                        comicLink.href = englishHref;
                        if (langToggleText) langToggleText.textContent = 'DE';
                        isGerman = false;
                        return;
                    }

                    const urlFromCache = toggleBtn.dataset.englishUrlFromCache;
                    let foundInCache = false;
                    let mainUrl = null;

                    if (urlFromCache) {
                        if (langToggleText) langToggleText.textContent = 'Lade...';
                        try {
                            // Ruft die globale Funktion auf
                            if (await window.checkUrlExists(urlFromCache)) {
                                mainUrl = urlFromCache;
                                foundInCache = true;
                                if (debugJs) console.log("DEBUG: Originalbild aus Cache verwendet:", urlFromCache);
                            } else if (debugJs) console.warn("DEBUG: Gespeicherter Original-Link nicht erreichbar:", urlFromCache);
                        } catch (err) { console.warn("DEBUG: Fehler bei Prüfung des Original-Cache-Links:", err); }
                    }

                    if (!foundInCache) {
                        if (debugJs) console.log("DEBUG: Originalbild nicht im Cache gefunden/gültig, starte Online-Suche.");
                        // Ruft die globale Funktion auf
                        mainUrl = await window.runOriginalProbingLogic(toggleBtn, langToggleText);
                    }

                    if (mainUrl) {
                        // Ruft die globale Funktion auf
                        const urls = await window.setEnglishImage(mainUrl, toggleBtn, comicImage, comicLink, langToggleText);
                        englishSrc = urls.englishSrc;
                        englishHref = urls.englishHref;
                        isGerman = false;
                    }
                } else {
                    comicImage.src = toggleBtn.dataset.germanSrc;
                    comicLink.href = toggleBtn.dataset.germanHref;
                    if (langToggleText) langToggleText.textContent = 'EN';
                    isGerman = true;
                }
            });
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
