<?php

/**
 * Comic-Seiten-Renderer
 *
 * Dieses Skript wird von den einzelnen Comic-Seiten im /comic/ Verzeichnis aufgerufen.
 * Es extrahiert die Comic-ID aus dem Dateinamen, lädt die entsprechenden Daten
 * und rendert die vollständige HTML-Seite für den jeweiligen Comic.
 *
 * @file      ROOT/src/renderer/renderer_comic_page.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.4.2
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 * @since     4.2.0 Fügt den "Fehler melden"-Button und das Modal-Include hinzu. Korrigiert Lesezeichen-Daten und Charakter-Anzeige.
 * @since     4.3.1 Korrigiert die Einbindung von display_character.php und fügt JS-Debug-Variable hinzu.
 * @since     4.3.2 Fehler melden Button in obere Navigantionsleiste verschoben und design angepasst.
 * @since     4.4.0 Lädt jQuery und Summernote-Bibliotheken für das Report-Modal (WYSIWYG-Editor).
 * @since     4.4.1 Machte Bild-Suchfunktionen global verfügbar.
 * @since     4.4.2 Logik für Bildsuche (checkUrlExists, findExistingUrl etc.) vollständig nach comic.js verschoben. Dieses Skript ruft nur noch die globalen Funktionen (window.findExistingUrl) auf.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG ===
require_once __DIR__ . '/../components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN ===
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'load_comic_data.php'; // Lädt $comicData
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'helper_image_cache.php'; // Lädt get_cached_image_path()

// === 3. AKTUELLE COMIC-ID ERMITTELN ===
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// === 4. DATENVALIDIERUNG UND 404-HANDLING ===
if (!isset($comicData[$currentComicId])) {
    http_response_code(404);
    $pageTitle = 'Seite nicht gefunden (404)';
    $siteDescription = 'Der gesuchte Comic konnte leider nicht gefunden werden.';
    $isComicPage = true;
    require_once Path::getPartialTemplatePath('header.php');
    echo '<h1>404 - Seite nicht gefunden</h1>';
    echo '<p>Leider existiert unter dieser Adresse kein Comic. Möglicherweise haben Sie sich vertippt oder die Seite wurde verschoben.</p>';
    echo '<p><a href="' . htmlspecialchars(DIRECTORY_PUBLIC_URL) . '">Zurück zur neuesten Comicseite</a></p>';
    require_once Path::getPartialTemplatePath('footer.php');
    exit();
}

// === 5. COMIC-DATEN LADEN ===
$comicInfo = $comicData[$currentComicId]; // Hole das Array für die aktuelle ID
$comicTyp = $comicInfo['type'] ?? 'Comicseite';
$comicName = $comicInfo['name'] ?? '';
$comicTranscript = $comicInfo['transcript'] ?? 'Kein Transkript verfügbar.';
$urlOriginalbildFilename = $comicInfo['url_originalbild'] ?? '';
$characterIds = $comicInfo['charaktere'] ?? []; // Lade Charakter-IDs

// === 6. BILD-PFADE & FALLBACKS ===
$comicImagePath = get_cached_image_path($currentComicId, 'lowres');
$comicHiresPath = get_cached_image_path($currentComicId, 'hires');
$socialMediaPreviewUrl = get_cached_image_path($currentComicId, 'socialmedia');
$bookmarkThumbnailUrl = get_cached_image_path($currentComicId, 'thumbnails');
$urlOriginalbildFromCache = get_cached_image_path($currentComicId, 'url_originalbild');
$urlOriginalsketchFromCache = get_cached_image_path($currentComicId, 'url_originalsketch');

// --- FALLBACK-LOGIK ---
if (empty($comicImagePath)) {
//$comicImagePath = get_cached_image_path('in_translation', 'lowres');
    $comicImagePath = URL::getImgLayoutLowresUrl('in_translation.webp?c=20251101');
    if (empty($comicHiresPath)) { // Nur wenn Hires auch fehlt
        //$comicHiresPath = get_cached_image_path('in_translation', 'hires');
        $comicHiresPath = URL::getImgLayoutHiresUrl('in_translation.webp?c=20251101');
    }
}
if (empty($comicImagePath)) {
    $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
    if (empty($comicHiresPath)) {
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
    }
}
// Fallback für Hires, falls nur Lowres existiert
if (empty($comicHiresPath)) {
    $comicHiresPath = $comicImagePath; // Fallback auf Lowres-URL
}
if (empty($socialMediaPreviewUrl)) {
    $socialMediaPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Vorschau+fehlt';
}
if (empty($bookmarkThumbnailUrl)) {
    $bookmarkThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Afehlt';
}

// Deutsche Datumsformatierung
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));

// === 7. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = htmlspecialchars($comicTyp) . ': ' . htmlspecialchars($comicName);
$siteDescription = 'TwoKinds auf Deutsch - ' . htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman . ': ' . htmlspecialchars($comicName);
$ogImage = str_starts_with($socialMediaPreviewUrl, 'http') ? $socialMediaPreviewUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($socialMediaPreviewUrl, '/');

// JS für Comic-Seite laden
$comicJsPathOnServer = DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'comic.min.js';
$comicJsWebUrl = Url::getJsUrl('comic.min.js');
$cacheBuster = file_exists($comicJsPathOnServer) ? '?c=' . filemtime($comicJsPathOnServer) : '';
// Füge Debug-Modus als JS-Variable hinzu
$jsDebug = $debugMode ? 'true' : 'false';

// --- jQuery und Summernote für Report-Modal ---
$additionalScripts = "<script nonce=\"{$nonce}\" src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>"; // jQuery ZUERST
$additionalScripts .= "<link href=\"https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css\" rel=\"stylesheet\" nonce=\"{$nonce}\">";
$additionalScripts .= "<script nonce=\"{$nonce}\" src=\"https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js\"></script>";

$additionalScripts .= "<script nonce='" . htmlspecialchars($nonce) . "'>window.phpDebugMode = {$jsDebug};</script>"; // DEBUG
// WICHTIG: comic.js MUSS NACH jQuery/Summernote geladen werden
$additionalScripts .= "<script nonce='" . htmlspecialchars($nonce) . "' type='text/javascript' src='" . htmlspecialchars($comicJsWebUrl . $cacheBuster) . "'></script>";

$viewportContent = 'width=1099';
$robotsContent = 'index, follow';
$canonicalUrl = DIRECTORY_PUBLIC_COMIC_URL . '/' . $currentComicId . '.php'; // $dateiendungPHP

// === 8. HEADER EINBINDEN ===
$isComicPage = true;
require_once Path::getPartialTemplatePath('header.php');
?>

<article class="comic">
    <header class="comic-header">
        <h1><?php echo htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman; ?>:
            <?php echo htmlspecialchars($comicName); ?>
        </h1>
    </header>

    <div class='comicnav'> <?php // Obere Navigation ?>
        <?php
        $comicKeys = array_keys($comicData);
        $latestComicId = !empty($comicKeys) ? end($comicKeys) : '';
        $isCurrentPageLatest = ($currentComicId === $latestComicId);
        include DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . 'navigation_comic.php';
        ?>
        <button type="button" id="add-bookmark" class="bookmark" title="Diese Seite mit Lesezeichen versehen"
            data-id="<?php echo htmlspecialchars($currentComicId); ?>"
            data-page="<?php echo htmlspecialchars($comicName); ?>"
            data-permalink="<?php echo htmlspecialchars($canonicalUrl); ?>"
            data-thumb="<?php echo htmlspecialchars(str_starts_with($bookmarkThumbnailUrl, 'http') ? $bookmarkThumbnailUrl : DIRECTORY_PUBLIC_URL . '/' . ltrim($bookmarkThumbnailUrl, '/')); ?>">
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

    <a id="comic-image-link" <?php // Bild-Link ?>
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicHiresPath, '/')); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicImagePath, '/')); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page" fetchpriority="high"
            onerror="this.onerror=null; this.src='https://placehold.co/800x600/cccccc/333333?text=Bild+Fehler'; this.parentElement.href='#'; console.warn('Haupt-Comicbild Fehler (onerror)');">
    </a>

    <div class='comicnav bottomnav'> <?php // Untere Navigation ?>
        <?php
        include DIRECTORY_PRIVATE_PARTIAL_TEMPLATES . DIRECTORY_SEPARATOR . 'navigation_comic.php';
        ?>
        <!-- SPRACHUMSCHALTER-BUTTON -->
        <?php if (!empty($urlOriginalbildFilename)) : ?>
            <button type="button" id="toggle-language-btn" class="navarrow nav-lang-toggle" title="Sprache umschalten"
                data-german-src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicImagePath, '/')); ?>"
                data-german-href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : DIRECTORY_PUBLIC_URL . '/' . ltrim($comicHiresPath, '/')); ?>"
                data-english-filename="<?php echo htmlspecialchars($urlOriginalbildFilename); ?>"
                data-english-url-from-cache="<?php echo htmlspecialchars($urlOriginalbildFromCache ?? ''); ?>"
                data-english-sketch-url-from-cache="<?php echo htmlspecialchars($urlOriginalsketchFromCache ?? ''); ?>">
                <span class="nav-wrapper">
                    <span class="nav-text" id="lang-toggle-text">EN</span>
                </span>
            </button>
        <?php endif; ?>
    </div>

    <div class="below-nav jsdep"> <?php // Permalink & Nav-Hinweis ?>
        <div class="nav-instruction">
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K
                navigieren.</span>
        </div>
        <div class="permalink">
            <a href="#" id="copy-comic-url" title="Klicke, um den Link zu dieser Comicseite zu kopieren">URL zur
                aktuellen Seite kopieren</a>
        </div>
    </div>

    <aside class="transcript"> <?php // Transkript ?>
        <div class="transcript-header">
            <h2>Transkript</h2>

            <!-- Sketch-Button (optional) -->
            <?php if (!empty($urlOriginalsketchFromCache)) : ?>
                <a href="<?php echo htmlspecialchars($urlOriginalsketchFromCache); ?>" target="_blank"
                    rel="noopener noreferrer" class="button">Sketch ansehen</a>
            <?php endif; ?>
        </div>
        <div class="transcript-content">
            <?php echo $comicTranscript; // HTML-Tags sind hier erlaubt ?>
        </div>
    </aside>

    <?php
    // KORRIGIERT: Binde das Modul zur Anzeige der Charaktere direkt ein (wie im Original)
    // $characterIds wurde weiter oben definiert
    // Die Datei display_character.php muss existieren und $characterIds und $nonce ggf. global nutzen
    require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'display_character.php';
    ?>
</article>

<?php
// NEU: Modal einbinden
// WICHTIG: Stell sicher, dass $urlOriginalbildFilename hier verfügbar ist (ist es, oben definiert)
include_once Path::getPartialTemplatePath('report_modal.php');
?>

<?php // Original JavaScript Block für URL-Kopieren und Sprachumschaltung ?>
<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    // === GEÄNDERT V4.4.2: Alle Funktionsdefinitionen (checkUrlExists, etc.)
    // wurden nach comic.js verschoben. Dieses Skript ruft nur noch
    // die globalen Funktionen auf (window.findExistingUrl). ===

    document.addEventListener('DOMContentLoaded', function () {
        const debugJs = window.phpDebugMode || false; // Verwende die PHP Debug-Variable

        // --- URL-Kopieren Logik ---
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
                    // Fallback für execCommand
                    try {
                        const tempInput = document.createElement('textarea');
                        tempInput.style.position = 'absolute';
                        tempInput.style.left = '-9999px';
                        tempInput.value = urlToCopy;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                        this.textContent = 'Kopiert! (Fallback)';
                        setTimeout(() => { this.textContent = originalText; }, 2000);
                    } catch (copyErr) {
                        console.error('Fallback-Kopieren fehlgeschlagen: ', copyErr);
                        this.textContent = 'Fehler beim Kopieren';
                    }
                });
            });
        }

        // --- ANGEPASSTE LOGIK FÜR SPRACHUMSCHALTUNG (Nutzt jetzt globale Funktionen) ---
        const toggleBtn = document.getElementById('toggle-language-btn');
        if (toggleBtn) {
            const comicLink = document.getElementById('comic-image-link');
            const comicImage = document.getElementById('comic-image');
            const langToggleText = document.getElementById('lang-toggle-text');
            let isGerman = true;
            let englishSrc = '', englishHref = ''; // Lokaler Cache für den Button-Klick

            // Prüfen, ob die globalen Funktionen von comic.js geladen wurden
            if (typeof window.checkUrlExists !== 'function' || typeof window.runOriginalProbingLogic !== 'function') {
                console.error("Fehler: Globale Bild-Suchfunktionen (von comic.js) nicht gefunden!");
                if (langToggleText) langToggleText.textContent = "Skript-Fehler";
                return;
            }

            toggleBtn.addEventListener('click', async function (event) {
                event.preventDefault();
                if (!comicImage || !comicLink) return; // Schutz

                if (isGerman) {
                    // Wenn wir die URLs schon haben, verwende sie
                    if (englishSrc && englishHref) {
                        comicImage.src = englishSrc;
                        comicLink.href = englishHref;
                        if (langToggleText) langToggleText.textContent = 'DE';
                        isGerman = false;
                        return;
                    }

                    // URLs suchen (zuerst Cache, dann Probing)
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
                        // setEnglishImage kümmert sich um den Sketch und setzt das Bild
                        // Ruft die globale Funktion auf
                        const urls = await window.setEnglishImage(mainUrl, toggleBtn, comicImage, comicLink, langToggleText);
                        englishSrc = urls.englishSrc;
                        englishHref = urls.englishHref;
                        isGerman = false;
                    }
                    // Wenn mainUrl null ist (Fehler), hat runOriginalProbingLogic bereits die Fehlermeldung angezeigt.

                } else {
                    // Zurück zu Deutsch
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
