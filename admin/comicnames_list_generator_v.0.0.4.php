<?php
/*
 * Dieses Skript generiert eine PHP-Datei mit Variablennamen für Comicnamen.
 * Es liest alle JPG-, GIF- und PNG-Dateien in einem angegebenen Verzeichnis und erstellt für jeden Dateinamen eine entsprechende Variable in der PHP-Datei.
 * Die erstellte PHP-Datei wird im "include"-Verzeichnis abgelegt.
 * Falls bereits eine Datei mit dem gleichen Namen existiert, wird eine nummerierte Version erstellt.
 * Am Ende werden Erfolgsmeldungen mit dem erstellten Dateinamen ausgegeben.
 */

session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt
echo '</br> <<------- Bitte wählen Sie eine Aktion im Menü auf der rechten Seite aus!';



$folderForGeneratingComicnameFiles = "../includes/"; // Pfad zum erstellen der Comicnamensliste (relativ zum Stammverzeichnis)
$imageFolderForGeneratingPHPfiles = "../comic/"; // Pfad zum Ordner mit den Bildern (relativ zum Stammverzeichnis)
$verzeichnis = opendir($imageFolderForGeneratingPHPfiles); // Ordner öffnen



$variablen = array(); // Array für Variablennamen

// Alle Dateien im Ordner durchgehen
while (($datei = readdir($verzeichnis)) !== false) {
    if (!is_dir($datei)) { // Nur Dateien berücksichtigen, keine Ordner
        $dateiinfo = pathinfo($datei);
        $dateiname = $dateiinfo['filename']; // Dateinamen ohne Erweiterung

        // Nur JPG-, GIF- und PNG-Dateien berücksichtigen
        if ($dateiinfo['extension'] == 'jpg' || $dateiinfo['extension'] == 'png' || $dateiinfo['extension'] == 'gif') {
            $variablen[] = $dateiname; // Variablennamen hinzufügen
        }
    }
}

closedir($verzeichnis); // Ordner schließen

sort($variablen); // Variablennamen alphabetisch sortieren

// Variablen für die Nummerierung der Datei erstellen
$dateiIndex = 0;
$phpdatei = '';

// Überprüfen, ob die Datei comicnamen.php oder bereits nummerierte Versionen existieren
while (file_exists($folderForGeneratingComicnameFiles . "comicnamen" . ($dateiIndex === 0 ? "" : $dateiIndex) . ".php")) {
    $dateiIndex++;
}

// Variablennamen in die PHP-Datei schreiben
$phpdatei = fopen($folderForGeneratingComicnameFiles . "comicnamen" . ($dateiIndex === 0 ? "" : $dateiIndex) . ".php", "w");

foreach ($variablen as $variablenname) {
    fwrite($phpdatei, "<?php $" . "comicTypInput" . $variablenname . " = 'Comicseite vom '; $" . "comicNameInput" . $variablenname . " = '';?>\n");
}

fclose($phpdatei);

// Meldungen ausgeben
echo '<h1 style="color: green; font-weight: bold;">Generator für die Comicnamen-Liste gestartet</h1>';
echo '<h2 style="color: green; font-weight: bold;">Vorgang erfolgreich beendet</h2>';
echo '<p>Erstellte Datei: ' . $folderForGeneratingComicnameFiles . 'comicnamen' . ($dateiIndex === 0 ? "" : $dateiIndex) . '.php</p>';



require_once('includes/design/footer.php');
?>
