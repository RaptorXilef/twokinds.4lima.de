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
 *
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 * @since     4.0.1 Umstellung der Bildpfade und Logik auf das neue Layout.
 * @since     5.0.0 refactor(Page): Inline-CSS entfernt, Nutzung der Comic-Page-Komponenten für einheitliches Design.
 */

// Setze den HTTP-Statuscode, damit Browser und Suchmaschinen wissen, dass dies ein Fehler ist.
http_response_code(404);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. LADE-SKRIPTE & DATEN ===
// URLs erstellen
$lowresImage = URL::getImgLayoutLowresUrl('404.webp');
$hiresImage = URL::getImgLayoutHiresUrl('404.webp');

// Lokale Pfade erstellen
$lowresImagePath = Path::getImgLayoutLowresPath('404.webp');
$hiresImagePath = Path::getImgLayoutHiresPath('404.webp');

$placeholderUrl = 'https://placehold.co/800x600/cccccc/333333?text=Seite+nicht+gefunden';

$imageToShow = ($lowresImage && file_exists($lowresImagePath)) ? ltrim($lowresImage, '/') : $placeholderUrl;
$linkToShow = ($hiresImage && file_exists($hiresImagePath)) ? ltrim($hiresImage, '/') : $imageToShow;

if ($debugMode && !$lowresImage) {
    error_log("DEBUG: 404-Bild nicht im Cache gefunden, verwende Placeholder.");
}

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Fehler 404 - Seite nicht gefunden';
$pageHeader = 'Fehler 404: Seite nicht gefunden';
$robotsContent = 'noindex, follow'; // Wichtig für SEO: Seite nicht indexieren

// === 4. HEADER EINBINDEN ===
require_once Path::getPartialTemplatePath('header.php');
?>

<article class="comic">
    <header>
        <h1 class="page-header"><?php echo htmlspecialchars($pageHeader); ?></h1>
    </header>

    <div>
        <a href="<?php echo htmlspecialchars($linkToShow); ?>" target="_blank" rel="noopener noreferrer">
            <img class="comic-image" src="<?php echo htmlspecialchars($imageToShow); ?>"
                alt="Fehlerbild: Seite nicht gefunden">
        </a>
    </div>

    <aside class="transcript">
        <div class="transcript-header">
            <h3>Wo sind wir denn hier gelandet?</h3>
        </div>
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
                <li>Gehe zurück zur <a href="<?php echo htmlspecialchars(DIRECTORY_PUBLIC_URL); ?>">Startseite</a>, um
                    den
                    neuesten Comic zu sehen.</li>
                <li>Besuche das <a href="<?php echo htmlspecialchars(DIRECTORY_PUBLIC_URL); ?>/archiv.php">Archiv</a>,
                    um einen
                    bestimmten Comic zu finden.</li>
            </ul>
            <p>
                Wenn du glaubst, dass hier ein Fehler auf der Webseite vorliegt, würde ich mich freuen, wenn du ihn
                meldest!
            </p>
        </div>
    </aside>
</article>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
