<?php
/**
 * Index-Seite für die Charakter-Übersicht.
 * Diese Seite lädt alle Charaktere aus der charaktere.json und zeigt sie
 * mithilfe der wiederverwendeten character_display.php Komponente an.
 * 
 * @file      ROOT/public/charaktere/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     1.1.0 Entfernt Inline-Style, um CSP-Konformität zu gewährleisten.
 * @since     1.2.0 Hartcodierte Pfade durch globale Konstanten ersetzt.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und URL-Konstanten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../../src/components/public_init.php';

// === 2. DATEN VORBEREITEN ===
// Lade die Charakterdaten, um eine vollständige Liste aller Charakter-IDs zu erstellen.
$allCharacterIDs = [];
$charaktereJsonPath = Path::getData('charaktere.json');
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedCharaktere = json_decode($charaktereJsonContent, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($decodedCharaktere['characters'])) {
        // Sammle die IDs (Schlüssel) aller Charaktere.
        $allCharacterIDs = array_keys($decodedCharaktere['characters']);
    }
}

// Erstelle einen "virtuellen" Comic-Eintrag, der alle Charaktere enthält.
// Dies ermöglicht uns, die character_display.php Komponente ohne Änderungen wiederzuverwenden.
$currentComicId = 'all_characters';
$comicData = [
    $currentComicId => [
        'charaktere' => $allCharacterIDs
    ]
];

// === 3. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Charakterübersicht';
$siteDescription = 'Eine Übersicht aller Haupt- und Nebencharaktere, die im TwoKinds Webcomic vorkommen. Erfahre mehr über Trace, Flora, Keith und viele andere.';
$canonicalUrl = DIRECTORY_PUBLIC_CHARAKTERE_URL . '/'; // Verweist auf den Ordner
$robotsContent = 'index, follow';

// === 4. HEADER EINBINDEN ===
require_once Path::getTemplatePartial('header.php');
?>

<article class="charaktere-overview">
    <header>
        <h1>Alle Charaktere im Überblick</h1>
        <p class="character-overview-intro">Hier findest du eine Liste aller Charaktere, die im Comic eine Rolle
            spielen, sortiert nach ihren Gruppen.</p>
    </header>

    <?php
    // === 5. CHARAKTER-ANZEIGE KOMPONENTE EINBINDEN ===
    // Setze die Variable, um die Standard-Überschrift in der Komponente auszublenden.
    $showCharacterSectionTitle = false;
    require_once Path::getComponent('character_display.php');
    ?>
</article>

<?php
// === 6. FOOTER EINBINDEN ===
require_once Path::getTemplatePartial('footer.php');
?>