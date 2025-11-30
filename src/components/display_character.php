<?php
/**
 * Dieses Modul zeigt die Charaktere an, die auf einer bestimmten Comic-Seite vorkommen.
 * Die Charaktere werden nach den Gruppen und der Reihenfolge aus charaktere.json sortiert.
 * Die Gruppen-Überschriften werden dynamisch aus der charaktere.json geladen.
 * 
 * @file      ROOT/src/components/display_character.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   3.0.3
 * @since     1.3.2 Entfernt das feste Mapping und liest Gruppennamen dynamisch aus.
 * @since     1.4.0 Fügt eine Variable hinzu, um die Hauptüberschrift optional auszublenden.
 * @since     1.4.1 Korrigiert den Link, sodass er auf die individuelle Charakter-PHP-Seite verweist.
 * @since     2.0.0 Umstellung auf das neue ID-basierte Charaktersystem.
 * @since     2.1.0 Stellt die Link-Struktur auf individuelle PHP-Dateien (/charaktere/Trace.php) wieder her.
 * @since     2.1.1 Code-Bereinigung und Korrektur des Dateipfads im Doc-Block.
 * @since     3.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     3.0.1 BUG-FIX Charakter-Profilbild-URL angepasst an die neue Konfiguration.
 * @since     3.0.2 BUG-FIX Leerzeichen in URLs werden nun korrekt durch Unterstriche ersetzt statt durch Plus-Zeichen.
 * @since     3.0.3 Layout-Änderung: Name wird nun unter dem Bild angezeigt (DOM-Reihenfolge getauscht).
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

$charaktereData = [];

// Lade und verarbeite die Charakterdaten nur einmal mit der Path-Klasse
$charaktereJsonPath = Path::getDataPath('charaktere.json');
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedData = json_decode($charaktereJsonContent, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($decodedData['characters']) && isset($decodedData['groups'])) {
        $charaktereData = $decodedData;
    }
}

// Hole die Liste der Charakter-IDs für die aktuelle Seite aus den Comic-Daten.
$pageCharaktereIDs = $comicData[$currentComicId]['charaktere'] ?? [];

// Wenn keine Charaktere für diese Seite definiert sind oder die Charakter-Daten fehlen, zeige nichts an.
if (!empty($pageCharaktereIDs) && !empty($charaktereData)):

    // Standardwert für die Sichtbarkeit der Überschrift.
    if (!isset($showCharacterSectionTitle)) {
        $showCharacterSectionTitle = true;
    }
    ?>

    <div class="comic-characters">
        <?php if ($showCharacterSectionTitle): ?>
            <h3>Charaktere auf dieser Seite:</h3>
        <?php endif; ?>
        <div class="character-list">
            <?php
            foreach ($charaktereData['groups'] as $groupName => $char_id_list):
                $idsToShowInGroup = array_intersect($char_id_list, $pageCharaktereIDs);

                if (!empty($idsToShowInGroup)):
                    ?>
                    <div class="character-group">
                        <h4><?php echo htmlspecialchars($groupName); ?></h4>
                        <div class="character-group-list">
                            <?php
                            foreach ($char_id_list as $char_id):
                                if (in_array($char_id, $idsToShowInGroup)):
                                    $characterDetails = $charaktereData['characters'][$char_id] ?? null;

                                    if ($characterDetails):
                                        $characterName = $characterDetails['name'];
                                        
                                        // FIX 3.0.2: Ersetze Leerzeichen durch Unterstriche für den Dateinamen
                                        $filename = str_replace(' ', '_', $characterName);
                                        
                                        // Link mit DIRECTORY_PUBLIC_URL erstellen
                                        $characterLink = DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $filename . $dateiendungPHP;

                                        $imageSrc = 'https://placehold.co/80x80/cccccc/333333?text=Bild%0Afehlt';
                                        if (!empty($characterDetails['pic_url'])) {
                                            // Bild-URL mit DIRECTORY_PUBLIC_URL erstellen
                                            $imageSrc = DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES_URL . '/' . htmlspecialchars($characterDetails['pic_url']);
                                        }
                                        ?>
                                        <div class="character-item">
                                            <a href="<?php echo htmlspecialchars($characterLink); ?>" target="_blank" rel="noopener noreferrer"
                                                title="Mehr über <?php echo htmlspecialchars($characterName); ?> erfahren">
                                                <!-- FIX 3.0.3: Bild zuerst, dann Name -->
                                                <img src="<?php echo htmlspecialchars($imageSrc); ?>"
                                                    alt="Bild von <?php echo htmlspecialchars($characterName); ?>" loading="lazy" width="80"
                                                    height="80" class="character-image-fallback">
                                                <span class="character-name"><?php echo htmlspecialchars($characterName); ?></span>
                                            </a>
                                        </div>
                                        <?php
                                    endif;
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