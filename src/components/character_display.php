<?php
/**
 * Dieses Modul zeigt die Charaktere an, die auf einer bestimmten Comic-Seite vorkommen.
 * Es liest die Charakterdaten aus comic_var.json und die Bild-URLs aus charaktere.json.
 *
 * Benötigt, dass $currentComicId, $comicData und $baseUrl im aufrufenden Skript verfügbar sind.
 */

// Pfad zur Charakter-Definitionsdatei
$charaktereJsonPath = __DIR__ . '/../config/charaktere.json';
$allCharaktereData = [];

// Lade und verarbeite die Charakterdaten nur einmal
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedCharaktere = json_decode($charaktereJsonContent, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedCharaktere)) {
        // Fasse alle Charaktergruppen (main, secondary, etc.) in einem einzigen Array zusammen
        // für eine einfachere Suche.
        foreach ($decodedCharaktere as $group) {
            if (is_array($group)) {
                $allCharaktereData = array_merge($allCharaktereData, $group);
            }
        }
    } else {
        error_log("Fehler beim Dekodieren von charaktere.json: " . json_last_error_msg());
    }
} else {
    error_log("Fehler: charaktere.json wurde nicht gefunden unter " . $charaktereJsonPath);
}


// Hole die Charaktere für den aktuellen Comic
$comicCharacters = $comicData[$currentComicId]['charaktere'] ?? [];

// Zeige den Bereich nur an, wenn Charaktere vorhanden sind und die Daten geladen werden konnten
if (!empty($comicCharacters) && !empty($allCharaktereData)):
    ?>
    <div class="comic-characters">
        <h3>Charaktere auf dieser Seite:</h3>
        <div class="character-list">
            <?php foreach ($comicCharacters as $characterName): ?>
                <?php
                // Finde die Bild-URL für den aktuellen Charakter
                $imageUrl = $allCharaktereData[$characterName]['charaktere_pic_url'] ?? null;
                if ($imageUrl):
                    // Erstelle den vollständigen, relativen Pfad zum Bild
                    $fullImagePath = $baseUrl . $imageUrl;
                    // Erstelle den Link zur Charakter-Seite
                    $characterLink = $baseUrl . 'charaktere/' . rawurlencode($characterName) . '.php';
                    ?>
                    <div class="character-item">
                        <a href="<?php echo htmlspecialchars($characterLink); ?>" target="_blank" rel="noopener noreferrer"
                            title="Mehr über <?php echo htmlspecialchars($characterName); ?> erfahren">
                            <span
                                class="character-name"><?php echo htmlspecialchars(str_replace('_', ' ', $characterName)); ?></span>
                            <img src="<?php echo htmlspecialchars($fullImagePath); ?>"
                                alt="Bild von <?php echo htmlspecialchars($characterName); ?>" loading="lazy" width="80"
                                height="80">
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>