<?php
/**
 * Fehlerseite für den HTTP-Status 404 (Not Found / Seite nicht gefunden).
 */

// Setze den HTTP-Statuscode, damit Browser und Suchmaschinen wissen, dass dies ein Fehler ist.
http_response_code(404);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: 404.php wird geladen.");

// === ANGEPASST: Lade den neuen zentralen Image-Cache-Helfer ===
require_once __DIR__ . '/src/components/image_cache_helper.php';

// === KORRIGIERTE Dynamische Basis-URL Bestimmung ===
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$appRootAbsPath = str_replace('\\', '/', dirname(__FILE__));
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
$subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);
if (!empty($subfolderPath) && $subfolderPath !== '/') {
    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
} elseif (empty($subfolderPath)) {
    $subfolderPath = '/';
}
$baseUrl = $protocol . $host . $subfolderPath;


// === NEUE LOGIK: Bildpfade für Low-Res und High-Res aus dem Cache abrufen ===
$lowresImage = get_cached_image_path('404', 'lowres');
$hiresImage = get_cached_image_path('404', 'hires');

$imageToShow = $lowresImage ? $baseUrl . $lowresImage : 'https://placehold.co/800x600/cccccc/333333?text=Seite+nicht+gefunden';
// Der Link zeigt auf die Hi-Res-Version, falls vorhanden, ansonsten auf die Low-Res-Version selbst.
$linkToShow = $hiresImage ? $baseUrl . $hiresImage : $imageToShow;

if ($debugMode && !$lowresImage) {
    error_log("DEBUG: 404-Bild nicht im Cache gefunden, verwende Placeholder.");
}


// Setze Parameter für den Header.
$pageTitle = 'Fehler 404 - Seite nicht gefunden';
$pageHeader = 'Fehler 404: Seite nicht gefunden';
$robotsContent = 'noindex, follow'; // Wichtig für SEO: Seite nicht indexieren

// Binde den gemeinsamen Header ein.
include __DIR__ . '/src/layout/header.php';
?>

<style>
    /* Passt die Größe des Fehler-Bildes an die Containerbreite an */
    #error-image {
        width: 100%;
        max-width: 800px;
        /* Maximale Breite des Bildes */
        height: auto;
        display: block;
        margin: 20px auto;
        /* Zentriert das Bild */
        border-radius: 8px;
    }

    .transcript {
        border-top: 1px dashed #ccc;
        padding-top: 15px;
        margin-top: 20px;
    }
</style>

<article class="comic">
    <header>
        <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
    </header>

    <div>
        <a href="<?php echo htmlspecialchars($linkToShow); ?>" target="_blank" rel="noopener noreferrer">
            <img id="error-image" src="<?php echo htmlspecialchars($imageToShow); ?>"
                alt="Fehlerbild: Seite nicht gefunden">
        </a>
    </div>

    <aside class="transcript">
        <h2>Wo sind wir denn hier gelandet?</h2>
        <div class="transcript-content">
            <p>
                Du bist einem Link gefolgt oder hast eine Adresse eingegeben, die ins Leere führt.
                Vielleicht hat sich ein Tippfehler eingeschlichen, oder die Seite wurde verschoben oder entfernt.
            </p>
            <p>
                <strong>Keine Sorge, hier sind ein paar Vorschläge, um wieder auf den richtigen Weg zu kommen:</strong>
            </p>
            <ul>
                <li>Überprüfe die URL auf Tippfehler.</li>
                <li>Gehe zurück zur <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php">Startseite</a>, um den
                    neuesten Comic zu sehen.</li>
                <li>Besuche das <a href="<?php echo htmlspecialchars($baseUrl); ?>archiv.php">Archiv</a>, um einen
                    bestimmten Comic zu finden.</li>
            </ul>
            <p>
                Wenn du glaubst, dass hier ein Fehler auf der Webseite vorliegt, würde ich mich freuen, wenn du ihn
                meldest!
            </p>
        </div>
    </aside>
</article>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/src/layout/footer.php';
?>