<?php

/**
 * Lädt Comic-Metadaten aus einer JSON-Datei.
 * Stellt die Daten als assoziatives PHP-Array zur Verfügung.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
/* $debugMode = false; */

// Pfad zur JSON-Datei
$comicDataPath = __DIR__ . '/../config/comic_var.json';

// Überprüfen, ob die JSON-Datei existiert
if (file_exists($comicDataPath)) {
    // Inhalt der JSON-Datei lesen
    $jsonContent = file_get_contents($comicDataPath);

    // JSON-Inhalt in ein PHP-Array dekodieren
    $comicData = json_decode($jsonContent, true);

    // Überprüfen, ob das Dekodieren erfolgreich war
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        $comicData = []; // Leeres Array im Fehlerfall
    }
    // Sortiere die Comic-Daten nach Datum (dem Schlüssel), um die Navigation zu erleichtern
    ksort($comicData);
} else {
    if ($debugMode)
        error_log("Fehler: comic_var.json wurde nicht gefunden unter " . $comicDataPath);
    $comicData = []; // Leeres Array, wenn Datei nicht existiert
}

?>