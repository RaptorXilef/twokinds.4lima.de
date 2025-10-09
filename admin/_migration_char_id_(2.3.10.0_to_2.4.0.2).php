<?php
/**
 * Einmaliges Migrationsskript zur Umstellung der Charakter-Referenzen von Namen auf eindeutige IDs.
 * Migriert die Daten in charaktere.json und comic_var.json in die neue Struktur von Projektversion 2.3.10.0 und früheren Versionen
 * in das neue Format, das in Version 2.4.0.2 eingeführt wurde.
 * Ist nur einmalig auszuführen und sollte danach vom Server gelöscht werden.
 *
 * @file      /admin/migration_char_id.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @version   1.0.0
 */

require_once __DIR__ . '/src/components/admin_init.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starte Charakter-ID-Migration...\n\n";

// Pfade definieren
$charaktereJsonPath = __DIR__ . '/../src/config/charaktere.json';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';

// Backup-Pfade
$charaktereBackupPath = $charaktereJsonPath . '.bak';
$comicVarBackupPath = $comicVarJsonPath . '.bak';

// --- Schritt 1: Überprüfen, ob die Migration bereits durchgeführt wurde ---
$isAlreadyMigrated = false;
if (file_exists($charaktereJsonPath)) {
    $currentContent = json_decode(file_get_contents($charaktereJsonPath), true);
    if (isset($currentContent['schema_version']) && $currentContent['schema_version'] >= 2) {
        $isAlreadyMigrated = true;
    }
}

if ($isAlreadyMigrated) {
    die("FEHLER: Die Migration scheint bereits durchgeführt worden zu sein (Schema-Version >= 2 erkannt).\nSkript wird beendet, um Datenverlust zu verhindern.\n");
}

// --- Schritt 2: Backups erstellen ---
echo "Erstelle Backups...\n";
if (!copy($charaktereJsonPath, $charaktereBackupPath)) {
    die("FEHLER: Konnte kein Backup von charaktere.json erstellen. Breche ab.\n");
}
echo "  - Backup von charaktere.json erstellt: " . basename($charaktereBackupPath) . "\n";

if (!copy($comicVarJsonPath, $comicVarBackupPath)) {
    die("FEHLER: Konnte kein Backup von comic_var.json erstellen. Breche ab.\n");
}
echo "  - Backup von comic_var.json erstellt: " . basename($comicVarBackupPath) . "\n\n";


// --- Schritt 3: charaktere.json umwandeln ---
echo "Wandle charaktere.json um...\n";
$oldCharaktereData = json_decode(file_get_contents($charaktereJsonPath), true);
if (!$oldCharaktereData) {
    die("FEHLER: charaktere.json konnte nicht gelesen oder dekodiert werden.\n");
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
        // Prüfen, ob der Charakter schon eine ID hat (falls er in mehreren Gruppen vorkommt)
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
    echo "  - charaktere.json erfolgreich in das neue ID-Format konvertiert.\n";
    echo "  - " . count($newCharaktereData['characters']) . " eindeutige Charaktere gefunden und IDs zugewiesen.\n\n";
} else {
    die("FEHLER: Konnte die neue charaktere.json nicht speichern.\n");
}


// --- Schritt 4: comic_var.json aktualisieren ---
echo "Aktualisiere comic_var.json mit den neuen Charakter-IDs...\n";
$comicVarData = json_decode(file_get_contents($comicVarJsonPath), true);
if (!$comicVarData) {
    die("FEHLER: comic_var.json konnte nicht gelesen oder dekodiert werden.\n");
}

$updatedComicsCount = 0;
foreach ($comicVarData as $comicId => &$comicDetails) {
    if (isset($comicDetails['charaktere']) && is_array($comicDetails['charaktere'])) {
        $updatedChars = [];
        $hasChanged = false;
        foreach ($comicDetails['charaktere'] as $charName) {
            if (isset($nameToIdMap[$charName])) {
                $updatedChars[] = $nameToIdMap[$charName];
                $hasChanged = true;
            } else {
                // Name nicht gefunden, behalte ihn vorerst, aber gib eine Warnung aus
                $updatedChars[] = $charName;
                echo "  - WARNUNG: Charakter '$charName' in Comic '$comicId' wurde in charaktere.json nicht gefunden und konnte nicht migriert werden.\n";
            }
        }
        if ($hasChanged) {
            $comicDetails['charaktere'] = array_unique($updatedChars);
            $updatedComicsCount++;
        }
    }
}
unset($comicDetails); // Referenz aufheben

if (file_put_contents($comicVarJsonPath, json_encode($comicVarData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    echo "  - comic_var.json erfolgreich aktualisiert. $updatedComicsCount Comic-Einträge wurden angepasst.\n\n";
} else {
    die("FEHLER: Konnte die aktualisierte comic_var.json nicht speichern.\n");
}

echo "MIGRATION ERFOLGREICH ABGESCHLOSSEN!\n";
echo "Du kannst diese Datei ('migration_char_id.php') jetzt vom Server löschen.\n";

?>