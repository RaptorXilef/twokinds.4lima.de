<?php
/**
 * Dies ist eine Beispiel-Comicseite (alias index.php).
 * Sie lädt Comic-Metadaten und zeigt das Comic-Bild, Navigation und Transkript an.
 */

// Lade die Comic-Daten aus der JSON-Datei
require_once __DIR__ . '/src/components/load_comic_data.php';

// Die ID der aktuellen Comic-Seite (der Dateiname ohne .php)
$currentComicId = basename(__FILE__, '.php');

// Hole die Daten für die aktuelle Comic-Seite
$comicTyp = '';
$comicName = '';
$comicTranscript = '';

if (isset($comicData[$currentComicId])) {
    $comicTyp = $comicData[$currentComicId]['type'];
    $comicName = $comicData[$currentComicId]['name'];
    $comicTranscript = $comicData[$currentComicId]['transcript'];
} else {
    // Fallback, falls die ID nicht in der JSON gefunden wird
    error_log("Warnung: Comic-Daten für ID '{$currentComicId}' nicht in comic_var.json gefunden.");
    $comicTyp = 'Comicseite vom ';
    $comicName = 'Unbekannter Comic';
    $comicTranscript = '<p>Für diese Seite ist kein Transkript verfügbar.</p>';
}

// Setze Parameter für den Header
$pageTitle = $comicTyp . $currentComicId . ' - ' . $comicName;
// Da die Comic-Seiten ihren eigenen H1 innerhalb des article-Tags haben, lassen wir $pageHeader leer.
$pageHeader = '';
$additionalScripts = "<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20201116'></script>";
// Google Analytics Code kann hier oder direkt im Header sein, je nach Präferenz.
// Wenn es nur auf Comic-Seiten ist, hier. Wenn global, im allgemeinen Header.
// Da es in der ursprünglichen comicpages_header.php enthalten war, belasse ich es als Teil des Headers.

// Binde den gemeinsamen Header ein
include __DIR__ . '/src/layout/header.php';
?>

<header>
    <h1><?php echo $comicTyp . date('d.m.Y', strtotime($currentComicId)); ?>: <?php echo htmlspecialchars($comicName); ?></h1>
</header>

<?php
// Binde das Comic-Bild ein
include __DIR__ . '/src/layout/comic_image.php';

// Binde die Comic-Navigation ein
include __DIR__ . '/src/layout/comic_navigation.php';
?>

<aside class="transcript">
    <h2>Transkript</h2>
    <div class="transcript-content">
        <?php echo $comicTranscript; ?>
    </div>
</aside>

<?php
// Binde den gemeinsamen Footer ein
include __DIR__ . '/src/layout/footer.php';
?>