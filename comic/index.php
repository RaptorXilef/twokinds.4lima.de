<?php
/**
 * Dies ist die Startseite des Comic-Bereichs.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an.
 * Der Seitentitel und Open Graph Metadaten werden spezifisch für die Startseite gesetzt.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
require_once __DIR__ . '/../src/components/load_comic_data.php';
// Lade die Helferfunktion zum Finden des Bildpfades.
// Diese muss hier eingebunden werden, da sie vor dem Header benötigt wird.
require_once __DIR__ . '/../src/components/get_comic_image_path.php';

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
    if (!empty($rawComicPreviewPath) && file_exists(realpath(__DIR__ . '/../' . $rawComicPreviewPath))) {
        $comicPreviewUrl = '../' . $rawComicPreviewPath;
        if ($debugMode)
            error_log("DEBUG: Comic Preview Bild gefunden (Index): " . realpath(__DIR__ . '/' . $comicPreviewUrl));
    } else {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
        if ($debugMode)
            error_log("DEBUG: Fallback auf Placeholder-URL für Comic Preview (Index): " . $comicPreviewUrl);
    }
} else {
    // Fallback-Werte, falls keine Comic-Daten oder der neueste Comic nicht gefunden wird.
    error_log("Fehler: Daten für den neuesten Comic (ID '{$currentComicId}') nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite'; // Angepasst, da "vom" nun im H1 hinzugefügt wird
    $comicName = 'Willkommen';
    $comicTranscript = '<p>Willkommen auf TwoKinds auf Deutsch! Leider konnte der neueste Comic nicht geladen werden.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Fehler';
}

// Define paths for fallback "in translation" images relative to the current file (e.g., comic/index.php)
$inTranslationLowres = '../assets/comic_lowres/in_translation.png';
$inTranslationHires = '../assets/comic_hires/in_translation.jpg';

// Get paths from the helper function (these are relative to the project root, e.g., 'assets/comic_lowres/20250604.png')
$rawComicLowresPath = getComicImagePath($currentComicId, './assets/comic_lowres/');
$rawComicHiresPath = getComicImagePath($currentComicId, './assets/comic_hires/');

// Initialize the final paths that will be used in HTML
$comicImagePath = '';
$comicHiresPath = '';

// Check if the actual comic image exists on disk
if (!empty($rawComicLowresPath) && file_exists(realpath(__DIR__ . '/../' . $rawComicLowresPath))) {
    // If original comic exists, use its path (relative to current file)
    $comicImagePath = '../' . $rawComicLowresPath;
    $comicHiresPath = '../' . $rawComicHiresPath;
    if ($debugMode)
        error_log("DEBUG: Original Comic Bild gefunden (Index): " . realpath(__DIR__ . '/' . $comicImagePath));
} else {
    // If original comic does not exist, try "in translation" image
    if ($debugMode)
        error_log("DEBUG: Original Comic Bild nicht gefunden oder Pfad leer (Index). Versuche In Translation.");
    // Check if the "in translation" fallback exists
    if (file_exists(realpath(__DIR__ . '/' . $inTranslationLowres))) {
        $comicImagePath = $inTranslationLowres;
        $comicHiresPath = $inTranslationHires;
        if ($debugMode)
            error_log("DEBUG: In Translation Bild gefunden (Index): " . realpath(__DIR__ . '/' . $comicImagePath));
    } else {
        // If "in translation" also doesn't exist, use generic placeholder URL
        error_log("FEHLER: 'in_translation' Bild nicht gefunden unter dem erwarteten Pfad (Index): " . realpath(__DIR__ . '/' . $inTranslationLowres));
        $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
        if ($debugMode)
            error_log("DEBUG: Fallback auf allgemeine Placeholder-URL für Hauptcomicbild (Index): " . $comicImagePath);
    }
}
if ($debugMode)
    error_log("DEBUG: Finaler \$comicImagePath, der im HTML verwendet wird (Index): " . $comicImagePath);


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
// Korrektur für ltrim(): Verwende substr und str_starts_with zum Entfernen des Präfixes.
$tempPreviewUrl = $comicPreviewUrl;
if (str_starts_with($tempPreviewUrl, '../')) {
    $tempPreviewUrl = substr($tempPreviewUrl, 3); // Entferne die ersten 3 Zeichen ('../')
}
$absoluteComicPreviewUrl = $baseUrl . htmlspecialchars($tempPreviewUrl);

if ($debugMode)
    error_log("DEBUG: Finaler \$absoluteComicPreviewUrl für Open Graph (Index): " . $absoluteComicPreviewUrl);


$additionalHeadContent = '
    <link rel="canonical" href="' . $baseUrl . 'comic/">
    <meta property="og:title" content="TwoKinds auf Deutsch - Startseite (Comic)">
    <meta property="og:description" content="Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.">
    <meta property="og:image" content="' . $absoluteComicPreviewUrl . '">
    <meta property="og:type" content="website">
    <meta property="og:url" content="' . $baseUrl . 'comic/">
';
// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099'; // Konsistent mit Comic-Seiten für das Design.

// Binde den gemeinsamen Header ein.
$robotsContent = 'noindex, follow';
include __DIR__ . '/../src/layout/header.php';
?>

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
        include __DIR__ . '/../src/layout/comic_navigation.php';
        unset($isCurrentPageLatest); // Variable wieder zurücksetzen, um andere Seiten nicht zu beeinflussen
        ?>
    </div>

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version. -->
    <a href="<?php echo htmlspecialchars($comicHiresPath); ?>">
        <img src="<?php echo htmlspecialchars($comicImagePath); ?>" title="<?php echo htmlspecialchars($comicName); ?>"
            alt="Comic Page">
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
            <!-- Hinweis zur Navigation mit Tastaturpfeilen auf Deutsch. -->
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K
                navigieren.</span>
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
include __DIR__ . '/../src/layout/footer.php';
?>