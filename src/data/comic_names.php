<?php
// src/data/comic_names.php (als Beispiel, wo die JSON-Daten geladen werden könnten)

// Pfad zur JSON-Datei mit den Comicnamen
$jsonComicNamesPath = __DIR__ . '/comic_names.json'; // __DIR__ stellt sicher, dass der Pfad relativ zur aktuellen Datei ist

// Überprüfen, ob die Datei existiert
if (!file_exists($jsonComicNamesPath)) {
    die("Fehler: Die Datei $jsonComicNamesPath wurde nicht gefunden.");
}

// Inhalt der JSON-Datei lesen
$jsonComicData = file_get_contents($jsonComicNamesPath);

// JSON-Daten in ein PHP-Array dekodieren
$comicNames = json_decode($jsonComicData, true);

// Überprüfen, ob das Dekodieren erfolgreich war
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Fehler beim Dekodieren der JSON-Datei: " . json_last_error_msg());
}

// Optional: Zur einfacheren Handhabung könntest du die Daten in ein assoziatives Array umwandeln,
// bei dem das Datum der Schlüssel ist, ähnlich wie es in comicnamen.php war.
$comicNamesIndexedByDate = [];
foreach ($comicNames as $comic) {
    $comicNamesIndexedByDate[$comic['date']] = [
        'type' => $comic['type'],
        'name' => $comic['name']
    ];
}

// Jetzt kannst du auf die Comicnamen zugreifen, z.B. $comicNamesIndexedByDate['20250312']['name']

// Wahr mit einer Wahrscheinlichkeit von 100%
// Quelle: Eigene Logik basierend auf der Dateianforderung.
?>