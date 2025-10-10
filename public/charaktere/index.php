<?php
/**
 * Index-Seite für die Charakter-Übersicht.
 * Diese Seite lädt alle Charaktere aus der charaktere.json und zeigt sie
 * mithilfe der wiederverwendeten character_display.php Komponente an.
 * 
 * @file      /charaktere/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.1.0 Entfernt Inline-Style, um CSP-Konformität zu gewährleisten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG ===
// Der Pfad ist relativ zum /charaktere/ Ordner
require_once __DIR__ . '/../src/components/public_init.php';

// === 2. DATEN VORBEREITEN ===
// Lade die Charakterdaten, um eine vollständige Liste aller Charaktere zu erstellen.
$charaktereJsonPath = __DIR__ . '/../src/config/charaktere.json';
$allCharacterNames = [];
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedCharaktere = json_decode($charaktereJsonContent, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedCharaktere)) {
        // Sammle die Namen (Schlüssel) aller Charaktere aus allen Gruppen
        foreach ($decodedCharaktere as $group) {
            if (is_array($group)) {
                $allCharacterNames = array_merge($allCharacterNames, array_keys($group));
            }
        }
    }
}

// Erstelle einen "virtuellen" Comic-Eintrag, der alle Charaktere enthält.
// Dies ermöglicht uns, die character_display.php Komponente ohne Änderungen wiederzuverwenden.
$currentComicId = 'all_characters';
$comicData = [
    $currentComicId => [
        'charaktere' => $allCharacterNames
    ]
];

// === 3. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Charakterübersicht';
$siteDescription = 'Eine Übersicht aller Haupt- und Nebencharaktere, die im TwoKinds Webcomic vorkommen. Erfahre mehr über Trace, Flora, Keith und viele andere.';
$canonicalUrl = $baseUrl . 'charaktere/'; // Verweist auf den Ordner
$robotsContent = 'index, follow';

// === 4. HEADER EINBINDEN ===
require_once __DIR__ . '/../src/layout/header.php';
?>

<article class="charaktere-overview">
    <header>
        <h1>Alle Charaktere im Überblick</h1>
        <!-- KORREKTUR: Inline-Style durch eine CSS-Klasse ersetzt -->
        <p class="character-overview-intro">Hier findest du eine Liste aller Charaktere, die im Comic eine Rolle
            spielen, sortiert nach ihren Gruppen.</p>
    </header>

    <?php
    // === 5. CHARAKTER-ANZEIGE KOMPONENTE EINBINDEN ===
    // Setze die Variable, um die Standard-Überschrift in der Komponente auszublenden.
    $showCharacterSectionTitle = false;
    require_once __DIR__ . '/../src/components/character_display.php';
    ?>
</article>

<?php
// === 6. FOOTER EINBINDEN ===
require_once __DIR__ . '/../src/layout/footer.php';
?>