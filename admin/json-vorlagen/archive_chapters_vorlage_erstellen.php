<?php

/**
 * Dieses Skript liest die 'archive_chapters.json', leert die Werte der Schlüssel
 * 'title' und 'description' für jeden Eintrag und speichert das Ergebnis
 * in einer neuen JSON-Datei als Vorlage.
 */

// ============== KONFIGURATION ==============
// Pfad zur Quelldatei
$sourceFilePath = '../../src/config/archive_chapters.json';

// Pfad zur Zieldatei
$destinationFilePath = 'archive_chapters.json';
// ===========================================


// Schritt 1: Prüfen, ob die Quelldatei existiert und lesbar ist.
if (!is_readable($sourceFilePath)) {
    die("FEHLER: Die Quelldatei '$sourceFilePath' konnte nicht gefunden oder gelesen werden.");
}

// Schritt 2: Den Inhalt der JSON-Datei als String einlesen.
$jsonContent = file_get_contents($sourceFilePath);

// Schritt 3: Den JSON-String in ein assoziatives PHP-Array umwandeln.
$data = json_decode($jsonContent, true);

// Schritt 4: Prüfen, ob beim Parsen der JSON-Daten ein Fehler aufgetreten ist.
if (json_last_error() !== JSON_ERROR_NONE) {
    die("FEHLER: Die Datei '$sourceFilePath' enthält kein valides JSON. Fehler: " . json_last_error_msg());
}

// Schritt 5: Durch jedes Kapitel im Array iterieren.
// Die Referenz (&) sorgt dafür, dass das Original-Array direkt geändert wird.
foreach ($data as &$chapter) {
    // Prüfen, ob der Schlüssel 'title' existiert und ihn dann leeren.
    if (isset($chapter['title'])) {
        $chapter['title'] = '';
    }

    // Prüfen, ob der Schlüssel 'description' existiert und ihn dann leeren.
    if (isset($chapter['description'])) {
        $chapter['description'] = '';
    }
}
// Die Referenz nach der Schleife aufheben, um Nebeneffekte zu vermeiden.
unset($chapter);

// Schritt 6: Das modifizierte PHP-Array zurück in einen formatierten JSON-String umwandeln.
$newJsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Schritt 7: Den neuen JSON-String in die Zieldatei schreiben.
$result = file_put_contents($destinationFilePath, $newJsonContent);

// Schritt 8: Eine abschließende Erfolgs- oder Fehlermeldung ausgeben.
if ($result === false) {
    echo "FEHLER: Die Zieldatei '$destinationFilePath' konnte nicht geschrieben werden. Überprüfe die Berechtigungen.";
} else {
    echo "Erfolgreich! Die Datei '$destinationFilePath' wurde erstellt.";
}

?>