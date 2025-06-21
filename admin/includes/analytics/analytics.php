<?php
/* Variablen festlegen */

// Manuell festlegen:
// Pfad definieren und überprüfen
$analyticsDir = 'analytics';


// Automatisch festgelegt
// Variablen initialisieren
$analyticsUserIP = $_SERVER['REMOTE_ADDR']; // Die IP-Adresse des Benutzers wird in $analyticsUserIP gespeichert
$analyticsCurrentYear = date('Y'); // Aktuelles Jahr ermitteln
$analyticsCurrentMonth = date('m'); // Aktueller Monat ermitteln
$analyticsCurrentDay = date('d'); // Aktueller Tag ermitteln
$aktuelleUhrzeit = date('H:i:s'); // Aktuelle Uhrzeit im Format "Stunden:Minuten:Sekunden"
// Die Stunden, Minuten und Sekunden separat speichern
$aktuelleStunden = date('H', strtotime($aktuelleUhrzeit));
$aktuelleMinuten = date('i', strtotime($aktuelleUhrzeit));
$aktuelleSekunden = date('s', strtotime($aktuelleUhrzeit));

$analyticsAktuelleDatei = basename($_SERVER['SCRIPT_FILENAME']); // Aktueller Dateiname der Datei die aufgerufen wurde ermitteln (Nicht die, in der dieser Code steht. das wäre basename($_SERVER['__FILE__']) )
$analyticsAbsoluterPfad = dirname($_SERVER['SCRIPT_FILENAME']); // Aktueller Dateipfad ab Stammverzeichnis der aufgerufen Datei ermitteln (Nicht die, in der dieser Code steht. das wäre dirname($_SERVER['__FILE__']) )
$analyticsPfadname = $analyticsDir . '/' . $analyticsCurrentYear . '/' . $analyticsCurrentMonth;
$analyticsFilename = $analyticsPfadname . '/' . $analyticsUserIP . '.csv';
$analyticsData = $analyticsUserIP . ';' . $analyticsCurrentDay . '.' . $analyticsCurrentMonth . '.' . $analyticsCurrentYear . ';' . $aktuelleUhrzeit . ';' . $analyticsAbsoluterPfad . ';' . $analyticsAktuelleDatei;


/* Ordner erstellen wenn noch nicht erstellt */
/*// Hauptordner erstellen
if (!is_dir($analyticsDir)) {
    mkdir($analyticsDir, 0777, true);
}
*/
//Unterordner erstellen (Jahr und Monat)
if (!is_dir($analyticsPfadname)) {
    mkdir($analyticsPfadname, 0777, true);
}


/* Hauptcode */
// Prüfen, ob die Datei existiert
if (!file_exists($analyticsFilename)) {
    // Datei erstellen und den Dateninhalt schreiben
    $analyticsHandle = fopen($analyticsFilename, 'w');
    fwrite($analyticsHandle, $analyticsData . "\n");
    fclose($analyticsHandle);
} else {
    // Datei öffnen und Inhalte einlesen
    $analyticsLines = file($analyticsFilename, FILE_IGNORE_NEW_LINES);

    // Prüfen, ob der String bereits in der Datei enthalten ist
    $analyticsFound = false;
    foreach ($analyticsLines as $analyticsLine) {
        if ($analyticsLine === $analyticsData) {
            $analyticsFound = true;
            break;
        }
    }

    // Wenn der String nicht gefunden wurde, diesen hinzufügen
    if (!$analyticsFound) {
        $analyticsHandle = fopen($analyticsFilename, 'a');
        fwrite($analyticsHandle, $analyticsData . "\n");
        fclose($analyticsHandle);
    }
}
?>