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
    // Die Preview-URL wird lokal aus dem thumbnails-Ordner geladen.
    // Pfad relativ zum Hauptverzeichnis, da getComicImagePath so funktioniert.
    $comicPreviewUrl = getComicImagePath($currentComicId, './assets/comic_thumbnails/', '_preview');
    // Fallback falls kein spezifisches Preview-Bild gefunden wird
    if (empty($comicPreviewUrl)) {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
    }
} else {
    // Fallback-Werte, falls die Comic-ID nicht in der JSON-Datei gefunden wird.
    error_log("Warnung: Comic-Daten für ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite vom ';
    $comicName = 'Unbekannter Comic';
    $comicTranscript = '<p>Für diese Seite ist kein Transkript verfügbar.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
}

// Definiere die Pfade zu den Lückenfüller-Bildern.
// Diese Pfade sind relativ zum Hauptverzeichnis.
$inTranslationLowres = './assets/comic_lowres/in_translation.png';
$inTranslationHires = './assets/comic_hires/in_translation.jpg';

// Ermittle die Pfade zu den Comic-Bildern mit der Helferfunktion.
// Die Funktion getComicImagePath gibt Pfade relativ zum Hauptverzeichnis zurück.
// Da diese Datei im Unterordner 'comic/' liegt, müssen wir '../' voranstellen,
// um vom aktuellen Standort ins Hauptverzeichnis zu gelangen und dann den von getComicImagePath
// zurückgegebenen Pfad anzuhängen.
$comicImagePath = '../' . getComicImagePath($currentComicId, './assets/comic_lowres/');
$comicHiresPath = '../' . getComicImagePath($currentComicId, './assets/comic_hires/');

// Prüfe, ob die tatsächlichen Bilder existieren (unter Berücksichtigung des Prefixes).
// Hier müssen wir file_exists mit dem korrekten, vom Dateisystem aus gesehenen Pfad prüfen.
// ACHTUNG: getComicImagePath prüft bereits, ob die Datei existiert und gibt bei Nicht-Existenz einen leeren String zurück.
// Daher ist der ursprüngliche if-Block, der Lückenfüller setzt, immer noch wichtig.
if (empty($comicImagePath)) { // Wenn getComicImagePath einen leeren String zurückgab
    $comicImagePath = '../' . $inTranslationLowres;
    $comicHiresPath = '../' . $inTranslationHires;
}


// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));
// Konvertiere die Comic-ID (Datum) ins englische Format für den Original-H1-Header-Stil "Month Day, Year"
$formattedDateEnglish = date('F d, Y', strtotime($currentComicId));

// Die allgemeine Seitenbeschreibung, die in header.php verwendet wird.
$siteDescription = 'Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.';

// Setze Parameter für den Header.
// Der Seitentitel für den Browser-Tab wird für bessere Sortierbarkeit formatiert: "TwoKinds auf Deutsch - Comicseite vom JJJJ.MM.TT - Comic Name".
$pageTitle = $comicTyp . date('Y.m.d', strtotime($currentComicId)) . ' - ' . $comicName;
// H1-Header bleibt hier leer, da er direkt im article-Tag definiert wird (Original-Stil).
$pageHeader = '';
// Füge comic.js als zusätzliches Skript hinzu (Version an Original angepasst).
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
$additionalHeadContent = '
    <link rel="canonical" href="https://twokinds.keenspot.com/comic/' . htmlspecialchars($currentComicId) . '">
    <meta property="og:title" content="' . htmlspecialchars($comicName) . ' - TwoKinds auf Deutsch - Deine Fan Übersetzung">
    <meta property="og:description" content="' . htmlspecialchars($siteDescription) . '">
    <meta property="og:image" content="' . htmlspecialchars($comicPreviewUrl) . '">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://twokinds.keenspot.com/comic/' . htmlspecialchars($currentComicId) . '">
';
// Viewport-Meta-Tag an Original angepasst.
$viewportContent = 'width=1099';

// Binde den gemeinsamen Header ein.
$robotsContent = 'noindex, follow';
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
