<?php
/**
 * Dieses Modul zeigt die Charaktere an, die auf einer bestimmten Comic-Seite vorkommen.
 * Die Charaktere werden nach den Gruppen und der Reihenfolge aus charaktere.json sortiert.
 * Die Gruppen-Überschriften werden dynamisch aus der charaktere.json geladen.
 * * @file      /src/components/character_display.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.3.2
 * @since     1.3.0 Fügt Sortierung und Gruppierung gemäß charaktere.json hinzu.
 * @since     1.3.1 Korrigiert die Schlüssel für die Gruppenzuordnung.
 * @since     1.3.2 Entfernt das feste Mapping und liest Gruppennamen dynamisch aus.
 */

// Pfad zur Charakter-Definitionsdatei
$charaktereJsonPath = __DIR__ . '/../config/charaktere.json';
$allCharaktereData = [];
$decodedCharaktere = [];

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
        $decodedCharaktere = []; // Sicherstellen, dass es ein leeres Array ist, wenn JSON fehlerhaft ist
    }
}

// Hole die Liste der Charaktere für die aktuelle Seite aus den Comic-Daten.
$pageCharaktere = $comicData[$currentComicId]['charaktere'] ?? [];

// Wenn keine Charaktere für diese Seite definiert sind oder die Charakter-Daten fehlen, zeige nichts an.
if (!empty($pageCharaktere) && !empty($decodedCharaktere)):
    ?>

    <div class="comic-characters">
        <h3>Charaktere auf dieser Seite:</h3>
        <div class="character-list">
            <?php
            // Das feste Mapping-Array für Überschriften wurde entfernt.
            // Die Überschriften werden jetzt direkt aus den Schlüsseln der JSON-Datei genommen.
        
            // Iteriere durch die Gruppen in der Reihenfolge, wie sie in charaktere.json definiert sind
            foreach ($decodedCharaktere as $groupName => $charactersInGroup):
                // Finde heraus, welche Charaktere aus dieser Gruppe auf der aktuellen Seite sind
                $charactersToShowInGroup = array_intersect(array_keys($charactersInGroup), $pageCharaktere);

                // Wenn in dieser Gruppe Charaktere angezeigt werden sollen, erstelle den Gruppen-Container
                if (!empty($charactersToShowInGroup)):
                    ?>
                    <div class="character-group">
                        <h4><?php echo htmlspecialchars($groupName); ?></h4>
                        <div class="character-group-list">
                            <?php
                            // Iteriere nun durch die Charaktere in der Reihenfolge von charaktere.json
                            foreach ($charactersInGroup as $characterName => $characterDetails):
                                // Zeige den Charakter nur an, wenn er auf dieser Seite vorkommen soll
                                if (in_array($characterName, $charactersToShowInGroup)):
                                    $characterData = $allCharaktereData[$characterName] ?? null;
                                    $characterLink = $baseUrl . 'charaktere#' . urlencode($characterName);

                                    $imageSrc = 'https://placehold.co/80x80/cccccc/333333?text=Bild%0Afehlt'; // Standard-Platzhalter
                                    $imageClass = '';

                                    if ($characterData && !empty($characterData['charaktere_pic_url'])) {
                                        $imageSrc = $baseUrl . htmlspecialchars($characterData['charaktere_pic_url']);
                                        $imageClass = 'character-image-fallback';
                                    }
                                    ?>
                                    <div class="character-item">
                                        <a href="<?php echo htmlspecialchars($characterLink); ?>" target="_blank" rel="noopener noreferrer"
                                            title="Mehr über <?php echo htmlspecialchars($characterName); ?> erfahren">
                                            <span
                                                class="character-name"><?php echo htmlspecialchars(str_replace('_', ' ', $characterName)); ?></span>
                                            <img src="<?php echo htmlspecialchars($imageSrc); ?>"
                                                alt="Bild von <?php echo htmlspecialchars($characterName); ?>" loading="lazy" width="80"
                                                height="80" class="<?php echo $imageClass; ?>">
                                        </a>
                                    </div>
                                <?php
                                endif;
                            endforeach;
                            ?>
                                                </div>
                                                </div>
                                    <?php
                endif;
            endforeach;
            ?>
        </div>
    </div>

    <script nonce="<?php echo htmlspecialchars($nonce ?? ''); ?>">
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.character-image-fallback').forEach(function (img) {
                img.addEventListener('error', function () {
                    this.onerror = null;
                    this.src = 'https://placehold.co/80x80/cccccc/333333?text=Bild%0AFehlt';
                });
            });
        });
    </script>
<?php endif; ?>