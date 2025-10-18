<?php
/**
 * Einmaliges Migrationsskript zur Umstellung der Charakter-Referenzen von Namen auf eindeutige IDs.
 * Migriert die Daten in charaktere.json und comic_var.json in die neue Struktur.
 * Ist nur einmalig auszuführen und sollte danach vom Server gelöscht werden.
 *
 * @file      ROOT/public/admin/migration_char_id.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.0
 * @since     1.1.0 Aktualisiert auf neue Konstanten-Struktur und robustere Verarbeitung.
 * @since     2.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === ZENTRALE ADMIN-INITIALISIERUNG ===
// Wird nur benötigt, um die Pfad-Konstanten und die Path-Klasse zu laden.
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starte Charakter-ID-Migration...\n\n";

// --- Dateipfade definieren ---
$charaktereJsonPath = Path::getDataPath('charaktere.json');
$comicVarJsonPath = Path::getDataPath('comic_var.json');
$charaktereJsonFilename = basename($charaktereJsonPath);
$comicVarJsonFilename = basename($comicVarJsonPath);

// --- Schritt 1: Überprüfen, ob die Migration bereits durchgeführt wurde ---
$isAlreadyMigrated = false;
if (file_exists($charaktereJsonPath)) {
    $currentContent = json_decode(file_get_contents($charaktereJsonPath), true);
    if (isset($currentContent['schema_version']) && $currentContent['schema_version'] >= 2) {
        $isAlreadyMigrated = true;
    }
}

if ($isAlreadyMigrated) {
    die("FEHLER: Die Migration scheint bereits durchgeführt worden zu sein (Schema-Version >= 2 in {$charaktereJsonFilename} erkannt).\nSkript wird beendet, um Datenverlust zu verhindern.\n");
}

// --- Schritt 2: Backups erstellen ---
echo "Erstelle Backups...\n";
$charaktereBackupPath = $charaktereJsonPath . '.bak';
$comicVarBackupPath = $comicVarJsonPath . '.bak';

if (file_exists($charaktereJsonPath) && !copy($charaktereJsonPath, $charaktereBackupPath)) {
    die("FEHLER: Konnte kein Backup von {$charaktereJsonFilename} erstellen. Breche ab.\n");
}
echo "  - Backup von {$charaktereJsonFilename} erstellt: " . basename($charaktereBackupPath) . "\n";

if (file_exists($comicVarJsonPath) && !copy($comicVarJsonPath, $comicVarBackupPath)) {
    die("FEHLER: Konnte kein Backup von {$comicVarJsonFilename} erstellen. Breche ab.\n");
}
echo "  - Backup von {$comicVarJsonFilename} erstellt: " . basename($comicVarBackupPath) . "\n\n";


// --- Schritt 3: charaktere.json umwandeln ---
echo "Wandle {$charaktereJsonFilename} um...\n";
$oldCharaktereData = json_decode(file_get_contents($charaktereJsonPath), true);
if (!$oldCharaktereData) {
    die("FEHLER: {$charaktereJsonFilename} konnte nicht gelesen oder dekodiert werden.\n");
}

$newCharaktereData = [
    'schema_version' => 2,
    'characters' => [],
    'groups' => []
];

$nameToIdMap = [];
$idCounter = 1;

foreach ($oldCharaktereData as $groupName => $characters) {
    if (!is_array($characters))
        continue;

    $groupIds = [];
    foreach ($characters as $charName => $details) {
        $charId = '';
        if (isset($nameToIdMap[$charName])) {
            $charId = $nameToIdMap[$charName];
        } else {
            $charId = 'char_' . str_pad($idCounter++, 4, '0', STR_PAD_LEFT);
            $nameToIdMap[$charName] = $charId;
            $newCharaktereData['characters'][$charId] = [
                'name' => $charName,
                'pic_url' => $details['charaktere_pic_url'] ?? ''
            ];
        }
        $groupIds[] = $charId;
    }
    $newCharaktereData['groups'][$groupName] = $groupIds;
}

if (file_put_contents($charaktereJsonPath, json_encode($newCharaktereData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    echo "  - {$charaktereJsonFilename} erfolgreich in das neue ID-Format konvertiert.\n";
    echo "  - " . count($newCharaktereData['characters']) . " eindeutige Charaktere gefunden und IDs zugewiesen.\n\n";
} else {
    die("FEHLER: Konnte die neue {$charaktereJsonFilename} nicht speichern.\n");
}


// --- Schritt 4: comic_var.json aktualisieren ---
echo "Aktualisiere {$comicVarJsonFilename} mit den neuen Charakter-IDs...\n";
$comicVarData = json_decode(file_get_contents($comicVarJsonPath), true);
if (!$comicVarData) {
    die("FEHLER: {$comicVarJsonFilename} konnte nicht gelesen oder dekodiert werden.\n");
}

$comicsToUpdate = [];
if (isset($comicVarData['schema_version']) && $comicVarData['schema_version'] >= 2) {
    $comicsToUpdate = $comicVarData['comics'];
} else {
    $comicsToUpdate = $comicVarData;
}

$updatedComicsCount = 0;
foreach ($comicsToUpdate as $comicId => &$comicDetails) {
    if (isset($comicDetails['charaktere']) && is_array($comicDetails['charaktere'])) {
        $updatedChars = [];
        $hasChanged = false;
        foreach ($comicDetails['charaktere'] as $charName) {
            if (isset($nameToIdMap[$charName])) {
                $updatedChars[] = $nameToIdMap[$charName];
                $hasChanged = true;
            } else {
                $updatedChars[] = $charName; // Behalte unbekannte Einträge bei
                echo "  - WARNUNG: Charakter '$charName' in Comic '$comicId' wurde in {$charaktereJsonFilename} nicht gefunden und konnte nicht migriert werden.\n";
            }
        }
        if ($hasChanged) {
            $comicDetails['charaktere'] = array_unique($updatedChars);
            $updatedComicsCount++;
        }
    }
}
unset($comicDetails);

$finalComicVarData = [
    'schema_version' => 2,
    'comics' => $comicsToUpdate
];

if (file_put_contents($comicVarJsonPath, json_encode($finalComicVarData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    echo "  - {$comicVarJsonFilename} erfolgreich aktualisiert. $updatedComicsCount Comic-Einträge wurden angepasst.\n\n";
} else {
    die("FEHLER: Konnte die aktualisierte {$comicVarJsonFilename} nicht speichern.\n");
}

echo "MIGRATION ERFOLGREICH ABGESCHLOSSEN!\n";
echo "Bitte lösche diese Datei ('" . basename(__FILE__) . "') jetzt vom Server.\n";
?>