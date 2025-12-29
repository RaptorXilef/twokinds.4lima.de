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

// 1. Daten laden (unverändert)
$charaktereJsonPath = Path::getDataPath('charaktere.json');
if (file_exists($charaktereJsonPath)) {
    $charaktereJsonContent = file_get_contents($charaktereJsonPath);
    $decodedData = json_decode($charaktereJsonContent, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decodedData['characters']) && isset($decodedData['groups'])) {
        $charaktereData = $decodedData;
    }
}

$pageCharaktereIDs = $comicData[$currentComicId]['charaktere'] ?? [];

// Prüfen, ob wir uns auf einer Comicseite befinden (für den Umschalter)
$isInteractiveComicPage = (isset($isComicPage) && $isComicPage);

if (!empty($pageCharaktereIDs) && !empty($charaktereData)) :
    $showCharacterSectionTitle = $showCharacterSectionTitle ?? true;

    // Standard-Ansicht festlegen (z.B. Tag-Modus als Standard für Comicseiten)
    $activeMode = $isInteractiveComicPage ? 'tags' : 'grouped';
    ?>

    <div class="comic-characters-container <?= $isInteractiveComicPage ? 'interactive' : ''; ?>" id="char-display-wrapper" data-active-view="<?= $activeMode; ?>">

        <div class="char-display-header">
            <div class="header-spacer"></div>

            <?php if ($showCharacterSectionTitle) : ?>
                <h3>Charaktere auf dieser Seite:</h3>
            <?php endif; ?>

            <?php if ($isInteractiveComicPage) : ?>
                <button type="button" id="toggle-char-view" class="button button-small" title="Ansicht umschalten">
                    <i class="fas fa-th-list"></i> <span id="toggle-view-text">Gruppierte Ansicht</span>
                </button>
            <?php else : ?>
                <div class="header-spacer"></div>
            <?php endif; ?>
        </div>

        <div class="char-view-section view-tags">
            <div class="character-group-list">
                <?php
                    $mainCharIds = $charaktereData['groups']['Hauptcharaktere'] ?? [];
                    $mainList = [];
                    $otherList = [];

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

                    $sortFunc = fn($a, $b) => strcasecmp($a['name'], $b['name']);
                    usort($mainList, $sortFunc);
                    usort($otherList, $sortFunc);
                    $finalList = array_merge($mainList, $otherList);

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
                        $filename = str_replace(' ', '_', $char['name']);
                        $link = DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $filename . $dateiendungPHP;
                        $img = !empty($char['pic_url']) ? DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES_URL . '/' . htmlspecialchars($char['pic_url']) : 'https://placehold.co/80x80/cccccc/333333?text=Fehlt';
                        ?>
                        <div class="character-item">
                            <a href="<?= htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer">
                                <img src="<?= htmlspecialchars($img); ?>" class="character-image-fallback" width="80" height="80">
                                <span class="character-name"><?= htmlspecialchars($char['name']); ?></span>
                            </a>
                            <div class="character-tags">
                                <?php foreach ($tagsMap[$char['id']] as $tagName) : ?>
                                    <span class="char-tag"><?= htmlspecialchars($tagName); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="char-view-section view-grouped">
            <div class="character-list">
                <?php foreach ($charaktereData['groups'] as $groupName => $idList) :
                    $idsInPage = array_intersect($idList, $pageCharaktereIDs);
                    if (!empty($idsInPage)) : ?>
                        <div class="character-group">
                            <h4><?= htmlspecialchars($groupName); ?></h4>
                            <div class="character-group-list">
                                <?php foreach ($idList as $id) :
                                    if (in_array($id, $idsInPage)) :
                                        $c = $charaktereData['characters'][$id];
                                        $file = str_replace(' ', '_', $c['name']);
                                        $link = DIRECTORY_PUBLIC_CHARAKTERE_URL . '/' . $file . $dateiendungPHP;
                                        $img = !empty($c['pic_url']) ? DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES_URL . '/' . htmlspecialchars($c['pic_url']) : 'https://placehold.co/80x80/cccccc/333333?text=Fehlt';
                                        ?>
                                        <div class="character-item">
                                            <a href="<?= htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer">
                                                <img src="<?= htmlspecialchars($img); ?>" class="character-image-fallback" width="80" height="80">
                                                <span class="character-name"><?= htmlspecialchars($c['name']); ?></span>
                                            </a>
                                        </div>
                                    <?php endif;
                                endforeach; ?>
                            </div>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>
        </div>
    </div>

    <script nonce="<?= htmlspecialchars($nonce ?? ''); ?>">
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('char-display-wrapper');
            const toggleBtn = document.getElementById('toggle-char-view');
            const toggleText = document.getElementById('toggle-view-text');

            if (toggleBtn && wrapper) {
                toggleBtn.addEventListener('click', function() {
                    const currentView = wrapper.getAttribute('data-active-view');
                    const newView = currentView === 'tags' ? 'grouped' : 'tags';

                    wrapper.setAttribute('data-active-view', newView);

                    // Button-Text und Icon aktualisieren
                    if (newView === 'grouped') {
                        toggleText.textContent = 'Tag Ansicht';
                        toggleBtn.querySelector('i').className = 'fas fa-tags';
                    } else {
                        toggleText.textContent = 'Gruppierte Ansicht';
                        toggleBtn.querySelector('i').className = 'fas fa-th-list';
                    }
                });
            }

            // Image-Fallback (unverändert)
            document.querySelectorAll('.character-image-fallback').forEach(function (img) {
                img.addEventListener('error', function () {
                    this.onerror = null;
                    this.src = 'https://placehold.co/80x80/cccccc/333333?text=Bild%0AFehlt';
                });
            });
        });
    </script>
<?php endif; ?>
