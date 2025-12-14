<?php
/**
 * Index-Seite für die Charakter-Übersicht.
 * Diese Seite lädt alle Charaktere aus der charaktere.json und zeigt sie
 * mithilfe der wiederverwendeten display_character.php Komponente an.
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
require_once __DIR__ . '/../../src/components/init_public.php';

// === 2. DATEN VORBEREITEN ===
// Lade die Charakterdaten, um eine vollständige Liste aller Charakter-IDs zu erstellen.
$allCharacterIDs = [];
$charaktereJsonPath = Path::getDataPath('charaktere.json');
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedCharaktere = json_decode($charaktereJsonContent, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($decodedCharaktere['characters'])) {
        // Sammle die IDs (Schlüssel) aller Charaktere.
        $allCharacterIDs = array_keys($decodedCharaktere['characters']);
    }
}

// Erstelle einen "virtuellen" Comic-Eintrag, der alle Charaktere enthält.
// Dies ermöglicht uns, die display_character.php Komponente ohne Änderungen wiederzuverwenden.
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
require_once Path::getPartialTemplatePath('header.php');
?>

<article class="charaktere-overview">

    <header>
        <h1 class="page-header">Alle Charaktere im Überblick</h1>
        <p class="character-overview-intro">Hier findest du eine Liste aller Charaktere, die im Comic eine Rolle
            spielen, sortiert nach ihren Gruppen.</p>
    </header>

    <?php
    // === 5. CHARAKTER-ANZEIGE KOMPONENTE EINBINDEN ===
    // Setze die Variable, um die Standard-Überschrift in der Komponente auszublenden.
    $showCharacterSectionTitle = false;
    require_once DIRECTORY_PRIVATE_COMPONENTS . DIRECTORY_SEPARATOR . 'display_character.php';
    ?>
</article>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
