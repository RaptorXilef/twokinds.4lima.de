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
// $_SERVER['SCRIPT_FILENAME'] gibt den vollständigen Pfad zur aufgerufenen PHP-Datei zurück.
$currentComicId = basename($_SERVER['SCRIPT_FILENAME'], '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';
$comicPreviewUrl = ''; // URL für das Vorschaubild, z.B. für Social Media Meta-Tags.

// Definiere die externe Platzhalter-URL für Thumbnails
$externalPlaceholderThumbnailUrl = 'https://placehold.co/96x96/cccccc/333333?text=Vorschau%0Aaktuell%0Anicht%0Averf%C3%BCgbar';


if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];

    // Die Preview-URL wird nun lokal aus dem 'comic_thumbnails'-Ordner geladen (relativ zum Projekt-Root).
    // Korrigierter Pfad zu 'assets/comic_thumbnails/' und ohne '_preview' Suffix.
    $rawComicThumbnailRootPath = getComicImagePath($currentComicId, './assets/comic_thumbnails/');

    // Pfad für die Vorschaubild-URL (relativ zur aktuellen Comic-Seite, d.h., comic/YYYYMMDD.php)
    // realpath(__DIR__ . '/../../' . $rawComicThumbnailRootPath) konstruiert den absoluten Pfad zur Datei.
    if (!empty($rawComicThumbnailRootPath) && file_exists(realpath(__DIR__ . '/../../' . $rawComicThumbnailRootPath))) {
        // Wenn Original-Comic existiert, nutze dessen Pfad (relativ zur aktuellen Comic-Seite).
        $comicPreviewUrl = '../' . $rawComicThumbnailRootPath; // Pfad relativ zum Comic-Ordner
        if ($debugMode)
            error_log("DEBUG: Comic Thumbnail Bild gefunden (Renderer): " . realpath(__DIR__ . '/../../' . $rawComicThumbnailRootPath));
    } else {
        $comicPreviewUrl = $externalPlaceholderThumbnailUrl; // Neue, externe Platzhalter-URL
        if ($debugMode)
            error_log("DEBUG: Fallback auf externe Placeholder-URL für Comic Thumbnail (Renderer): " . $comicPreviewUrl);
    }
} else {
    // Fallback-Werte, falls keine Comic-Daten für die aktuelle Seite gefunden werden.
    error_log("FEHLER: Daten für Comic ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Fehler auf Seite'; // Angepasst, da "vom" nun im H1 hinzugefügt wird
    $comicName = 'Comic nicht gefunden';
    $comicTranscript = '<p>Dieser Comic konnte leider nicht geladen werden.</p>';
    $comicPreviewUrl = $externalPlaceholderThumbnailUrl; // Immer die externe Platzhalter-URL verwenden
    if ($debugMode)
        error_log("DEBUG: Fallback auf externe Placeholder-URL für Comic Thumbnail (Renderer): " . $comicPreviewUrl);
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
// Die comic.js ist für die Lesezeichen-Funktion notwendig
$additionalScripts = "<script type='text/javascript' src='../src/layout/js/comic.js?c=20250722'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
// Die og:image URL muss absolut sein.
// Hier prüfen wir, ob $comicPreviewUrl bereits eine absolute URL ist
if (strpos($comicPreviewUrl, 'http://') === 0 || strpos($comicPreviewUrl, 'https://') === 0) {
    $absoluteComicPreviewUrl = $comicPreviewUrl; // Ist bereits absolut
} else {
    // Wenn es ein relativer Pfad ist (z.B. ../assets/thumbnails/...), müssen wir ihn absolut machen
    // Dazu entfernen wir den '..' Teil und konkatenieren mit $baseUrl
    $absoluteComicPreviewUrl = $baseUrl . ltrim($comicPreviewUrl, './');
}
if ($debugMode)
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
    <header style="position: relative;">
        <!-- H1-Tag im Format des Originals, Datum und Titel werden aus der JSON geladen. -->
        <h1><?php echo $pageHeader; ?></h1>
        <!-- Der Lesezeichen-Button wird nun im comicnav-Container platziert,
             damit die CSS-Regeln aus main.css korrekt angewendet werden können. -->
    </header>

    <div class='comicnav'>
        <?php
        // Binde die obere Comic-Navigation ein.
        // Pfad von src/components/ zu src/layout/
        include __DIR__ . '/../layout/comic_navigation.php';
        ?>
        <!-- Lesezeichen-Button mit der Original-Klasse und den Datenattributen -->
        <button type="button" id="add-bookmark" class="bookmark" title="Diese Seite mit Lesezeichen versehen"
            data-id="<?php echo htmlspecialchars($currentComicId); ?>"
            data-page="<?php echo htmlspecialchars($currentComicId); ?>"
            data-permalink="<?php echo htmlspecialchars($baseUrl . 'comic/' . $currentComicId . '.php'); ?>" data-thumb="<?php
                     // Hier prüfen wir erneut, ob $comicPreviewUrl bereits absolut ist
                     if (strpos($comicPreviewUrl, 'http://') === 0 || strpos($comicPreviewUrl, 'https://') === 0) {
                         echo htmlspecialchars($comicPreviewUrl); // Ist bereits absolut
                     } else {
                         // Wenn es ein relativer Pfad ist (z.B. ../assets/thumbnails/...), müssen wir ihn absolut machen
                         // Dazu entfernen wir den '..' Teil und konkatenieren mit $baseUrl
                         echo htmlspecialchars($baseUrl . ltrim($comicPreviewUrl, './'));
                     }
                     ?>">
            Bookmark this page
        </button>
    </div>

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version. -->
    <a href="<?php echo htmlspecialchars($comicHiresPath); ?>">
        <img src="<?php echo htmlspecialchars($comicImagePath); ?>" title="<?php echo htmlspecialchars($comicName); ?>"
            alt="Comic Page">
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
            <span class="nav-instruction-content">Sie können auch mit den Pfeiltasten oder den Tasten J und K
                navigieren.</span>
        </div>
        <!-- Link zur Lesezeichen-Seite (wie im Original verfügbar) -->
        <div class="bookmark-control">
            <a href="<?php echo htmlspecialchars($baseUrl); ?>lesezeichen.php" class="view-bookmarks-link">
                Lesezeichen anzeigen
            </a>
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