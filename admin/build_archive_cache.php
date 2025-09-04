<?php
/**
 * Build-Skript zur Generierung des Thumbnail-Caches für die Archivseite.
 * Dieses Skript sollte manuell ausgeführt werden, nachdem neue Comics/Thumbnails hinzugefügt wurden.
 * Es reduziert die Serverlast der archiv.php erheblich, indem es die Dateiprüfungen vorab durchführt.
 */

// === KONFIGURATION ===
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$outputCachePath = __DIR__ . '/../src/config/archive_cache.json';
$thumbnailBaseDir = __DIR__ . '/../assets/comic_thumbnails/';
$supportedExtensions = ['webp', 'jpg', 'png', 'gif'];

echo "<h1>Archiv-Cache-Generator</h1>";

// --- Schritt 1: Lade die Comic-Daten ---
if (!file_exists($comicVarJsonPath)) {
    die("<p style='color:red;'>FEHLER: comic_var.json nicht gefunden unter: " . htmlspecialchars($comicVarJsonPath) . "</p>");
}
$comicData = json_decode(file_get_contents($comicVarJsonPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("<p style='color:red;'>FEHLER: Konnte comic_var.json nicht dekodieren: " . json_last_error_msg() . "</p>");
}
echo "<p>1. Comic-Daten erfolgreich geladen (" . count($comicData) . " Einträge gefunden).</p>";

// --- Schritt 2: Durchlaufe alle Comics und finde die Thumbnails ---
$thumbnailCache = [];
$foundCount = 0;
$notFoundCount = 0;

foreach ($comicData as $comicId => $details) {
    $foundImagePath = null;
    foreach ($supportedExtensions as $ext) {
        $potentialImagePath = "{$thumbnailBaseDir}{$comicId}.{$ext}";
        if (file_exists($potentialImagePath)) {
            // Wir speichern den Pfad relativ zum Projekt-Root, damit er in archiv.php direkt verwendet werden kann.
            $foundImagePath = "assets/comic_thumbnails/{$comicId}.{$ext}";
            break;
        }
    }

    if ($foundImagePath) {
        $thumbnailCache[$comicId] = $foundImagePath;
        $foundCount++;
    } else {
        // Optional: Logge Comics ohne Thumbnail
        // error_log("Kein Thumbnail für Comic-ID gefunden: " . $comicId);
        $notFoundCount++;
    }
}
echo "<p>2. Thumbnails durchsucht: <strong>" . $foundCount . " gefunden</strong>, " . $notFoundCount . " nicht gefunden.</p>";

// --- Schritt 3: Speichere den Cache als JSON-Datei ---
// JSON_PRETTY_PRINT macht die Datei lesbarer
$jsonOutput = json_encode($thumbnailCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($outputCachePath, $jsonOutput) !== false) {
    echo "<p style='color:green; font-weight:bold;'>3. Erfolg! Die Cache-Datei wurde erfolgreich erstellt unter:</p>";
    echo "<pre>" . htmlspecialchars($outputCachePath) . "</pre>";
    echo "<p><strong>WICHTIG:</strong> Du kannst dieses Skript nun vom Server löschen oder den Zugriff darauf beschränken.</p>";
} else {
    echo "<p style='color:red;'>FEHLER: Konnte die Cache-Datei nicht schreiben nach: " . htmlspecialchars($outputCachePath) . "</p>";
}
?>