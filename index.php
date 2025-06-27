<?php
/**
 * Dies ist die Startseite der TwoKinds-Webseite.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an.
 * Der Seitentitel und Open Graph Metadaten werden spezifisch für die Startseite gesetzt.
 */

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
require_once __DIR__ . '/src/components/load_comic_data.php';
// Lade die Helferfunktion zum Finden des Bildpfades.
// Diese muss hier eingebunden werden, da sie vor dem Header benötigt wird.
require_once __DIR__ . '/src/components/get_comic_image_path.php';

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
$comicPreviewUrl = ''; // URL für das Vorschaubild, z.B. für Social Media Meta-Tags.

if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];
    // Die Preview-URL wird nun lokal aus dem 'comic_socialmedia'-Ordner geladen (relativ zum Projekt-Root).
    $rawComicPreviewPath = getComicImagePath($currentComicId, './assets/comic_socialmedia/');

    // Pfad für die Vorschau-URL (relativ zur aktuellen Datei)
    if (!empty($rawComicPreviewPath) && file_exists(realpath(__DIR__ . '/' . $rawComicPreviewPath))) {
        $comicPreviewUrl = './' . $rawComicPreviewPath;
        error_log("DEBUG: Comic Preview Bild gefunden (Haupt-Index): " . realpath(__DIR__ . '/' . $comicPreviewUrl));
    } else {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
        error_log("DEBUG: Fallback auf Placeholder-URL für Comic Preview (Haupt-Index): " . $comicPreviewUrl);
    }
} else {
    // Fallback-Werte, falls keine Comic-Daten oder der neueste Comic nicht gefunden wird.
    error_log("Fehler: Daten für den neuesten Comic (ID '{$currentComicId}') nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite vom ';
    $comicName = 'Willkommen';
    $comicTranscript = '<p>Willkommen auf TwoKinds auf Deutsch! Leider konnte der neueste Comic nicht geladen werden.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Fehler';
}

// Define paths for fallback "in translation" images relative to the current file (e.g., index.php in root)
$inTranslationLowres = './assets/comic_lowres/in_translation.png';
$inTranslationHires = './assets/comic_hires/in_translation.jpg';

// Get paths from the helper function (these are relative to the project root, e.g., 'assets/comic_lowres/20250604.png')
$rawComicLowresPath = getComicImagePath($currentComicId, './assets/comic_lowres/');
$rawComicHiresPath = getComicImagePath($currentComicId, './assets/comic_hires/');

// Initialize the final paths that will be used in HTML
$comicImagePath = '';
$comicHiresPath = '';

// Check if the actual comic image exists on disk
if (!empty($rawComicLowresPath) && file_exists(realpath(__DIR__ . '/' . $rawComicLowresPath))) {
    // If original comic exists, use its path (relative to current file)
    $comicImagePath = './' . $rawComicLowresPath;
    $comicHiresPath = './' . $rawComicHiresPath;
    error_log("DEBUG: Original Comic Bild gefunden (Haupt-Index): " . realpath(__DIR__ . '/' . $comicImagePath));
} else {
    // If original comic does not exist, try "in translation" image
    error_log("DEBUG: Original Comic Bild nicht gefunden oder Pfad leer (Haupt-Index). Versuche In Translation.");
    // Check if the "in translation" fallback exists
    if (file_exists(realpath(__DIR__ . '/' . $inTranslationLowres))) {
        $comicImagePath = $inTranslationLowres;
        $comicHiresPath = $inTranslationHires;
        error_log("DEBUG: In Translation Bild gefunden (Haupt-Index): " . realpath(__DIR__ . '/' . $comicImagePath));
    } else {
        // If "in translation" also doesn't exist, use generic placeholder URL
        error_log("FEHLER: 'in_translation' Bild nicht gefunden unter dem erwarteten Pfad (Haupt-Index): " . realpath(__DIR__ . '/' . $inTranslationLowres));
        $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
        error_log("DEBUG: Fallback auf allgemeine Placeholder-URL für Hauptcomicbild (Haupt-Index): " . $comicImagePath);
    }
}
error_log("DEBUG: Finaler \$comicImagePath, der im HTML verwendet wird (Haupt-Index): " . $comicImagePath);


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
    // Beispiel: /twokinds/default-website/twokinds/index.php
    // Wir wollen: /twokinds/default-website/twokinds/
    $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
    array_pop($pathParts); // Entfernt 'index.php'
    $basePath = implode('/', $pathParts);
    $baseUrl = $protocol . $host . $basePath . '/';
    error_log("DEBUG: Lokale Basis-URL (Haupt-Index): " . $baseUrl);
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
    error_log("DEBUG: Live Basis-URL (Haupt-Index): " . $baseUrl);
}

// Setze Parameter für den Header.
// Der Seitentitel für den Browser-Tab ist spezifisch für die Startseite.
$pageTitle = 'Startseite'; // Der Präfix "TwoKinds auf Deutsch - " wird automatisch von header.php hinzugefügt.
// H1-Header für die Startseite. Er zeigt den Titel des neuesten Comics.
$pageHeader = 'Comic for ' . $formattedDateEnglish . ': ' . htmlspecialchars($comicName);
// Füge comic.js als zusätzliches Skript hinzu.
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
// Für Open Graph URLs muss der Pfad absolut sein.
// Hier ist keine ltrim-Anpassung notwendig, da die Pfade in dieser index.php bereits relativ zum Root sind.
$absoluteComicPreviewUrl = $baseUrl . htmlspecialchars($comicPreviewUrl);

error_log("DEBUG: Finaler \$absoluteComicPreviewUrl für Open Graph (Haupt-Index): " . $absoluteComicPreviewUrl);


$additionalHeadContent = '
    <link rel="canonical" href="' . $baseUrl . '">
    <meta property="og:title" content="TwoKinds auf Deutsch - Startseite (Comic)">
    <meta property="og:description" content="Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.">
    <meta property="og:image" content="' . $absoluteComicPreviewUrl . '">
    <meta property="og:type" content="website">
    <meta property="og:url" content="' . $baseUrl . '">
';
// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099'; // Konsistent mit Comic-Seiten für das Design.

// Binde den gemeinsamen Header ein.
include __DIR__ . '/src/layout/header.php';
?>

<article class="comic">
    <header>
        <!-- H1-Tag im Format des Originals, zeigt den Titel des neuesten Comics. -->
        <h1><?php echo htmlspecialchars($comicTyp) . $formattedDateEnglish; ?>: <?php echo htmlspecialchars($comicName); ?></h1>
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

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version. -->
    <a href="<?php echo htmlspecialchars($comicHiresPath); ?>">
        <img src="<?php echo htmlspecialchars($comicImagePath); ?>"
             title="<?php echo htmlspecialchars($comicName); ?>"
             alt="Comic Page"
        >
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
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K navigieren.</span>
        </div>
    </div>

    <aside class="transcript">
        <h2>Transkript</h2>
        <div class="transcript-content">
            <?php echo $comicTranscript; ?>
        </div>
    </aside>
</article>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/src/layout/footer.php';
?>
