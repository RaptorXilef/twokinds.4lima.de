<?php
/**
 * Dies ist die Startseite der TwoKinds-Webseite.
 * Sie lädt dynamisch den neuesten Comic und zeigt ihn an.
 * Der Seitentitel und Open Graph Metadaten werden spezifisch für die Startseite gesetzt.
 */

// Lade die Comic-Daten aus der JSON-Datei, die alle Comic-Informationen enthält.
require_once __DIR__ . '/src/components/load_comic_data.php';
// Lade die Helferfunktion zum Finden des Bildpfades.
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
    // Die Preview-URL wird lokal aus dem thumbnails-Ordner geladen.
    $comicPreviewUrl = getComicImagePath($currentComicId, './assets/comic_thumbnails/', '_preview');
    // Fallback falls kein spezifisches Preview-Bild gefunden wird.
    if (empty($comicPreviewUrl)) {
        $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Comic+Preview+Fehler';
    }
} else {
    // Fallback-Werte, falls keine Comic-Daten oder der neueste Comic nicht gefunden wird.
    error_log("Fehler: Daten für den neuesten Comic (ID '{$currentComicId}') nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite vom ';
    $comicName = 'Willkommen';
    $comicTranscript = '<p>Willkommen auf TwoKinds auf Deutsch! Leider konnte der neueste Comic nicht geladen werden.</p>';
    $comicPreviewUrl = 'https://placehold.co/1200x630/cccccc/333333?text=Fehler';
}

// Konvertiere die Comic-ID (Datum) ins deutsche Format TT.MM.JJJJ.
$formattedDateGerman = date('d.m.Y', strtotime($currentComicId));
// Konvertiere die Comic-ID (Datum) ins englische Format für den H1-Header (Original-Stil).
$formattedDateEnglish = date('F d, Y', strtotime($currentComicId));

// Setze Parameter für den Header.
// Der Seitentitel für den Browser-Tab ist spezifisch für die Startseite.
$pageTitle = 'Startseite'; // Der Präfix "TwoKinds auf Deutsch - " wird automatisch von header.php hinzugefügt.
// H1-Header für die Startseite. Er zeigt den Titel des neuesten Comics.
$pageHeader = 'Comic for ' . $formattedDateEnglish . ': ' . htmlspecialchars($comicName);
// Füge comic.js als zusätzliches Skript hinzu.
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20250531'></script>";

// Zusätzliche Meta-Tags für Social Media (Open Graph).
$additionalHeadContent = '
    <link rel="canonical" href="https://twokinds.4lima.de/">
    <meta property="og:title" content="TwoKinds auf Deutsch - Startseite">
    <meta property="og:description" content="Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.">
    <meta property="og:image" content="' . htmlspecialchars($comicPreviewUrl) . '">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://twokinds.4lima.de/">
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
    <a href="<?php echo htmlspecialchars(getComicImagePath($currentComicId, './assets/comic_hires/')); ?>">
        <img src="<?php echo htmlspecialchars(getComicImagePath($currentComicId, './assets/comic_lowres/')); ?>"
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
