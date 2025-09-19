<?php
/**
 * Dieses Modul zeigt die Charaktere an, die auf einer bestimmten Comic-Seite vorkommen.
 * V1.2: Verbessert die Platzhalter für fehlende Bilder.
 */

// Pfad zur Charakter-Definitionsdatei
$charaktereJsonPath = __DIR__ . '/../config/charaktere.json';
$allCharaktereData = [];

// Lade und verarbeite die Charakterdaten nur einmal
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedCharaktere = json_decode($charaktereJsonContent, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedCharaktere)) {
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

if (!empty($comicCharacters) && !empty($allCharaktereData)):
    ?>
    <div class="comic-characters">
        <h3>Charaktere auf dieser Seite:</h3>
        <div class="character-list">
            <?php foreach ($comicCharacters as $characterName): ?>
                <?php
                $imageUrl = $allCharaktereData[$characterName]['charaktere_pic_url'] ?? null;
                $characterLink = $baseUrl . 'charaktere/' . rawurlencode($characterName) . '.php';

                // NEUE LOGIK: Unterscheide zwischen "nicht eingetragen" und "potenziell fehlend"
                $imageSrc = '';
                $imageClass = '';
                if (empty($imageUrl)) {
                    // Kein Pfad in der JSON -> '?'
                    $imageSrc = 'https://placehold.co/80x80/cccccc/333333?text=?';
                    $imageClass = ''; // Kein Fallback-Listener nötig
                } else {
                    // Pfad ist vorhanden, aber Datei könnte fehlen -> 'Fehlt'
                    $imageSrc = $baseUrl . $imageUrl;
                    $imageClass = 'character-image-fallback'; // Fallback-Listener wird aktiv
                }
                ?>
                <div class="character-item">
                    <a href="<?php echo htmlspecialchars($characterLink); ?>" target="_blank" rel="noopener noreferrer"
                        title="Mehr über <?php echo htmlspecialchars($characterName); ?> erfahren">
                        <span
                            class="character-name"><?php echo htmlspecialchars(str_replace('_', ' ', $characterName)); ?></span>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>"
                            alt="Bild von <?php echo htmlspecialchars($characterName); ?>" loading="lazy" width="80" height="80"
                            class="<?php echo $imageClass; ?>">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script nonce="<?php echo htmlspecialchars($nonce ?? ''); ?>">
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.character-image-fallback').forEach(function (img) {
                img.addEventListener('error', function () {
                    this.onerror = null;
                    // NEU: Zeigt "Fehlt" an, wenn die Datei nicht geladen werden kann
                    this.src = 'https://placehold.co/80x80/cccccc/333333?text=Fehlt';
                });
            });
        });
    </script>
<?php endif; ?>