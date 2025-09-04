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

// Lade die Cache-Busting-Konfiguration und Helferfunktion.
require_once __DIR__ . '/src/components/cache_config.php';

// === KORRIGIERTE Dynamische Basis-URL Bestimmung ===
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Ermittle den absoluten Dateisystempfad des Anwendungs-Roots.
// Da diese Datei im Root liegt, ist ihr Verzeichnis der Anwendungs-Root.
$appRootAbsPath = str_replace('\\', '/', dirname(__FILE__));

// Ermittle den absoluten Dateisystempfad des Webserver-Dokumenten-Roots.
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));

// Berechne den Unterordner-Pfad, falls die App in einem Unterordner liegt.
$subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);

// Stelle sicher, dass der Pfad korrekt formatiert ist (z.B. /mein-unterordner/)
if (!empty($subfolderPath) && $subfolderPath !== '/') {
    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
} elseif (empty($subfolderPath)) {
    $subfolderPath = '/';
}
$baseUrl = $protocol . $host . $subfolderPath;


// Definiere die Bildpfade
$errorImageName = '404.webp';
$errorImagePathOnDisk = 'assets/fehler/' . $errorImageName; // Pfad für die Dateisystem-Prüfung
$fallbackImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Seite+nicht+gefunden';

// === MODIFIZIERT: Logik nutzt nun die zentrale Helferfunktion ===
$imageToShow = $fallbackImagePath;
if (file_exists($errorImagePathOnDisk)) {
    $versionedPath = versioniere_bild_asset($errorImagePathOnDisk);
    $imageToShow = $baseUrl . $versionedPath;
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
        <img id="error-image" src="<?php echo htmlspecialchars($imageToShow); ?>"
            alt="Fehlerbild: Seite nicht gefunden">
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