<?php
// src/admin/archive_generator/data_loader.php

// Pfad zur JSON-Datei mit den Archivvariablen
$jsonFilePath = '../../data/generator_archive_vars.json';

// Überprüfen, ob die Datei existiert
if (!file_exists($jsonFilePath)) {
    die("Fehler: Die Datei $jsonFilePath wurde nicht gefunden.");
}

// Inhalt der JSON-Datei lesen
$jsonData = file_get_contents($jsonFilePath);

// JSON-Daten in ein PHP-Array dekodieren
$archiveData = json_decode($jsonData, true);

// Überprüfen, ob das Dekodieren erfolgreich war und die Daten gültig sind
if (json_last_error() !== JSON_ERROR_NONE || !isset($archiveData[$archiveNumberIdInput])) {
    die("Fehler: Ungültige JSON-Daten oder Archivnummer ($archiveNumberIdInput) nicht gefunden in $jsonFilePath.");
}

// Die spezifischen Daten für die aktuelle archiveNumberIdInput abrufen
$currentArchiveData = $archiveData[$archiveNumberIdInput];

// Variablen aus den JSON-Daten extrahieren
$startupPictures = $currentArchiveData['startupPictures'];
$pictureStartNumber = $currentArchiveData['pictureStartNumber'];
$headings = $currentArchiveData['headings'];

// Optional: Anzeigen der geladenen Daten zu Debugging-Zwecken
// echo '<pre>';
// print_r($currentArchiveData);
// echo '</pre>';
?>