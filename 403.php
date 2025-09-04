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
$errorImageName = '403.webp';
$errorImagePathOnDisk = 'assets/fehler/' . $errorImageName; // Pfad für die Dateisystem-Prüfung
$fallbackImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Seite+nicht+gefunden';

// === MODIFIZIERT: Logik nutzt nun die zentrale Helferfunktion ===
$imageToShow = $fallbackImagePath;
if (file_exists($errorImagePathOnDisk)) {
    $versionedPath = versioniere_bild_asset($errorImagePathOnDisk);
    $imageToShow = $baseUrl . $versionedPath;
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
        <h1>
            <?php echo htmlspecialchars($pageHeader); ?>
        </h1>
    </header>

    <div>
        <img id="error-image" src="<?php echo htmlspecialchars($imageToShow); ?>" alt="Fehlerbild: Zugriff verweigert">
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