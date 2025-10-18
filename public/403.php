<?php
/**
 * Fehlerseite für den HTTP-Status 403 (Forbidden / Zugriff verweigert).
 * 
 * @file      ROOT/public/403.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// Setze den HTTP-Statuscode, damit Browser und Suchmaschinen wissen, dass dies ein Fehler ist.
http_response_code(403);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN (Jetzt mit der Path-Klasse) ===
require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'helper_image_cache.php';

// === 3. BILD-PFADE & FALLBACKS ===
$lowresImage = get_cached_image_path('403', 'lowres');
$hiresImage = get_cached_image_path('403', 'hires');

// URLs mit DIRECTORY_PUBLIC_URL erstellen
$imageToShow = $lowresImage ? DIRECTORY_PUBLIC_URL . '/' . ltrim($lowresImage, '/') : 'https://placehold.co/800x600/cccccc/333333?text=Zugriff+verweigert';
$linkToShow = $hiresImage ? DIRECTORY_PUBLIC_URL . '/' . ltrim($hiresImage, '/') : $imageToShow;

if ($debugMode && !$lowresImage) {
    error_log("DEBUG: 403-Bild nicht im Cache gefunden, verwende Placeholder.");
}

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Fehler 403 - Zugriff verweigert';
$pageHeader = 'Fehler 403: Zugriff verweigert';
$robotsContent = 'noindex, follow'; // Wichtig für SEO: Seite nicht indexieren

// === 5. HEADER EINBINDEN (mit Path-Klasse) ===
require_once Path::getTemplatePartial('header.php');
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
                Am besten kehrst du einfach zur <a
                    href="<?php echo htmlspecialchars(DIRECTORY_PUBLIC_URL); ?>">Startseite</a>
                zurück und setzt deine Reise von dort aus fort.
            </p>
        </div>
    </aside>
</article>

<?php require_once Path::getTemplatePartial('footer.php'); ?>