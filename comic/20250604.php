<?php
/**
 * Dies ist eine Comicseite. Sie lädt Comic-Metadaten und zeigt das Comic-Bild,
 * Navigation und Transkript an. Das Design ist an das Original von Tom Fischbach angepasst.
 * Die Bookmark-Funktion und externe Analytics-Dienste wurden entfernt.
 * Datum und Comic-Titel werden dynamisch aus der JSON im deutschen Format geladen.
 * Der Seitentitel im Browser-Tab ist für eine bessere Sortierbarkeit formatiert.
 * Die Bildpfade unterstützen nun verschiedene Dateiformate (.jpg, .png, .gif).
 * Es wird ein Lückenfüller-Bild angezeigt, falls das Original nicht existiert.
 */

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
require_once __DIR__ . '/../src/components/load_comic_data.php';
// Lade die Helferfunktion zum Finden des Bildpfades.
require_once __DIR__ . '/../src/components/get_comic_image_path.php';


// Die ID der aktuellen Comic-Seite wird aus dem Dateinamen extrahiert.
$currentComicId = basename(__FILE__, '.php');

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
    $rawComicPreviewPath = getComicImagePath($currentComicId, './assets/comic_socialmedia/');

    // Pfad für die Vorschau-URL (relativ zur aktuellen Datei, d.h., comic/20250604.php)
    // Wir müssen einen Schritt zurückgehen, um aus 'comic/' in den Root-Ordner zu gelangen,
    // um dann auf 'assets/comic_socialmedia/' zuzugreifen.
    if (!empty($rawComicPreviewPath) && file_exists(realpath(__DIR__ . '/../' . $rawComicPreviewPath))) {
        $comicPreviewUrl = '../' . $rawComicPreviewPath;
        error_log("DEBUG: Comic Preview Bild gefunden (Comic-Seite): " . realpath(__DIR__ . '/../' . $comicPreviewUrl));
    } else {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
        error_log("DEBUG: Fallback auf Placeholder-URL für Comic Preview (Comic-Seite): " . $comicPreviewUrl);
    }
} else {
    // Fallback-Werte, falls keine Comic-Daten für die aktuelle Seite gefunden werden.
    error_log("Fehler: Daten für Comic ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Fehler auf Seite vom ';
    $comicName = 'Comic nicht gefunden';
    $comicTranscript = '<p>Dieser Comic konnte leider nicht geladen werden.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Fehler';
}

// Definiere die Pfade zu den Lückenfüller-Bildern (relativ zur aktuellen Comic-Seite).
$inTranslationLowres = '../assets/comic_lowres/in_translation.png';
$inTranslationHires = '../assets/comic_hires/in_translation.jpg';

// Ermittle die Pfade zu den Comic-Bildern mit der Helferfunktion.
// Die Helferfunktion gibt Pfade relativ zum Projekt-Root zurück (z.B. 'assets/comic_lowres/20250604.png').
$rawComicLowresPath = getComicImagePath($currentComicId, './assets/comic_lowres/');
$rawComicHiresPath = getComicImagePath($currentComicId, './assets/comic_hires/');

// Initialisiere die finalen Pfade, die im HTML verwendet werden.
$comicImagePath = '';
$comicHiresPath = '';

// Prüfe, ob die tatsächlichen Comic-Bilder existieren (Pfade sind relativ zum Projekt-Root).
// Da die Comic-Seiten in einem Unterordner (comic/) liegen, müssen wir "../" voranstellen.
if (!empty($rawComicLowresPath) && file_exists(realpath(__DIR__ . '/../' . $rawComicLowresPath))) {
    // Wenn Original-Comic existiert, nutze dessen Pfad (relativ zur aktuellen Comic-Seite).
    $comicImagePath = '../' . $rawComicLowresPath;
    $comicHiresPath = '../' . $rawComicHiresPath;
    error_log("DEBUG: Original Comic Bild gefunden (Comic-Seite): " . realpath(__DIR__ . '/../' . $comicImagePath));
} else {
    // Wenn Original-Comic nicht existiert, versuche "in translation" Bild.
    error_log("DEBUG: Original Comic Bild nicht gefunden oder Pfad leer (Comic-Seite). Versuche In Translation.");
    // Prüfe, ob der "in translation" Fallback existiert.
    if (file_exists(realpath(__DIR__ . '/' . $inTranslationLowres))) {
        $comicImagePath = $inTranslationLowres;
        $comicHiresPath = $inTranslationHires;
        error_log("DEBUG: In Translation Bild gefunden (Comic-Seite): " . realpath(__DIR__ . '/' . $comicImagePath));
    } else {
        // Wenn auch "in translation" nicht existiert, nutze generischen Placeholder-URL.
        error_log("FEHLER: 'in_translation' Bild nicht gefunden unter dem erwarteten Pfad (Comic-Seite): " . realpath(__DIR__ . '/' . $inTranslationLowres));
        $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
        error_log("DEBUG: Fallback auf allgemeine Placeholder-URL für Hauptcomicbild (Comic-Seite): " . $comicImagePath);
    }
}
error_log("DEBUG: Finaler \$comicImagePath, der im HTML verwendet wird (Comic-Seite): " . $comicImagePath);


// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ.
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));
// Konvertiere die Comic-ID (Datum) ins englische Format für den H1-Header (Original-Stil).
$formattedDateEnglish = date('F d, Y', strtotime($currentComicId));

// Die allgemeine Seitenbeschreibung, die in header.php verwendet wird.
$siteDescription = 'Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.';

// === Dynamische Basis-URL Bestimmung für Comic-Seiten ===
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
    error_log("DEBUG: Lokale Basis-URL (Comic-Seite): " . $baseUrl);
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
    error_log("DEBUG: Live Basis-URL (Comic-Seite): " . $baseUrl);
}

// Setze Parameter für den Header.
$pageTitle = 'Comic ' . $formattedDateGerman . ': ' . $comicName; // Seitentitel für den Browser-Tab
$pageHeader = 'Comic for ' . $formattedDateEnglish . ': ' . htmlspecialchars($comicName); // H1-Header auf der Seite
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
// Die og:image URL muss absolut sein. Wir müssen den Pfad anpassen, da die Comic-Seiten in einem Unterordner liegen.
// Der $comicPreviewUrl ist relativ zur aktuellen Datei (z.B. ../assets/comic_socialmedia/...)
// Wir müssen den Teil vor 'assets/' entfernen und mit $baseUrl konkatenieren.
$absoluteComicPreviewUrl = $baseUrl . ltrim($comicPreviewUrl, './'); // Entfernt './' oder '../' vom Anfang des Pfades
error_log("DEBUG: Finaler \$absoluteComicPreviewUrl für Open Graph (Comic-Seite): " . $absoluteComicPreviewUrl);

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
include __DIR__ . '/../src/layout/header.php';
?>

<article class="comic">
    <header>
        <!-- H1-Tag im Format des Originals, Datum und Titel werden aus der JSON geladen. -->
        <h1><?php echo htmlspecialchars($comicTyp) . $formattedDateEnglish; ?>: <?php echo htmlspecialchars($comicName); ?></h1>
    </header>

    <div class='comicnav'>
        <?php
        // Binde die obere Comic-Navigation ein.
        include __DIR__ . '/../src/layout/comic_navigation.php';
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
        include __DIR__ . '/../src/layout/comic_navigation.php';
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
include __DIR__ . '/../src/layout/footer.php';
?>
