<?php
// Pfad definieren und überprüfen
$analysticDir = 'analystic/';

if (!is_dir($analysticDir)) {
    mkdir($analysticDir, 0777, true);
}

// Variablen initialisieren
$analysticUserIP = $_SERVER['REMOTE_ADDR']; // Die IP-Adresse des Benutzers wird in $analysticUserIP gespeichert
$analysticCurrentYear = date('Y'); // Aktuelles Jahr ermitteln
$analysticCurrentMonth = date('m'); // Aktueller Monat ermitteln
$analysticCurrentDay = date('d'); // Aktueller Tag ermitteln
$analysticAktuelleDatei = basename($_SERVER['SCRIPT_FILENAME']);
$analysticAbsoluterPfad = dirname($_SERVER['SCRIPT_FILENAME']);
$analysticFilename = $analysticDir . $analysticUserIP . '.csv';
$analysticData = $analysticUserIP . ';' . $analysticCurrentDay . '.' . $analysticCurrentMonth . '.' . $analysticCurrentYear . ';' . $analysticAbsoluterPfad . ';' . $analysticAktuelleDatei;

// Prüfen, ob die Datei existiert
if (!file_exists($analysticFilename)) {
    // Datei erstellen und den Dateninhalt schreiben
    $analysticHandle = fopen($analysticFilename, 'w');
    fwrite($analysticHandle, $analysticData . "\n");
    fclose($analysticHandle);
} else {
    // Datei öffnen und Inhalte einlesen
    $analysticLines = file($analysticFilename, FILE_IGNORE_NEW_LINES);

    // Prüfen, ob der String bereits in der Datei enthalten ist
    $analysticFound = false;
    foreach ($analysticLines as $analysticLine) {
        if ($analysticLine === $analysticData) {
            $analysticFound = true;
            break;
        }
    }

    // Wenn der String nicht gefunden wurde, diesen hinzufügen
    if (!$analysticFound) {
        $analysticHandle = fopen($analysticFilename, 'a');
        fwrite($analysticHandle, $analysticData . "\n");
        fclose($analysticHandle);
    }
}
?>