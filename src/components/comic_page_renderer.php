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

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
// load_comic_data.php ist dafür zuständig, die comic_var.json zu lesen und $comicData zu befüllen.
require_once __DIR__ . '/load_comic_data.php';
// Lade die Helferfunktion zum Finden des Bildpfades.
require_once __DIR__ . '/get_comic_image_path.php';
// Lade die Hilfsfunktion zum Rendern der Navigationsbuttons.
require_once __DIR__ . '/nav_link_helper.php';


// Die ID der aktuellen Comic-Seite wird aus dem Dateinamen der AUFRUFENDEN Datei extrahiert.
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$urlOriginalbildFilename = ''; // NEU: Initialisieren der Variable
$socialMediaPreviewUrl = ''; // URL für Social Media Vorschaubild.
$bookmarkThumbnailUrl = ''; // URL für Lesezeichen-Vorschaubild.

// Definiere Platzhalter-URLs
$externalPlaceholderSocialUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Vorschau+fehlt';
$externalPlaceholderThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Afehlt';


if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];
    $urlOriginalbildFilename = $comicData[$currentComicId]['url_originalbild'] ?? ''; // NEU: Variable aus JSON lesen

    // --- LOGIK FÜR SOCIAL MEDIA VORSCHAUBILD ---
    $rawSocialPreviewPath = getComicImagePath($currentComicId, './assets/comic_socialmedia/');
    // Erstelle den relativen Pfad zum Vorschaubild
    if (!empty($rawSocialPreviewPath) && file_exists(realpath(__DIR__ . '/../../' . $rawSocialPreviewPath))) {
        $socialMediaPreviewUrl = '../' . $rawSocialPreviewPath;
    } else {
        $socialMediaPreviewUrl = $externalPlaceholderSocialUrl;
    }

    // --- LOGIK FÜR LESEZEICHEN-VORSCHAUBILD ---
    $rawBookmarkThumbnailPath = getComicImagePath($currentComicId, './assets/comic_thumbnails/');
    if (!empty($rawBookmarkThumbnailPath) && file_exists(realpath(__DIR__ . '/../../' . $rawBookmarkThumbnailPath))) {
        $bookmarkThumbnailUrl = '../' . $rawBookmarkThumbnailPath;
    } else {
        $bookmarkThumbnailUrl = $externalPlaceholderThumbnailUrl;
    }

} else {
    // Fallback-Werte, falls keine Comic-Daten für die aktuelle Seite gefunden werden.
    error_log("FEHLER: Daten für Comic ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Fehler auf Seite';
    $comicName = 'Comic nicht gefunden';
    $comicTranscript = '<p>Dieser Comic konnte leider nicht geladen werden.</p>';
    $socialMediaPreviewUrl = $externalPlaceholderSocialUrl;
    $bookmarkThumbnailUrl = $externalPlaceholderThumbnailUrl;
    if ($debugMode)
        error_log("DEBUG: Fallback auf externe Placeholder-URL, da Comic-Daten fehlen (Renderer): " . $socialMediaPreviewUrl . "oder " . $bookmarkThumbnailUrl);
}

// === ANFANG DER ÄNDERUNG: Dynamische Suche nach "in translation"-Bildern ===
// Definiere die Basis-Pfade (relativ zum Projekt-Root) und die Dateiendungen.
$inTranslationLowresBase = 'assets/comic_lowres/in_translation';
$inTranslationHiresBase = 'assets/comic_hires/in_translation';
$imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

// Initialisiere die Pfad-Variablen mit einem nicht existierenden Pfad.
// Dies ist ein Workaround, damit die spätere file_exists(realpath(...))-Prüfung korrekt fehlschlägt.
$inTranslationLowresRootPath = $inTranslationLowresBase . '.invalid';
$inTranslationHiresRootPath = $inTranslationHiresBase . '.invalid';

// Suche nach dem niedrigauflösenden "in translation"-Bild.
foreach ($imageExtensions as $ext) {
    $potentialPath = $inTranslationLowresBase . '.' . $ext;
    // __DIR__ ist /src/components, also gehen wir zwei Ebenen hoch zum Projekt-Root.
    if (file_exists(__DIR__ . '/../../' . $potentialPath)) {
        $inTranslationLowresRootPath = $potentialPath;
        break; // Sobald ein Bild gefunden wurde, wird die Schleife beendet.
    }
}

// Suche nach dem hochauflösenden "in translation"-Bild.
foreach ($imageExtensions as $ext) {
    $potentialPath = $inTranslationHiresBase . '.' . $ext;
    if (file_exists(__DIR__ . '/../../' . $potentialPath)) {
        $inTranslationHiresRootPath = $potentialPath;
        break; // Sobald ein Bild gefunden wurde, wird die Schleife beendet.
    }
}
// === ENDE DER ÄNDERung ===

// Ermittle die Pfade zu den Comic-Bildern mit der Helferfunktion.
// Die Helferfunktion getComicImagePath gibt Pfade relativ zum Projekt-Root zurück (z.B. 'assets/comic_lowres/20250604.png').
$rawComicLowresRootPath = getComicImagePath($currentComicId, './assets/comic_lowres/');
$rawComicHiresRootPath = getComicImagePath($currentComicId, './assets/comic_hires/');

// Initialisiere die finalen Pfade, die im HTML verwendet werden.
$comicImagePath = '';
$comicHiresPath = '';

// Prüfe, ob die tatsächlichen Comic-Bilder existieren (Pfade sind relativ zum Projekt-Root).
// Da die Comic-Seiten in einem Unterordner (comic/) liegen, müssen wir "../" voranstellen,
// um vom aktuellen Standort (comic/YYYYMMDD.php) zum Projekt-Root zu gelangen und dann den Pfad zu assets/ zu finden.
if (!empty($rawComicLowresRootPath) && file_exists(realpath(__DIR__ . '/../../' . $rawComicLowresRootPath))) {
    // Wenn Original-Comic existiert, nutze dessen Pfad (relativ zur aktuellen Comic-Seite).
    $comicImagePath = '../' . $rawComicLowresRootPath;
    $comicHiresPath = '../' . $rawComicHiresRootPath;
    if ($debugMode)
        error_log("DEBUG: Original Comic Bild gefunden (Renderer): " . realpath(__DIR__ . '/../../' . $rawComicLowresRootPath));
} else {
    // Wenn Original-Comic nicht existiert, versuche "in translation" Bild.
    if ($debugMode)
        error_log("DEBUG: Original Comic Bild nicht gefunden oder Pfad leer (Renderer). Versuche In Translation.");
    // Prüfe, ob der "in translation" Fallback existiert. Pfad ist relativ zum Projekt-Root für file_exists.
    if (file_exists(realpath(__DIR__ . '/../../' . $inTranslationLowresRootPath))) {
        // Wenn "in translation" existiert, nutze dessen Pfad (relativ zur aktuellen Comic-Seite).
        $comicImagePath = '../' . $inTranslationLowresRootPath;
        $comicHiresPath = '../' . $inTranslationHiresRootPath;
        if ($debugMode)
            error_log("DEBUG: In Translation Bild gefunden (Renderer): " . realpath(__DIR__ . '/../../' . $inTranslationLowresRootPath));
    } else {
        // Wenn auch "in translation" nicht existiert, nutze generischen Placeholder-URL.
        if ($debugMode)
            error_log("FEHLER: 'in_translation' Bild nicht gefunden unter dem erwarteten Pfad (Renderer): " . realpath(__DIR__ . '/../../' . $inTranslationLowresRootPath));

        $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
        if ($debugMode)
            error_log("DEBUG: Fallback auf allgemeine Placeholder-URL für Hauptcomicbild (Renderer): " . $comicImagePath);
    }
}
if ($debugMode)
    error_log("DEBUG: Finaler \$comicImagePath, der im HTML verwendet wird (Renderer): " . $comicImagePath);


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

// Funktion zur Erstellung einer absoluten URL aus einer relativen oder bereits absoluten URL
function makeAbsoluteUrl($url, $baseUrl)
{
    if (str_starts_with($url, 'http')) {
        return $url;
    }
    $tempUrl = $url;
    if (str_starts_with($tempUrl, '../')) {
        $tempUrl = substr($tempUrl, 3);
    } elseif (str_starts_with($tempUrl, './')) {
        $tempUrl = substr($tempUrl, 2);
    }
    return $baseUrl . $tempUrl;
}

// Absolute URLs für Social Media und Lesezeichen erstellen
$absoluteSocialPreviewUrl = makeAbsoluteUrl($socialMediaPreviewUrl, $baseUrl);
$absoluteBookmarkThumbnailUrl = makeAbsoluteUrl($bookmarkThumbnailUrl, $baseUrl);

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

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version. -->
    <a id="comic-image-link" href="<?php echo htmlspecialchars($comicHiresPath); ?>" target="_blank"
        rel="noopener noreferrer">
        <img id="comic-image" src="<?php echo htmlspecialchars($comicImagePath); ?>"
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
                    data-german-src="<?php echo htmlspecialchars($comicImagePath); ?>"
                    data-german-href="<?php echo htmlspecialchars($comicHiresPath); ?>"
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