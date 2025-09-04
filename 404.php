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

// Definiere die Bildpfade
$errorImagePath = './assets/fehler/404.webp';
$fallbackImagePath = 'https://placehold.co/800x600/cccccc/333333?text=Seite+nicht+gefunden';
$imageToShow = file_exists(__DIR__ . '/' . ltrim($errorImagePath, './')) ? $errorImagePath : $fallbackImagePath;
$hiresImageToShow = $imageToShow; // Für 404 gibt es kein separates hochauflösendes Bild

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
                <li>Gehe zurück zur <a href="./index.php">Startseite</a>, um den neuesten Comic zu sehen.</li>
                <li>Besuche das <a href="./archiv.php">Archiv</a>, um einen bestimmten Comic zu finden.</li>
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
```

**Wichtiger letzter Schritt:**
Vergiss nicht, in deiner `.htaccess`-Datei (Block 2) sicherzustellen, dass diese beiden Dateien auch wirklich aufgerufen
werden:

```apache
# --- Block 2: Eigene Fehlerseiten (Benutzerfreundlichkeit) ---
ErrorDocument 403 /403.php
ErrorDocument 404 /404.php