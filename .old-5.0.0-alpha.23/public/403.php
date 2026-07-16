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
 *
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 * @since     4.0.1 Umstellung der Bildpfade und Logik auf das neue Layout.
 * @since     5.0.0 refactor(Page): Inline-CSS entfernt, Nutzung der Comic-Page-Komponenten für einheitliches Design.
 */

// Setze den HTTP-Statuscode, damit Browser und Suchmaschinen wissen, dass dies ein Fehler ist.
http_response_code(403);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. BILD-PFADE & FALLBACKS ===
$lowresImage = URL::getImgLayoutLowresUrl('403.webp');
$hiresImage = URL::getImgLayoutHiresUrl('403.webp');

$lowresImagePath = Path::getImgLayoutLowresPath('403.webp');
$hiresImagePath = Path::getImgLayoutHiresPath('403.webp');

$placeholderUrl = 'https://placehold.co/800x600/cccccc/333333?text=Zugriff+verweigert';

$imageToShow = ($lowresImage && file_exists($lowresImagePath)) ? ltrim($lowresImage, '/') : $placeholderUrl;
$linkToShow = ($hiresImage && file_exists($hiresImagePath)) ? ltrim($hiresImage, '/') : $imageToShow;

if ($debugMode && !$lowresImage) {
    error_log("DEBUG: 403-Bild nicht im Cache gefunden, verwende Placeholder.");
}

// === 4. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Fehler 403 - Zugriff verweigert';
$pageHeader = 'Fehler 403: Zugriff verweigert';
$robotsContent = 'noindex, follow'; // Wichtig für SEO: Seite nicht indexieren

// === 4. HEADER EINBINDEN ===
$isComicPage = true;
require_once Path::getPartialTemplatePath('header.php');
?>

    <header>
        <h1 class="page-header"><?php echo htmlspecialchars($pageHeader); ?></h1>
    </header>

    <div>
        <a href="<?php echo htmlspecialchars($linkToShow); ?>" target="_blank" rel="noopener noreferrer">
            <img class="comic-image" src="<?php echo htmlspecialchars($imageToShow); ?>"
                alt="Fehlerbild: Zugriff verweigert">
        </a>
    </div>

    <hr>

    <aside class="transcript">
        <div class="transcript-header">
            <h3>Was ist passiert?</h3>
        </div>
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

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
