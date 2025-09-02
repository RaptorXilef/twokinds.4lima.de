<?php

/**
 * Dieses Skript liest eine JSON-Datei, leert den Wert des Schlüssels 'transcript'
 * für jeden Eintrag und speichert das Ergebnis in einer neuen JSON-Datei.
 */

// ============== KONFIGURATION ==============
// Gib hier den Pfad zur Quelldatei an.
$sourceFilePath = '../../src/config/comic_var.json';

// Gib hier den Pfad zur Zieldatei an.
$destinationFilePath = 'comic_var.json';
// ===========================================


// Schritt 1: Prüfen, ob die Quelldatei existiert und lesbar ist.
if (!is_readable($sourceFilePath)) {
    // bricht die Ausführung ab und gibt eine Fehlermeldung aus.
    die("FEHLER: Die Quelldatei '$sourceFilePath' konnte nicht gefunden oder gelesen werden.");
}

// Schritt 2: Den Inhalt der JSON-Datei als String einlesen.
$jsonContent = file_get_contents($sourceFilePath);

// Schritt 3: Den JSON-String in ein assoziatives PHP-Array umwandeln.
// Der zweite Parameter `true` sorgt für die Umwandlung in ein Array statt ein Objekt.
$data = json_decode($jsonContent, true);

// Schritt 4: Prüfen, ob beim Parsen der JSON-Daten ein Fehler aufgetreten ist.
if (json_last_error() !== JSON_ERROR_NONE) {
    die("FEHLER: Die Datei '$sourceFilePath' enthält kein valides JSON. Fehler: " . json_last_error_msg());
}

// Schritt 5: Durch jedes Element des Arrays iterieren.
// Das kaufmännische Und (&) vor $entry erstellt eine Referenz,
// sodass Änderungen direkt im ursprünglichen $data-Array vorgenommen werden.
foreach ($data as &$entry) {
    // Prüfen, ob der Schlüssel 'transcript' im aktuellen Element existiert.
    if (isset($entry['transcript'])) {
        // Den Wert von 'transcript' auf einen leeren String setzen.
        $entry['transcript'] = '';
    }
}
// Die Referenz nach der Schleife aufheben, um unbeabsichtigte Nebeneffekte zu vermeiden.
unset($entry);

// Schritt 6: Das modifizierte PHP-Array zurück in einen formatierten JSON-String umwandeln.
// JSON_PRETTY_PRINT sorgt für eine saubere, menschenlesbare Formatierung mit Einrückungen.
// JSON_UNESCAPED_UNICODE stellt sicher, dass Sonderzeichen wie 'ü' nicht als '\u00fc' kodiert werden.
$newJsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Schritt 7: Den neuen JSON-String in die Zieldatei schreiben.
// file_put_contents erstellt die Datei, falls sie nicht existiert, oder überschreibt sie andernfalls.
$result = file_put_contents($destinationFilePath, $newJsonContent);

// Schritt 8: Eine abschließende Erfolgs- oder Fehlermeldung ausgeben.
if ($result === false) {
    echo "FEHLER: Die Zieldatei '$destinationFilePath' konnte nicht geschrieben werden. Überprüfe die Berechtigungen.";
} else {
    echo "Erfolgreich! Die Datei '$destinationFilePath' wurde erstellt.";
}

?>