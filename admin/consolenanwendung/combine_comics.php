<?php

/**
 * PHP-Skript zum Kombinieren mehrerer JSON-Dateien in einem Ordner
 * zu einer einzigen 'comic_var.json'-Datei.
 *
 * Dabei werden doppelte Einträge anhand des Datums-Schlüssels behandelt:
 * - Wenn ein Eintrag in der 'comic_var.json' existiert, aber 'name' und/oder 'transcript' leer sind,
 * werden diese Felder mit den Werten aus der neueren JSON-Datei ergänzt, falls diese dort vorhanden sind.
 * - Wenn 'name' oder 'transcript' in der 'comic_var.json' bereits Inhalte haben,
 * behalten diese Inhalte Vorrang (werden nicht überschrieben).
 * - Für alle anderen Felder hat der Eintrag aus der neueren JSON-Datei Vorrang.
 *
 * Autor: Felix Maywald
 * Datum: 2025-07-28
 */

// --- Konfiguration ---
const COMICS_DIR = 'scrape_comics/'; // Ordner, der die JSON-Dateien enthält, die zusammengeführt werden sollen
// --- ANPASSBARER PFAD FÜR DIE KOMBINIERTE MASTER-JSON-DATEI ---
// Geben Sie hier den vollständigen oder relativen Pfad zur kombinierten comic_var.json an.
// Beispiel (relativ zum Skript-Ordner): 'output/comic_var.json'
// Beispiel (absoluter Pfad unter Windows): 'C:/MeinProjekt/output/comic_var.json'
// Beispiel (absoluter Pfad unter Linux): '/var/www/html/output/comic_var.json'
// Wenn Sie es im selben Ordner wie die einzelnen JSONs haben möchten, lassen Sie es wie unten:
const MASTER_JSON_OUTPUT_PATH = 'scrape_comics/comic_var.json';

// --- DEBUG-MODUS ---
// Setze auf 'true', um detaillierte Debug-Ausgaben in der Konsole zu sehen.
// Setze auf 'false', für eine weniger ausführliche Ausgabe.
const DEBUG_MODE = true;

// --- Initialisierung für Browser-Ausgabe (optional, primär für Konsole gedacht) ---
@ob_implicit_flush();
@ob_end_clean(); // Leert und beendet alle aktiven Output-Buffer

echo "Starte JSON-Kombinierer...\n";
echo "Überprüfe erforderliche PHP-Erweiterungen...\n";
flush();

// Prüfe, ob die JSON-Erweiterung aktiviert ist
if (!extension_loaded('json')) {
    echo "\nFEHLER: Die 'json'-Erweiterung ist nicht aktiviert.\n";
    echo "Bitte aktiviere sie in deiner php.ini-Datei, indem du die Zeile 'extension=json' (oder 'extension=php_json.dll' unter Windows) einkommentierst (Semikolon am Anfang entfernen).\n";
    echo "Starte danach deinen Webserver neu.\n";
    exit(1); // Skript beenden mit Fehlercode
}

echo "Erforderliche Erweiterungen sind aktiv. Fahre fort...\n\n";
flush();

// Bestimme den absoluten Pfad des Skripts
$scriptPath = __DIR__;

// Absolute Pfade für Quellordner und Zieldatei
$comicsFullPath = $scriptPath . DIRECTORY_SEPARATOR . COMICS_DIR;
// Behandelt, ob MASTER_JSON_OUTPUT_PATH ein absoluter oder relativer Pfad ist
if (strpos(MASTER_JSON_OUTPUT_PATH, '/') === 0 || strpos(MASTER_JSON_OUTPUT_PATH, ':') !== false) {
    $masterJsonFullPath = MASTER_JSON_OUTPUT_PATH; // Absoluter Pfad
} else {
    $masterJsonFullPath = $scriptPath . DIRECTORY_SEPARATOR . MASTER_JSON_OUTPUT_PATH; // Relativer Pfad
}

// Extrahiere den Verzeichnisnamen für die Master-JSON-Datei, um sicherzustellen, dass er existiert
$masterJsonOutputDir = dirname($masterJsonFullPath);


echo "Quellordner für einzelne JSONs: " . $comicsFullPath . "\n";
echo "Ziel-Datei für kombinierte JSON: " . $masterJsonFullPath . "\n";
flush();

// Prüfe, ob der Comics-Ordner existiert
if (!is_dir($comicsFullPath)) {
    echo "\nFEHLER: Der Ordner '" . $comicsFullPath . "' existiert nicht. Bitte stelle sicher, dass die gescrapten JSON-Dateien dort liegen.\n";
    exit(1);
}

// Erstelle den Ausgabeordner für die Master-JSON, falls er nicht existiert
if (!is_dir($masterJsonOutputDir)) {
    if (DEBUG_MODE) {
        echo "  [DEBUG] Erstelle Ausgabeordner für Master-JSON: " . $masterJsonOutputDir . "\n";
        flush();
    }
    if (!mkdir($masterJsonOutputDir, 0777, true)) {
        echo "\nFEHLER: Der Ausgabeordner für die Master-JSON '" . $masterJsonOutputDir . "' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.\n";
        exit(1);
    } else {
        if (DEBUG_MODE) {
            echo "  [DEBUG] Ausgabeordner für Master-JSON erfolgreich erstellt.\n";
            flush();
        }
    }
}


$combinedData = [];

// Lade die bestehende Master-JSON-Datei, falls vorhanden
if (file_exists($masterJsonFullPath)) {
    if (DEBUG_MODE) {
        echo "  [DEBUG] Lade bestehende Master-JSON: {$masterJsonFullPath}\n";
        flush();
    }
    $masterJsonContent = file_get_contents($masterJsonFullPath);
    if ($masterJsonContent === false) {
        echo "\nFEHLER: Konnte bestehende Master-JSON-Datei nicht lesen: {$masterJsonFullPath}\n";
        exit(1);
    }
    $decodedMasterData = json_decode($masterJsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "\nFEHLER: Konnte bestehende Master-JSON-Datei nicht dekodieren. Fehler: " . json_last_error_msg() . "\n";
        exit(1);
    }
    $combinedData = $decodedMasterData;
    echo "Bestehende Master-JSON-Datei geladen. Enthält " . count($combinedData) . " Einträge.\n";
    flush();
} else {
    echo "Keine bestehende Master-JSON-Datei gefunden. Erstelle eine neue.\n";
    flush();
}

// Suche alle JSON-Dateien im Ordner, außer der Master-Datei selbst
// Wichtig: glob() sucht nur im COMICS_DIR, nicht im MASTER_JSON_OUTPUT_PATH
$jsonFiles = glob($comicsFullPath . 'comic_var_*.json');
$filesToMerge = [];

foreach ($jsonFiles as $filePath) {
    $fileName = basename($filePath);
    // Stelle sicher, dass wir die Master-Datei nicht aus dem Quellordner lesen, falls sie dort liegt
    if ($filePath !== $masterJsonFullPath) {
        $filesToMerge[] = $filePath;
    }
}

// Sortiere die Dateien nach Namen (was sie chronologisch sortiert)
sort($filesToMerge);

if (empty($filesToMerge)) {
    echo "Keine neuen JSON-Dateien zum Zusammenführen gefunden in '" . $comicsFullPath . "'.\n";
    echo "Skript beendet.\n";
    exit(0);
}

echo "Gefundene Dateien zum Zusammenführen (" . count($filesToMerge) . "): \n";
foreach ($filesToMerge as $file) {
    echo " - " . basename($file) . "\n";
}
echo "\nStarte Zusammenführung...\n";
flush();

$mergedCount = 0;
$updatedCount = 0;

foreach ($filesToMerge as $filePath) {
    $fileName = basename($filePath);
    echo "\nVerarbeite Datei: {$fileName}\n";
    flush();

    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        echo "  [WARNUNG] Konnte Datei nicht lesen: {$fileName}. Überspringe.\n";
        flush();
        continue;
    }

    $newData = json_decode($fileContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "  [WARNUNG] Konnte Datei nicht dekodieren: {$fileName}. Fehler: " . json_last_error_msg() . ". Überspringe.\n";
        flush();
        continue;
    }

    foreach ($newData as $key => $newEntry) {
        if (DEBUG_MODE) {
            echo "  [DEBUG] Verarbeite Eintrag mit Schlüssel: '{$key}' aus '{$fileName}'\n";
            flush();
        }

        if (isset($combinedData[$key])) {
            // Eintrag existiert bereits in combinedData
            $existingEntry = $combinedData[$key];
            $entryUpdated = false;

            if (DEBUG_MODE) {
                echo "    [DEBUG] Eintrag '{$key}' existiert bereits. Wende Zusammenführungslogik an.\n";
                flush();
            }

            // Spezifische Logik für 'name': Wenn bestehender Name leer ist UND neuer Name nicht leer ist, aktualisiere.
            if (empty($existingEntry['name']) && !empty($newEntry['name'])) {
                $combinedData[$key]['name'] = $newEntry['name'];
                $entryUpdated = true;
                if (DEBUG_MODE) {
                    echo "      [DEBUG] 'name' aktualisiert von leer auf '{$newEntry['name']}'.\n";
                    flush();
                }
            }
            // Wenn existingEntry['name'] NICHT leer ist, behält es Vorrang (nichts tun).

            // Spezifische Logik für 'transcript': Wenn bestehendes Transkript leer ist UND neues Transkript nicht leer ist, aktualisiere.
            if (empty($existingEntry['transcript']) && !empty($newEntry['transcript'])) {
                $combinedData[$key]['transcript'] = $newEntry['transcript'];
                $entryUpdated = true;
                if (DEBUG_MODE) {
                    echo "      [DEBUG] 'transcript' aktualisiert von leer auf (Länge " . strlen($newEntry['transcript']) . ").\n";
                    flush();
                }
            }
            // Wenn existingEntry['transcript'] NICHT leer ist, behält es Vorrang (nichts tun).

            // Für alle anderen Felder hat der neue Eintrag Vorrang, ABER nur wenn der Schlüssel im neuen Eintrag existiert.
            // Wir überschreiben nicht blind, um nicht versehentlich Felder zu entfernen.
            foreach ($newEntry as $field => $value) {
                if ($field !== 'name' && $field !== 'transcript') { // Diese wurden bereits speziell behandelt
                    if (isset($combinedData[$key][$field]) && $combinedData[$key][$field] !== $value) {
                        // Nur aktualisieren, wenn Wert sich ändert und nicht name/transcript ist
                        $combinedData[$key][$field] = $value;
                        $entryUpdated = true;
                        if (DEBUG_MODE) {
                            echo "      [DEBUG] Feld '{$field}' aktualisiert.\n";
                            flush();
                        }
                    } elseif (!isset($combinedData[$key][$field])) {
                        // Feld existiert im alten nicht, aber im neuen. Füge es hinzu.
                        $combinedData[$key][$field] = $value;
                        $entryUpdated = true;
                        if (DEBUG_MODE) {
                            echo "      [DEBUG] Feld '{$field}' hinzugefügt.\n";
                            flush();
                        }
                    }
                }
            }
            if ($entryUpdated) {
                $updatedCount++;
            }

        } else {
            // Eintrag existiert noch nicht, füge ihn einfach hinzu
            $combinedData[$key] = $newEntry;
            $mergedCount++;
            if (DEBUG_MODE) {
                echo "    [DEBUG] Eintrag '{$key}' neu hinzugefügt.\n";
                flush();
            }
        }
    }
}

echo "\nZusammenführung abgeschlossen.\n";
echo "Neue Einträge hinzugefügt: {$mergedCount}\n";
echo "Bestehende Einträge aktualisiert: {$updatedCount}\n";
echo "Gesamtzahl der Einträge in " . basename($masterJsonFullPath) . ": " . count($combinedData) . "\n";
flush();

// Speichere die kombinierte Daten in der Master-JSON-Datei
$jsonContent = json_encode($combinedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonContent === false) {
    $errorMsg = "Fehler beim Kodieren der kombinierten JSON-Daten: " . json_last_error_msg();
    error_log($errorMsg);
    echo "FEHLER: Beim Speichern der Master-JSON-Datei ist ein Problem aufgetreten. Details im PHP-Fehlerprotokoll.\n";
    if (DEBUG_MODE) {
        echo "  [DEBUG] {$errorMsg}\n";
        flush();
    }
} else {
    echo "Versuche, kombinierte Daten in '{$masterJsonFullPath}' zu speichern...\n";
    flush();
    if (file_put_contents($masterJsonFullPath, $jsonContent) === false) {
        $errorMsg = "Fehler beim Schreiben der Master-JSON-Datei: {$masterJsonFullPath}";
        error_log($errorMsg);
        echo "FEHLER: Master-JSON-Datei konnte NICHT in '{$masterJsonFullPath}' gespeichert werden. Bitte Berechtigungen und Pfad prüfen.\n";
        if (DEBUG_MODE) {
            echo "  [DEBUG] {$errorMsg}\n";
            flush();
        }
    } else {
        echo "Daten erfolgreich in '{$masterJsonFullPath}' gespeichert.\n";
        if (file_exists($masterJsonFullPath)) {
            echo "Bestätigung: Datei '{$masterJsonFullPath}' existiert nach dem Speichern.\n";
            if (DEBUG_MODE) {
                echo "  [DEBUG] Dateigröße von '{$masterJsonFullPath}': " . filesize($masterJsonFullPath) . " Bytes.\n";
                flush();
            }
        } else {
            echo "WARNUNG: Datei '{$masterJsonFullPath}' existiert NICHT, obwohl file_put_contents erfolgreich gemeldet wurde.\n";
        }
    }
}
flush();

echo "Skript beendet.\n";
flush();

?>