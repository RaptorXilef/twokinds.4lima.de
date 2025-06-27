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

    // Pfad für die Vorschau-URL (relativ zur aktuellen Datei)
    if (!empty($rawComicPreviewPath) && file_exists(realpath(__DIR__ . '/../' . $rawComicPreviewPath))) {
        $comicPreviewUrl = '../' . $rawComicPreviewPath;
        error_log("DEBUG: Comic Preview Bild gefunden: " . realpath(__DIR__ . '/' . $comicPreviewUrl));
    } else {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
        error_log("DEBUG: Fallback auf Placeholder-URL für Comic Preview: " . $comicPreviewUrl);
    }
} else {
    // Fallback-Werte, falls die Comic-ID nicht in der JSON-Datei gefunden wird.
    error_log("Warnung: Comic-Daten für ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite vom ';
    $comicName = 'Unbekannter Comic';
    $comicTranscript = '<p>Für diese Seite ist kein Transkript verfügbar.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
}

// Define paths for fallback "in translation" images relative to the current file (e.g., comic/20250604.php)
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
    error_log("DEBUG: Original Comic Bild gefunden: " . realpath(__DIR__ . '/' . $comicImagePath));
} else {
    // If original comic does not exist, try "in translation" image
    error_log("DEBUG: Original Comic Bild nicht gefunden oder Pfad leer. Versuche In Translation.");
    // Check if the "in translation" fallback exists
    if (file_exists(realpath(__DIR__ . '/' . $inTranslationLowres))) {
        $comicImagePath = $inTranslationLowres;
        $comicHiresPath = $inTranslationHires;
        error_log("DEBUG: In Translation Bild gefunden: " . realpath(__DIR__ . '/' . $comicImagePath));
    } else {
        // If "in translation" also doesn't exist, use generic placeholder URL
        error_log("FEHLER: 'in_translation' Bild nicht gefunden unter dem erwarteten Pfad: " . realpath(__DIR__ . '/' . $inTranslationLowres));
        $comicImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Bild+nicht+gefunden';
        $comicHiresPath = 'https://placehold.co/1600x1200/cccccc/333333?text=Bild+nicht+gefunden';
        error_log("DEBUG: Fallback auf allgemeine Placeholder-URL für Hauptcomicbild: " . $comicImagePath);
    }
}
error_log("DEBUG: Finaler \$comicImagePath, der im HTML verwendet wird: " . $comicImagePath);


// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ
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
    // Wir wollen: /twokinds/default-website/twokinds/
    $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
    array_pop($pathParts); // Entfernt '20250604.php'
    array_pop($pathParts); // Entfernt 'comic'
    $basePath = implode('/', $pathParts);
    $baseUrl = $protocol . $host . $basePath . '/';
    error_log("DEBUG: Lokale Basis-URL: " . $baseUrl);
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
    error_log("DEBUG: Live Basis-URL: " . $baseUrl);
}


// Zusätzliche Meta-Tags für Social Media (Open Graph).
// Für Open Graph URLs muss der Pfad absolut sein.
// Korrektur für ltrim(): Verwende substr und str_starts_with zum Entfernen des Präfixes.
$tempPreviewUrl = $comicPreviewUrl;
if (str_starts_with($tempPreviewUrl, '../')) {
    $tempPreviewUrl = substr($tempPreviewUrl, 3); // Entferne die ersten 3 Zeichen ('../')
}
$absoluteComicPreviewUrl = $baseUrl . htmlspecialchars($tempPreviewUrl);

error_log("DEBUG: Finaler \$absoluteComicPreviewUrl für Open Graph: " . $absoluteComicPreviewUrl);


$additionalHeadContent = '
    <link rel="canonical" href="' . $baseUrl . 'comic/' . htmlspecialchars($currentComicId) . '">
    <meta property="og:title" content="' . htmlspecialchars($comicName) . ' - TwoKinds auf Deutsch - Deine Fan Übersetzung">
    <meta property="og:description" content="' . htmlspecialchars($siteDescription) . '">
    <meta property="og:image" content="' . $absoluteComicPreviewUrl . '">
    <meta property="og:type" content="article">
    <meta property="og:url" content="' . $baseUrl . 'comic/' . htmlspecialchars($currentComicId) . '">
';
// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099';

// Binde den gemeinsamen Header ein.
$robotsContent = 'noindex, follow'; // Für lokale Tests sollte noindex,nofollow verwendet werden.
include __DIR__ . '/../src/layout/header.php';
?>

<article class="comic">
    <header>
        <!-- H1-Tag im Format des Originals, verwendet Comic-Typ, Datum (englisches Format) und Comic-Namen aus der JSON. -->
        <h1><?php echo htmlspecialchars($comicTyp) . $formattedDateEnglish; ?>: <?php echo htmlspecialchars($comicName); ?></h1>
    </header>

    <div class='comicnav'>
        <?php
        // Binde die obere Comic-Navigation ein.
        include __DIR__ . '/../src/layout/comic_navigation.php';
        ?>
    </div>

    <!-- Haupt-Comic-Bild mit Links zur Hi-Res-Version.
         Die Dateierweiterung wird dynamisch über die getComicImagePath-funktion ermittelt,
         oder ein Lückenfüller-Bild, falls das Original nicht existiert. -->
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
