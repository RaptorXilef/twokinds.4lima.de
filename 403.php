<?php
/**
 * Fehlerseite für den HTTP-Status 403 (Forbidden / Zugriff verweigert).
 */

// Setze den HTTP-Statuscode, damit Browser und Suchmaschinen wissen, dass dies ein Fehler ist.
http_response_code(403);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: 403.php wird geladen.");

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
$lowresImage = get_cached_image_path('403', 'lowres');
$hiresImage = get_cached_image_path('403', 'hires');

$imageToShow = $lowresImage ? $baseUrl . $lowresImage : 'https://placehold.co/800x600/cccccc/333333?text=Zugriff+verweigert';
// Der Link zeigt auf die Hi-Res-Version, falls vorhanden, ansonsten auf die Low-Res-Version selbst.
$linkToShow = $hiresImage ? $baseUrl . $hiresImage : $imageToShow;

if ($debugMode && !$lowresImage) {
    error_log("DEBUG: 403-Bild nicht im Cache gefunden, verwende Placeholder.");
}


// Setze Parameter für den Header.
$pageTitle = 'Fehler 403 - Zugriff verweigert';
$pageHeader = 'Fehler 403: Zugriff verweigert';
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
                alt="Fehlerbild: Zugriff verweigert">
        </a>
    </div>

    <aside class="transcript">
        <h2>Was ist passiert?</h2>
        <div class="transcript-content">
            <p>
                Hoppla! Es scheint, als hättest du versucht, einen Bereich zu betreten, für den du keine
                Zutrittsberechtigung hast.
                Stell es dir wie eine verschlossene Tür vor, für die nur bestimmte Personen einen Schlüssel haben.
            </p>
            <p>
                <strong>Mögliche Gründe dafür könnten sein:</strong>
            </p>
            <ul>
                <li>Du hast versucht, auf eine geschützte Systemdatei oder einen Ordner zuzugreifen.</li>
                <li>Die Berechtigungen für die angeforderte Ressource sind absichtlich eingeschränkt.</li>
            </ul>
            <p>
                Am besten kehrst du einfach zur <a href="./index.php">Startseite</a> zurück und setzt deine Reise von
                dort aus fort.
            </p>
        </div>
    </aside>
</article>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/src/layout/footer.php';
?>