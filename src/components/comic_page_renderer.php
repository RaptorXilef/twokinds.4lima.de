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

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
// load_comic_data.php ist dafür zuständig, die comic_var.json zu lesen und $comicData zu befüllen.
require_once __DIR__ . '/load_comic_data.php';
// Lade die Helferfunktion zum Finden des Bildpfades.
require_once __DIR__ . '/get_comic_image_path.php';
// Lade die Hilfsfunktion zum Rendern der Navigationsbuttons.
require_once __DIR__ . '/nav_link_helper.php';


// Die ID der aktuellen Comic-Seite wird aus dem Dateinamen der AUFRUFENDEN Datei extrahiert.
// $_SERVER['SCRIPT_FILENAME'] gibt den vollständigen Pfad zur aufgerufenen PHP-Datei zurück.
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$comicPreviewUrl = ''; // URL für das Vorschaubild, z.B. für Social Media Meta-Tags.

if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];

    // Die Preview-URL wird nun lokal aus dem 'comic_socialmedia'-Ordner geladen (relativ zum Projekt-Root).
    $rawComicPreviewRootPath = getComicImagePath($currentComicId, './assets/comic_socialmedia/');

    // Pfad für die Vorschau-URL (relativ zur aktuellen Comic-Seite, d.h., comic/YYYYMMDD.php)
    // realpath(__DIR__ . '/../../' . $rawComicPreviewRootPath) konstruiert den absoluten Pfad zur Datei.
    if (!empty($rawComicPreviewRootPath) && file_exists(realpath(__DIR__ . '/../../' . $rawComicPreviewRootPath))) {
        // Pfad von src/components/ zu assets/
        $comicPreviewUrl = '../' . $rawComicPreviewRootPath; // Pfad relativ zum Comic-Ordner
        error_log("DEBUG: Comic Preview Bild gefunden (Renderer): " . realpath(__DIR__ . '/../../' . $rawComicPreviewRootPath));
    } else {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
        error_log("DEBUG: Fallback auf Placeholder-URL für Comic Preview (Renderer): " . $comicPreviewUrl);
    }
} else {
    // Fallback-Werte, falls keine Comic-Daten für die aktuelle Seite gefunden werden.
    error_log("FEHLER: Daten für Comic ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Fehler auf Seite'; // Angepasst, da "vom" nun im H1 hinzugefügt wird
    $comicName = 'Comic nicht gefunden';
    $comicTranscript = '<p>Dieser Comic konnte leider nicht geladen werden.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Fehler';
}

// Definiere die Pfade zu den Lückenfüller-Bildern (relativ zum Projekt-Root).
// Diese Pfade werden später für file_exists und für die HTML-Ausgabe angepasst.
$inTranslationLowresRootPath = 'assets/comic_lowres/in_translation.png';
$inTranslationHiresRootPath = 'assets/comic_hires/in_translation.jpg';

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
    error_log("DEBUG: Original Comic Bild gefunden (Renderer): " . realpath(__DIR__ . '/../../' . $rawComicLowresRootPath));
} else {
    // Wenn Original-Comic nicht existiert, versuche "in translation" Bild.
    error_log("DEBUG: Original Comic Bild nicht gefunden oder Pfad leer (Renderer). Versuche In Translation.");
    // Prüfe, ob der "in translation" Fallback existiert. Pfad ist relativ zum Projekt-Root für file_exists.
    if (file_exists(realpath(__DIR__ . '/../../' . $inTranslationLowresRootPath))) {
        // Wenn "in translation" existiert, nutze dessen Pfad (relativ zur aktuellen Comic-Seite).
        $comicImagePath = '../' . $inTranslationLowresRootPath;
        $comicHiresPath = '../' . $inTranslationHiresRootPath;
        error_log("DEBUG: In Translation Bild gefunden (Renderer): " . realpath(__DIR__ . '/../../' . $inTranslationLowresRootPath));
    } else {
        // Wenn auch "in translation" nicht existiert, nutze generischen Placeholder-URL.
        error_log("FEHLER: 'in_translation' Bild nicht gefunden unter dem erwarteten Pfad (Renderer): " . realpath(__DIR__ . '/../../' . $inTranslationLowresRootPath));
        $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
        error_log("DEBUG: Fallback auf allgemeine Placeholder-URL für Hauptcomicbild (Renderer): " . $comicImagePath);
    }
}
error_log("DEBUG: Finaler \$comicImagePath, der im HTML verwendet wird (Renderer): " . $comicImagePath);


// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ.
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));
// Konvertiere die Comic-ID (Datum) ins englische Format für den Original-H1-Header-Stil "Month Day, Year"
$formattedDateEnglish = date('F d, Y', strtotime($currentComicId));

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
    error_log("DEBUG: Lokale Basis-URL (Renderer): " . $baseUrl);
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
    error_log("DEBUG: Live Basis-URL (Renderer): " . $baseUrl);
}

// Setze Parameter für den Header.
// Seitentitel für den Browser-Tab: JJJJ.MM.DD
$pageTitle = 'Comic ' . substr($currentComicId, 0, 4) . '.' . substr($currentComicId, 4, 2) . '.' . substr($currentComicId, 6, 2) . ': ' . $comicName;
// H1-Header auf der Seite: TT.MM.JJJJ
// Hinzufügen von " vom " zwischen Comic-Typ und Datum
$pageHeader = htmlspecialchars($comicTyp) . ' vom ' . $formattedDateGerman . ': ' . htmlspecialchars($comicName);
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
// Die og:image URL muss absolut sein.
// $comicPreviewUrl ist bereits relativ zur aktuellen Comic-Seite (z.B. ../assets/comic_socialmedia/...)
// Wir müssen den Teil vor 'assets/' entfernen und mit $baseUrl konkatenieren.
$absoluteComicPreviewUrl = $baseUrl . ltrim($comicPreviewUrl, './'); // Entfernt './' oder '../' vom Anfang des Pfades
error_log("DEBUG: Finaler \$absoluteComicPreviewUrl für Open Graph (Renderer): " . $absoluteComicPreviewUrl);

$additionalHeadContent = '
    <link rel="canonical" href="' . $baseUrl . 'comic/' . htmlspecialchars($currentComicId) . '.php">
    <meta property="og:title" content="TwoKinds auf Deutsch - Comic ' . htmlspecialchars($formattedDateGerman) . ': ' . htmlspecialchars($comicName) . '">
    <meta property="og:description" content="' . htmlspecialchars($siteDescription) . '">
    <meta property="og:image" content="' . htmlspecialchars($absoluteComicPreviewUrl) . '">
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

<article class="comic">
    <header>
        <!-- H1-Tag im Format des Originals, Datum und Titel werden aus der JSON geladen. -->
        <h1><?php echo $pageHeader; ?></h1>
    </header>

    <div class='comicnav'>
        <?php
        // Binde die obere Comic-Navigation ein.
        // Pfad von src/components/ zu src/layout/
        include __DIR__ . '/../layout/comic_navigation.php';
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
        // Pfad von src/components/ zu src/layout/
        include __DIR__ . '/../layout/comic_navigation.php';
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
// Pfad von src/components/ zu src/layout/
include __DIR__ . '/../layout/footer.php';
?>
