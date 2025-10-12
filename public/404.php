<?php
/**
 * Fehlerseite für den HTTP-Status 404 (Not Found / Seite nicht gefunden).
 * 
 * @file      ROOT/public/404.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 */

// Setze den HTTP-Statuscode, damit Browser und Suchmaschinen wissen, dass dies ein Fehler ist.
http_response_code(404);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konstanten erst lädt.
require_once __DIR__ . '/../src/components/public_init.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit Konstanten) ===
require_once IMAGE_CACHE_HELPER_PATH;

// === 3. BILD-PFADE & FALLBACKS ===
$lowresImage = get_cached_image_path('404', 'lowres');
$hiresImage = get_cached_image_path('404', 'hires');

// Benutze die globale $baseUrl aus public_init.php
$imageToShow = $lowresImage ? $baseUrl . ltrim($lowresImage, './') : 'https://placehold.co/800x600/cccccc/333333?text=Seite+nicht+gefunden';
// Der Link zeigt auf die Hi-Res-Version, falls vorhanden, ansonsten auf die Low-Res-Version selbst.
$linkToShow = $hiresImage ? $baseUrl . ltrim($hiresImage, './') : $imageToShow;

if ($debugMode && !$lowresImage) {
    error_log("DEBUG: 404-Bild nicht im Cache gefunden, verwende Placeholder.");
}

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Fehler 404 - Seite nicht gefunden';
$pageHeader = 'Fehler 404: Seite nicht gefunden';
$robotsContent = 'noindex, follow'; // Wichtig für SEO: Seite nicht indexieren

// === 5. HEADER EINBINDEN (Jetzt mit Konstante) ===
require_once TEMPLATE_HEADER;
?>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
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
                <li>Gehe zurück zur <a href="<?php echo htmlspecialchars($baseUrl); ?>">Startseite</a>, um den
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
// Binde den gemeinsamen Footer ein (Jetzt mit Konstante).
require_once TEMPLATE_FOOTER;
?>