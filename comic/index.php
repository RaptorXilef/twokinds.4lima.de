<?php
/**
 * Dies ist die Startseite des Comic-Bereichs.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an.
 * Der Seitentitel und Open Graph Metadaten werden spezifisch für die Startseite gesetzt.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

// === ANGEPASST: Lade zentrale Helfer anstelle der alten Skripte ===
// Lade die Comic-Daten aus der JSON-Datei.
require_once __DIR__ . '/../src/components/load_comic_data.php';
// Lade den neuen Helfer, der die Bildpfade aus dem Cache bereitstellt.
require_once __DIR__ . '/../src/components/image_cache_helper.php';

// Ermittle die ID des neuesten Comics. Da $comicData nach Datum sortiert ist,
// ist der letzte Schlüssel im Array der neueste Comic.
$comicKeys = array_keys($comicData);
$latestComicId = !empty($comicKeys) ? end($comicKeys) : '';

// Setze die aktuelle Comic-ID für diese Seite auf die ID des neuesten Comics.
$currentComicId = $latestComicId;

// Hole die Daten für den neuesten Comic.
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$urlOriginalbildFilename = ''; // NEU
$comicPreviewUrl = ''; // URL für das Vorschaubild, z.B. für Social Media Meta-Tags.

if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];
    $urlOriginalbildFilename = $comicData[$currentComicId]['url_originalbild'] ?? ''; // NEU
} else {
    // Fallback-Werte, falls keine Comic-Daten oder der neueste Comic nicht gefunden wird.
    error_log("Fehler: Daten für den neuesten Comic (ID '{$currentComicId}') nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite'; // Angepasst, da "vom" nun im H1 hinzugefügt wird
    $comicName = 'Willkommen';
    $comicTranscript = '<p>Willkommen auf TwoKinds auf Deutsch! Leider konnte der neueste Comic nicht geladen werden.</p>';
}

// === NEUE LOGIK: Bildpfade direkt aus dem Cache abrufen ===
$comicImagePath = get_cached_image_path($currentComicId, 'lowres');
$comicHiresPath = get_cached_image_path($currentComicId, 'hires');
$comicPreviewUrl = get_cached_image_path($currentComicId, 'socialmedia');

// Fallback-Logik, falls der Comic (noch) nicht im Cache ist.
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
        error_log("DEBUG: Fallback auf externen Placeholder für Hauptcomicbild (Index).");
}
// Fallback für das Social-Media-Vorschaubild.
if (empty($comicPreviewUrl)) {
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
}


// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ.
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));
// Konvertiere die Comic-ID (Datum) ins englische Format für den H1-Header (Original-Stil).
$formattedDateEnglish = date('F d, Y', strtotime($currentComicId));

// Die allgemeine Seitenbeschreibung, die in header.php verwendet wird.
$siteDescription = 'Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.';

// === Dynamische Basis-URL Bestimmung ===
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
if ($isLocal) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Beispiel: /twokinds/default-website/twokinds/comic/index.php
    // Wir wollen: /twokinds/default-website/twokinds/
    $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
    array_pop($pathParts); // Entfernt 'index.php'
    array_pop($pathParts); // Entfernt 'comic'
    $basePath = implode('/', $pathParts);
    $baseUrl = $protocol . $host . $basePath . '/';
    if ($debugMode)
        error_log("DEBUG: Lokale Basis-URL (Index): " . $baseUrl);
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
    if ($debugMode)
        error_log("DEBUG: Live Basis-URL (Index): " . $baseUrl);
}


// Setze Parameter für den Header.
// Der Seitentitel für den Browser-Tab ist spezifisch für die Startseite.
$pageTitle = 'Startseite'; // Der Präfix "TwoKinds auf Deutsch - " wird automatisch von header.php hinzugefügt.
// H1-Header für die Startseite. Er zeigt den Titel des neuesten Comics.
// Angepasst, um " vom " und das deutsche Datumsformat zu verwenden.
$pageHeader = htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman . ': ' . htmlspecialchars($comicName);
// Füge comic.js als zusätzliches Skript hinzu.
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
// Für Open Graph URLs muss der Pfad absolut sein.
$absoluteComicPreviewUrl = str_starts_with($comicPreviewUrl, 'http') ? $comicPreviewUrl : $baseUrl . $comicPreviewUrl;

if ($debugMode)
    error_log("DEBUG: Finaler \$absoluteComicPreviewUrl für Open Graph (Index): " . $absoluteComicPreviewUrl);


$additionalHeadContent = '
    <link rel="canonical" href="' . $baseUrl . 'comic/">
    <meta property="og:title" content="TwoKinds auf Deutsch - Startseite (Comic)">
    <meta property="og:description" content="Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.">
    <meta property="og:image" content="' . htmlspecialchars($absoluteComicPreviewUrl) . '">
    <meta property="og:type" content="website">
    <meta property="og:url" content="' . $baseUrl . 'comic/">
';
// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099'; // Konsistent mit Comic-Seiten für das Design.

// Binde den gemeinsamen Header ein.
$robotsContent = 'noindex, follow';
include __DIR__ . '/../src/layout/header.php';
?>

<style>
    /* Passt die Größe des Comic-Bildes an die Containerbreite an */
    #comic-image {
        width: 100%;
        height: auto;
    }
</style>

<article class="comic">
    <header>
        <h1><?php echo htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman; ?>:
            <?php echo htmlspecialchars($comicName); ?>
        </h1>
    </header>

    <div class='comicnav'>
        <?php
        // Binde die obere Comic-Navigation ein.
        // Hier wird $isCurrentPageLatest auf TRUE gesetzt, um den "Letzte Seite" Button zu deaktivieren.
        $isCurrentPageLatest = true;
        include __DIR__ . '/../src/layout/comic_navigation.php';
        unset($isCurrentPageLatest); // Variable wieder zurücksetzen, um andere Seiten nicht zu beeinflussen
        ?>
    </div>

    <a id="comic-image-link"
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : '../' . $comicHiresPath); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : '../' . $comicImagePath); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page">
    </a>

    <div class='comicnav bottomnav'>
        <?php
        // Binde die untere Comic-Navigation ein (identisch zur oberen Navigation).
        // Hier wird $isCurrentPageLatest auf TRUE gesetzt, um den "Letzte Seite" Button zu deaktivieren.
        $isCurrentPageLatest = true;
        include __DIR__ . '/../src/layout/comic_navigation.php';
        unset($isCurrentPageLatest); // Variable wieder zurücksetzen
        ?>
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

<!-- NEU: JavaScript zum Kopieren der URL für die Index-Seite -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // URL Kopieren Logik
        const copyLink = document.getElementById('copy-comic-url');
        if (copyLink) {
            copyLink.addEventListener('click', function (event) {
                event.preventDefault();

                // Die zu kopierende URL wird aus der PHP-Variable geholt, die die URL des neuesten Comics enthält.
                const urlToCopy = '<?php echo $baseUrl . 'comic/' . $latestComicId . '.php'; ?>';
                const originalText = this.textContent;
                navigator.clipboard.writeText(urlToCopy).then(() => {
                    this.textContent = 'Kopiert!';
                    setTimeout(() => { this.textContent = originalText; }, 2000);
                }).catch(err => {
                    console.error('Fehler beim Kopieren der URL: ', err);
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

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/../src/layout/footer.php';
?>