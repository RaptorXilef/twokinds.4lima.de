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

// 1. Daten laden (unverändert aus deinem Original)
$charaktereJsonPath = Path::getDataPath('charaktere.json');
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedData = json_decode($charaktereJsonContent, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decodedData['characters']) && isset($decodedData['groups'])) {
        $charaktereData = $decodedData;
    }
}

$pageCharaktereIDs = $comicData[$currentComicId]['charaktere'] ?? [];

if (!empty($pageCharaktereIDs) && !empty($charaktereData)) :
    $showCharacterSectionTitle = $showCharacterSectionTitle ?? true;
    $useCharacterTags = $useCharacterTags ?? false; // Flag für die Comicseite
    ?>

    <div class="comic-characters">
        <?php if ($showCharacterSectionTitle) : ?>
            <h3>Charaktere auf dieser Seite:</h3>
        <?php endif; ?>

        <?php if ($useCharacterTags) :
            // --- MODUS: TAGS (KEINE DUBLETTEN, SORTIERT) ---

            // 1. Identifiziere Hauptcharaktere anhand der Gruppe in der JSON
            $mainCharIds = $charaktereData['groups']['Hauptcharaktere'] ?? [];

            $mainList = [];
            $otherList = [];

            // 2. Charaktere in zwei Töpfe sortieren
            foreach ($pageCharaktereIDs as $id) {
                if (!isset($charaktereData['characters'][$id])) {
                    continue;
                }
                $char = $charaktereData['characters'][$id];
                $char['id'] = $id;

                if (in_array($id, $mainCharIds)) {
                    $mainList[] = $char;
                } else {
                    $otherList[] = $char;
                }
            }

            // 3. Alphabetische Sortierung innerhalb der Töpfe
            $sortFunc = fn($a, $b) => strcasecmp($a['name'], $b['name']);
            usort($mainList, $sortFunc);
            usort($otherList, $sortFunc);

            // 4. Zusammenführen (Hauptcharaktere oben)
            $finalList = array_merge($mainList, $otherList);

            // 5. Rollen-Mapping für Tags sammeln
            $tagsMap = [];
            foreach ($charaktereData['groups'] as $groupName => $idList) {
                foreach ($idList as $id) {
                    if (!in_array($id, $pageCharaktereIDs)) {
                        continue;
                    }

                    $tagsMap[$id][] = $groupName;
                }
            }
            ?>

            <div class="character-group-list">
                <?php foreach ($finalList as $char) :
                    $charId = $char['id'];
                    $characterName = $char['name'];
                    $filename = str_replace(' ', '_', $characterName);
                    $characterLink = DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $filename . $dateiendungPHP;
                    $imageSrc = !empty($char['pic_url'])
                        ? DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES_URL . '/' . htmlspecialchars($char['pic_url'])
                        : 'https://placehold.co/80x80/cccccc/333333?text=Bild%0Afehlt';
                    ?>
                    <div class="character-item">
                        <a href="<?= htmlspecialchars($characterLink); ?>" target="_blank" rel="noopener noreferrer"
                           title="Mehr über <?= htmlspecialchars($characterName); ?> erfahren">
                            <img src="<?= htmlspecialchars($imageSrc); ?>"
                                 alt="Bild von <?= htmlspecialchars($characterName); ?>"
                                 class="character-image-fallback" width="80" height="80" loading="lazy">
                            <span class="character-name"><?= htmlspecialchars($characterName); ?></span>
                        </a>

                        <div class="character-tags">
                            <?php if (isset($tagsMap[$charId])) :
                                foreach ($tagsMap[$charId] as $tagName) : ?>
                                    <span class="char-tag"><?= htmlspecialchars($tagName); ?></span>
                                <?php endforeach;
                            endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            <div class="character-list">
                <?php foreach ($charaktereData['groups'] as $groupName => $idList) :
                    $idsInPage = array_intersect($idList, $pageCharaktereIDs);
                    if (!empty($idsInPage)) : ?>
                        <div class="character-group">
                            <h4><?= htmlspecialchars($groupName); ?></h4>
                            <div class="character-group-list">
                                <?php foreach ($idList as $id) :
                                    if (in_array($id, $idsInPage)) :
                                        $c = $charaktereData['characters'][$id] ?? null;
                                        if (!$c) {
                                            continue;
                                        }
                                        $charName = $c['name'];
                                        $file = str_replace(' ', '_', $charName);
                                        $link = DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $file . $dateiendungPHP;
                                        $img = !empty($c['pic_url']) ? DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES_URL . '/' . htmlspecialchars($c['pic_url']) : 'https://placehold.co/80x80/cccccc/333333?text=Fehlt';
                                        ?>
                                        <div class="character-item">
                                            <a href="<?= htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer">
                                                <img src="<?= htmlspecialchars($img); ?>" class="character-image-fallback" width="80" height="80">
                                                <span class="character-name"><?= htmlspecialchars($charName); ?></span>
                                            </a>
                                        </div>
                                    <?php endif;
                                endforeach; ?>
                            </div>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
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
    <?php
endif; ?>
