<?php
/**
 * Dies ist die Startseite der TwoKinds-Webseite.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an.
 * Der Seitentitel und Open Graph Metadaten werden spezifisch für die Startseite gesetzt.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
require_once __DIR__ . '/src/components/public_init.php';

// === 2. LADE-SKRIPTE & DATEN ===
require_once __DIR__ . '/src/components/load_comic_data.php';
// Lade den neuen Helfer, der die Bildpfade aus dem Cache bereitstellt.
require_once __DIR__ . '/src/components/image_cache_helper.php';

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
    error_log("Fehler: Daten für den neuesten Comic (ID '{$currentComicId}') nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite'; // Angepasst, da "vom" nun im H1 hinzugefügt wird
    $comicName = 'Willkommen';
    $comicTranscript = '<p>Willkommen auf TwoKinds auf Deutsch! Leider konnte der neueste Comic nicht geladen werden.</p>';
}

// === 3. BILD-PFADE & FALLBACKS ===
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
        error_log("DEBUG: Fallback auf externen Placeholder für Hauptcomicbild (Haupt-Index).");
}
// Fallback für das Social-Media-Vorschaubild.
if (empty($comicPreviewUrl)) {
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Vorschau+fehlt';
}

// Konvertiere die Comic-ID (Datum) ins deutsche Format
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Das Webcomic auf Deutsch';
$siteDescription = 'Tauche ein in die Welt von Twokinds – dem beliebten Fantasy-Webcomic von Tom Fischbach, jetzt komplett auf Deutsch verfügbar. Erlebe die spannende Geschichte von Trace und Flora und entdecke die Rassenkonflikte zwischen Menschen und Keidran.';
$ogImage = str_starts_with($comicPreviewUrl, 'http') ? $comicPreviewUrl : $baseUrl . $comicPreviewUrl;
$additionalScripts = "<script nonce=\"" . htmlspecialchars($nonce) . "\" type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";
$viewportContent = 'width=1099'; // Konsistent mit Comic-Seiten für das Design.

// === 5. HEADER EINBINDEN ===
require_once __DIR__ . '/src/layout/header.php';
?>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    /* Passt die Größe des Comic-Bildes an die Containerbreite an */
    #comic-image {
        width: 100%;
        height: auto;
    }

    /* Ersetzt den Inline-Stil für den Transcript-Header (CSP-Konformität) */
    .transcript-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
</style>

<article class="comic">
    <header>
        <!-- H1-Tag im Format des Originals, zeigt den Titel des neuesten Comics. -->
        <h1><?php echo htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman; ?>:
            <?php echo htmlspecialchars($comicName); ?>
        </h1>
    </header>

    <div class='comicnav'>
        <?php
        // Binde die obere Comic-Navigation ein.
        // Hier wird $isCurrentPageLatest auf TRUE gesetzt, um den "Letzte Seite" Button zu deaktivieren.
        $isCurrentPageLatest = true;
        include __DIR__ . '/src/layout/comic_navigation.php';
        unset($isCurrentPageLatest); // Variable wieder zurücksetzen, um andere Seiten nicht zu beeinflussen
        ?>
    </div>

    <a id="comic-image-link"
        href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : './' . $comicHiresPath); ?>"
        target="_blank" rel="noopener noreferrer">
        <img id="comic-image"
            src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : './' . $comicImagePath); ?>"
            title="<?php echo htmlspecialchars($comicName); ?>" alt="Comic Page">
    </a>

    <div class='comicnav bottomnav'>
        <?php
        // Binde die untere Comic-Navigation ein (identisch zur oberen Navigation).
        // Hier wird $isCurrentPageLatest auf TRUE gesetzt, um den "Letzte Seite" Button zu deaktivieren.
        $isCurrentPageLatest = true;
        include __DIR__ . '/src/layout/comic_navigation.php';
        unset($isCurrentPageLatest); // Variable wieder zurücksetzen
        ?>
    </div>

    <div class="below-nav jsdep">
        <div class="nav-instruction">
            <!-- Hinweis zur Navigation mit Tastaturpfeilen auf Deutsch. -->
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K
                navigieren.</span>
        </div>
        <!-- Link zum Kopieren der URL mit spezieller Logik für die Index-Seite -->
        <div class="permalink">
            <a href="#" id="copy-comic-url" title="Klicke, um den Link zu dieser Comicseite zu kopieren">URL zur
                aktuellen Seite kopieren</a>
        </div>
    </div>

    <aside class="transcript">
        <div class="transcript-header">
            <h2>Transkript</h2>
            <?php if (!empty($urlOriginalbildFilename)): ?>
                <a href="#" class="button" id="toggle-language-btn"
                    data-german-src="<?php echo htmlspecialchars(str_starts_with($comicImagePath, 'http') ? $comicImagePath : './' . $comicImagePath); ?>"
                    data-german-href="<?php echo htmlspecialchars(str_starts_with($comicHiresPath, 'http') ? $comicHiresPath : './' . $comicHiresPath); ?>"
                    data-english-filename="<?php echo htmlspecialchars($urlOriginalbildFilename); ?>">Seite auf englisch
                    anzeigen</a>
            <?php endif; ?>
        </div>
        <div class="transcript-content">
            <?php echo $comicTranscript; ?>
        </div>
    </aside>
</article>

<!-- JavaScript zum Kopieren der URL für die Index-Seite -->
<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // URL Kopieren Logik
        const copyLink = document.getElementById('copy-comic-url');
        if (copyLink) {
            copyLink.addEventListener('click', function (event) {
                event.preventDefault();
                // Die zu kopierende URL wird aus der PHP-Variable geholt, die die URL des neuesten Comics enthält.
                const urlToCopy = '<?php echo $baseUrl . 'comic/' . $latestComicId; ?>';
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
            const sketchImageUrlBase = 'https://twokindscomic.com/images/';
            const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

            // Findet das normale englische Bild
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

            // Findet das englische Sketch/Hi-Res Bild
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

                            // Versuche das Sketch-Bild zu laden, mit Fallback auf das Hauptbild
                            sketchImagePromise.then(sketchUrl => {
                                englishHref = sketchUrl;
                                comicLink.href = englishHref;
                            }).catch(sketchError => {
                                console.warn(sketchError); // Logge den Fehler, aber mache weiter
                                englishHref = mainUrl; // Fallback
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

<?php
// Binde den gemeinsamen Footer ein.
require_once __DIR__ . '/src/layout/footer.php';
?>