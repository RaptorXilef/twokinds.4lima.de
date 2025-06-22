<?php
// Pfad zur alten comicnamen.php
$phpFilePath = '../../includes/comicnamen.php'; // Passe dies an, falls nötig

// Pfad zur neuen JSON-Datei
$jsonFilePath = '../../src/data/comic_names.json';

// Array, um die Comic-Daten zu speichern
$comicData = [];

// Alte PHP-Datei einlesen
if (file_exists($phpFilePath)) {
    include $phpFilePath; // Lädt die alten Variablen

    // Durch alle Variablen gehen und sie in das neue Format bringen
    foreach (get_defined_vars() as $key => $value) {
        if (strpos($key, 'comicTypInput') === 0) {
            $date = substr(str_replace('comicTypInput', '', $key), 0, 8); // Extrahiert das Datum
            $nameKey = str_replace('comicTypInput', 'comicNameInput', $key); // Erzeugt den zugehörigen Namens-Key
            $name = isset($$nameKey) ? $$nameKey : ''; // Holt den Comic-Namen, falls vorhanden

            $comicData[] = [
                'date' => $date,
                'type' => $value,
                'name' => $name,
            ];
        }
    }

    // JSON erstellen und in die Datei schreiben
    $jsonData = json_encode($comicData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // Schöner formatiert und mit Unicode
    if (file_put_contents($jsonFilePath, $jsonData)) {
        echo "Die Datei '$phpFilePath' wurde erfolgreich in '$jsonFilePath' konvertiert.";
    } else {
        echo "FEHLER: Die Datei '$jsonFilePath' konnte nicht geschrieben werden.";
    }
} else {
    echo "FEHLER: Die Datei '$phpFilePath' wurde nicht gefunden.";
}
?>