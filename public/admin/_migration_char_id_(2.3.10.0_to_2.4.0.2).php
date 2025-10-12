<?php
/**
 * Einmaliges Migrationsskript zur Umstellung der Charakter-Referenzen von Namen auf eindeutige IDs.
 * Migriert die Daten in charaktere.json und comic_var.json in die neue Struktur von Projektversion 2.3.10.0 und früheren Versionen
 * in das neue Format, das in Version 2.4.0.2 eingeführt wurde.
 * Ist nur einmalig auszuführen und sollte danach vom Server gelöscht werden.
 *
 * @file      ROOT/public/admin/migration_char_id.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @version   1.1.0
 * @since     1.1.0 Aktualisiert auf neue Konstanten-Struktur und robustere Verarbeitung.
 */

// === ZENTRALE ADMIN-INITIALISIERUNG ===
// Wird nur benötigt, um die Pfad-Konstanten zu laden.
require_once __DIR__ . '/../../src/components/admin_init.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starte Charakter-ID-Migration...\n\n";

// --- Schritt 1: Überprüfen, ob die Migration bereits durchgeführt wurde ---
$isAlreadyMigrated = false;
if (file_exists(CHARAKTERE_JSON)) {
    $currentContent = json_decode(file_get_contents(CHARAKTERE_JSON), true);
    if (isset($currentContent['schema_version']) && $currentContent['schema_version'] >= 2) {
        $isAlreadyMigrated = true;
    }
}

if ($isAlreadyMigrated) {
    die("FEHLER: Die Migration scheint bereits durchgeführt worden zu sein (Schema-Version >= 2 in " . CHARAKTERE_JSON_FILE . " erkannt).\nSkript wird beendet, um Datenverlust zu verhindern.\n");
}

// --- Schritt 2: Backups erstellen ---
echo "Erstelle Backups...\n";
$charaktereBackupPath = CHARAKTERE_JSON . '.bak';
$comicVarBackupPath = COMIC_VAR_JSON . '.bak';

if (!copy(CHARAKTERE_JSON, $charaktereBackupPath)) {
    die("FEHLER: Konnte kein Backup von " . CHARAKTERE_JSON_FILE . " erstellen. Breche ab.\n");
}
echo "  - Backup von " . CHARAKTERE_JSON_FILE . " erstellt: " . basename($charaktereBackupPath) . "\n";

if (file_exists(COMIC_VAR_JSON) && !copy(COMIC_VAR_JSON, $comicVarBackupPath)) {
    die("FEHLER: Konnte kein Backup von " . COMIC_VAR_JSON_FILE . " erstellen. Breche ab.\n");
}
echo "  - Backup von " . COMIC_VAR_JSON_FILE . " erstellt: " . basename($comicVarBackupPath) . "\n\n";


// --- Schritt 3: charaktere.json umwandeln ---
echo "Wandle " . CHARAKTERE_JSON_FILE . " um...\n";
$oldCharaktereData = json_decode(file_get_contents(CHARAKTERE_JSON), true);
if (!$oldCharaktereData) {
    die("FEHLER: " . CHARAKTERE_JSON_FILE . " konnte nicht gelesen oder dekodiert werden.\n");
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

if (file_put_contents(CHARAKTERE_JSON, json_encode($newCharaktereData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    echo "  - " . CHARAKTERE_JSON_FILE . " erfolgreich in das neue ID-Format konvertiert.\n";
    echo "  - " . count($newCharaktereData['characters']) . " eindeutige Charaktere gefunden und IDs zugewiesen.\n\n";
} else {
    die("FEHLER: Konnte die neue " . CHARAKTERE_JSON_FILE . " nicht speichern.\n");
}


// --- Schritt 4: comic_var.json aktualisieren ---
echo "Aktualisiere " . COMIC_VAR_JSON_FILE . " mit den neuen Charakter-IDs...\n";
$comicVarData = json_decode(file_get_contents(COMIC_VAR_JSON), true);
if (!$comicVarData) {
    die("FEHLER: " . COMIC_VAR_JSON_FILE . " konnte nicht gelesen oder dekodiert werden.\n");
}

// NEU: Prüfen, ob comic_var.json bereits das neue Schema hat (z.B. bei Teil-Migration)
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
                echo "  - WARNUNG: Charakter '$charName' in Comic '$comicId' wurde in " . CHARAKTERE_JSON_FILE . " nicht gefunden und konnte nicht migriert werden.\n";
            }
        }
        if ($hasChanged) {
            $comicDetails['charaktere'] = array_unique($updatedChars);
            $updatedComicsCount++;
        }
    }
}
unset($comicDetails);

// Erstelle die finale Datenstruktur für Schema v2
$finalComicVarData = [
    'schema_version' => 2,
    'comics' => $comicsToUpdate
];

if (file_put_contents(COMIC_VAR_JSON, json_encode($finalComicVarData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    echo "  - " . COMIC_VAR_JSON_FILE . " erfolgreich aktualisiert. $updatedComicsCount Comic-Einträge wurden angepasst.\n\n";
} else {
    die("FEHLER: Konnte die aktualisierte " . COMIC_VAR_JSON_FILE . " nicht speichern.\n");
}

echo "MIGRATION ERFOLGREICH ABGESCHLOSSEN!\n";
echo "Bitte lösche diese Datei ('" . basename(__FILE__) . "') jetzt vom Server.\n";
?>