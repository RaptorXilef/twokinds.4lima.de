<?php
/* Variablen festlegen */

// Manuell festlegen:
// Pfad definieren und überprüfen
$analysticDir = 'analystic';


// Automatisch festgelegt
// Variablen initialisieren
$analysticUserIP = $_SERVER['REMOTE_ADDR']; // Die IP-Adresse des Benutzers wird in $analysticUserIP gespeichert
$analysticCurrentYear = date('Y'); // Aktuelles Jahr ermitteln
$analysticCurrentMonth = date('m'); // Aktueller Monat ermitteln
$analysticCurrentDay = date('d'); // Aktueller Tag ermitteln
$aktuelleUhrzeit = date('H:i:s'); // Aktuelle Uhrzeit im Format "Stunden:Minuten:Sekunden"
// Die Stunden, Minuten und Sekunden separat speichern
$aktuelleStunden = date('H', strtotime($aktuelleUhrzeit));
$aktuelleMinuten = date('i', strtotime($aktuelleUhrzeit));
$aktuelleSekunden = date('s', strtotime($aktuelleUhrzeit));

$analysticAktuelleDatei = basename($_SERVER['SCRIPT_FILENAME']); // Aktueller Dateiname der Datei die aufgerufen wurde ermitteln (Nicht die, in der dieser Code steht. das wäre basename($_SERVER['__FILE__']) )
$analysticAbsoluterPfad = dirname($_SERVER['SCRIPT_FILENAME']); // Aktueller Dateipfad ab Stammverzeichnis der aufgerufen Datei ermitteln (Nicht die, in der dieser Code steht. das wäre dirname($_SERVER['__FILE__']) )
$analysticPfadname = $analysticDir . '/' . $analysticCurrentYear . '/' . $analysticCurrentMonth;
$analysticFilename = $analysticPfadname . '/' . $analysticUserIP . '.csv';
$analysticData = $analysticUserIP . ';' . $analysticCurrentDay . '.' . $analysticCurrentMonth . '.' . $analysticCurrentYear . ';' . $aktuelleUhrzeit . ';' . $analysticAbsoluterPfad . ';' . $analysticAktuelleDatei;


/* Ordner erstellen wenn noch nicht erstellt */
/*// Hauptordner erstellen
if (!is_dir($analysticDir)) {
    mkdir($analysticDir, 0777, true);
}
*/
//Unterordner erstellen (Jahr und Monat)
if (!is_dir($analysticPfadname)) {
    mkdir($analysticPfadname, 0777, true);
}


/* Hauptcode */
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