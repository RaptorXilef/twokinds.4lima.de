<?php

/**
 * Administrationsseite zum Bearbeiten der charaktere.json.
 *
 * @file      ROOT/public/admin/data_editor_charaktere.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 2.0.0 - 4.0.0
 * - Architektur & Datenstruktur:
 *  - Vollständige Umstellung auf eindeutige Charakter-IDs (Schema v2) und Trennung von Stammdaten/Gruppenzuweisung.
 *  - Implementierung der dynamischen Path-Helfer-Klasse, zentraler Pfad-Konstanten und CSRF-Fixes (FormData).
 *  - Automatische Verwaltung (Erstellen/Löschen/Inhalt-Korrektur) der PHP-Dateien im /comic/-Ordner.
 *
 * - Benutzeroberfläche (UI) & UX:
 *  - Neues visuelles Multi-Select-Grid (mit Bildern) und Drag & Drop-Sortierung für Gruppen.
 *  - Duplizierung der Aktionsbuttons und Feedback-Boxen (oben/unten) für bessere Erreichbarkeit.
 *  - Implementierung von Pagination, "Sticky Footer"-Modals und 'C'-Status-Tag für Zuweisungen.
 *  - Komfort-Funktionen: Auto-Scroll/Highlight nach Bearbeitung, kein Auto-Reload, Bearbeiten von Gruppennamen.
 *  - Editor-Vereinfachung: Einfaches Textfeld statt Summernote.
 *
 * - Stabilität & Fixes:
 *  - CSP-konformes Fallback für Bilder (Event-Listener), Unterstützung für Leerzeichen in Namen.
 *  - Diverse Bugfixes: Theme-Styling (Hell/Dunkel), Hamburger-Icon, Lösch-Logik und JS-Referenzen.
 *
 * @since 5.0.0
 * - refactor(UI): Komplettes Redesign basierend auf SCSS 7-1 Architektur (keine Inline-Styles).
 * - refactor(Layout): Nutzung der Standard-Admin-Container (#settings-and-actions-container).
 * - refactor(Modal): Umstellung auf .modal-advanced-layout (Sticky Header/Footer).
 * - feat(JS): Modernes ES6+ JavaScript, fetch API und verbesserte Fehlerbehandlung.
 * - fix(Security): Konsequente Nutzung von Output-Escaping und Nonces.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json' für Timestamps.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> data_editor_charaktere).
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// HINWEIS: Der Pfad zu den Charakter-PHP-Seiten wird direkt aus der DIRECTORY_PUBLIC_CHARAKTERE-Konstante abgeleitet.
$charakterePhpPath = DIRECTORY_PUBLIC_CHARAKTERE . DIRECTORY_SEPARATOR;

/**
 * Berechnet den relativen Pfad von einem Start- zu einem Zielpfad.
 *
 * @param string $from Der absolute Startpfad (Verzeichnis).
 * @param string $to Der absolute Zielpfad (Datei).
 * @return string Der berechnete relative Pfad.
 */
function getRelativePath(string $from, string $to): string
{
    $from = str_replace('\\', '/', $from);
    $to = str_replace('\\', '/', $to);
    $fromArr = explode('/', rtrim($from, '/'));
    $toArr = explode('/', rtrim($to, '/'));
    $toFilename = array_pop($toArr); // Dateinamen für später aufheben

    while (count($fromArr) && count($toArr) && ($fromArr[0] == $toArr[0])) {
        array_shift($fromArr);
        array_shift($toArr);
    }
    $relativePath = str_repeat('../', count($fromArr));
    $relativePath .= implode('/', $toArr);
    $relativePath .= ($relativePath ? '/' : '') . $toFilename;
    return $relativePath;
}

// --- Einstellungsverwaltung (Standardisiert) ---
function loadGeneratorSettings(string $filePath, string $username, bool $debugMode): array
{
    $defaults = [
        'last_run_timestamp' => null,
    ];

    if (!file_exists($filePath)) {
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, json_encode(['users' => []], JSON_PRETTY_PRINT));
        return $defaults;
    }

    $content = file_get_contents($filePath);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        if ($debugMode) {
            error_log("[Charakter Editor] Config JSON korrupt oder leer. Lade Defaults.");
        }
        return $defaults;
    }

    $userSettings = $data['users'][$username]['data_editor_charaktere'] ?? [];
    return array_replace_recursive($defaults, $userSettings);
}

function saveGeneratorSettings(string $filePath, string $username, array $settings, bool $debugMode): bool
{
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $data = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data = $decoded;
        }
    }

    if (!isset($data['users'])) {
        $data['users'] = [];
    }
    if (!isset($data['users'][$username])) {
        $data['users'][$username] = [];
    }

    $currentGeneratorSettings = $data['users'][$username]['data_editor_charaktere'] ?? [];
    $data['users'][$username]['data_editor_charaktere'] = array_replace_recursive($currentGeneratorSettings, $settings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function loadJsonData(string $path): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// === AJAX HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_end_clean();
    header('Content-Type: application/json');

    verify_csrf_token();

    $inputDataStr = $_POST['characterData'] ?? '{}';
    $inputData = json_decode($inputDataStr, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($inputData) && isset($inputData['characters'], $inputData['groups'])) {
        $charaktereJsonPath = Path::getDataPath('charaktere.json');
        $currentData = loadJsonData($charaktereJsonPath);
        $currentCharObjects = $currentData['characters'] ?? [];
        $deletedCount = 0;
        $renamedCount = 0;
        $createdCount = 0;

        // 1. Gelöschte Charaktere finden und PHP-Dateien entfernen
        $deletedIds = array_diff_key($currentCharObjects, $inputData['characters']);
        foreach ($deletedIds as $charId => $charObject) {
            $fileName = str_replace(' ', '_', $charObject['name']) . '.php';
            $filePath = $charakterePhpPath . $fileName;
            if (!file_exists($filePath) || !unlink($filePath)) {
                continue;
            }

            $deletedCount++;
        }

        // 2. Umbenennungen und Neuerstellungen
        foreach ($inputData['characters'] as $charId => $charObject) {
            $newName = $charObject['name'];
            if (isset($currentCharObjects[$charId])) {
                // Existierender Charakter: Prüfe auf Namensänderung
                $oldName = $currentCharObjects[$charId]['name'];
                if ($oldName !== $newName) {
                    $oldFilePath = $charakterePhpPath . str_replace(' ', '_', $oldName) . '.php';
                    $newFilePath = $charakterePhpPath . str_replace(' ', '_', $newName) . '.php';
                    if (file_exists($oldFilePath) && is_writable(dirname($oldFilePath)) && rename($oldFilePath, $newFilePath)) {
                        $renamedCount++;
                    } elseif (!file_exists($oldFilePath)) {
                        // Falls die alte Datei fehlte, erstelle die neue einfach
                        $filePath = $charakterePhpPath . str_replace(' ', '_', $newName) . '.php';
                        $createdCount++; // Zählen wir hier dazu
                        // Logik siehe unten
                    }
                }
            } else {
                // Neuer Charakter: Erstelle PHP Datei
                $filePath = $charakterePhpPath . str_replace(' ', '_', $newName) . '.php';
                if (!file_exists($filePath)) {
                    $relativePathCharacterPageRenderer = getRelativePath(DIRECTORY_PUBLIC_CHARAKTERE, DIRECTORY_PRIVATE_RENDERER . DIRECTORY_SEPARATOR . 'renderer_character_page.php');
                    $phpContent = "<?php require_once __DIR__ . '/" . $relativePathCharacterPageRenderer . "'; ?>";
                    if (file_put_contents($filePath, $phpContent) !== false) {
                        $createdCount++;
                    }
                }
            }
        }

        // 3. JSON Speichern
        if (file_put_contents($charaktereJsonPath, json_encode($inputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))) {
            // 4. Einstellungen (Zeitstempel) aktualisieren
            $settingsData = [
                'last_run_timestamp' => time(),
            ];
            saveGeneratorSettings($configPath, $currentUser, $settingsData, $debugMode);

            $message = "Charakter-Daten erfolgreich gespeichert.";
            if ($createdCount > 0) {
                $message .= " $createdCount PHP-Datei(en) erstellt.";
            }
            if ($deletedCount > 0) {
                $message .= " $deletedCount PHP-Datei(en) gelöscht.";
            }
            if ($renamedCount > 0) {
                $message .= " $renamedCount PHP-Datei(en) umbenannt.";
            }

            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Fehler beim Schreiben der charaktere.json.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige JSON-Daten empfangen.']);
    }
    exit;
}

// === VIEW RENDERING ===
$allCharaktereData = loadJsonData(Path::getDataPath('charaktere.json'));

// Zeitstempel aus den zentralen Settings laden
$charSettings = loadGeneratorSettings($configPath, $currentUser, $debugMode);
$lastSavedTimestamp = $charSettings['last_run_timestamp'];

$pageTitle = 'Adminbereich - Charakter-Datenbank Editor';
$pageHeader = 'Charakter-Datenbank Editor';
$robotsContent = 'noindex, nofollow';
$bodyClass = 'admin-page';

// SortableJS laden
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>';

require_once Path::getPartialTemplatePath('header.php');
?>

<div class="content-section">
    <!-- UI HEADER & ACTIONS -->
    <div id="settings-and-actions-container">
        <div id="last-run-container">
            <?php if ($lastSavedTimestamp) : ?>
                <p class="status-message status-info">Letzte Speicherung am
                    <?php echo date('d.m.Y \u\m H:i:s', $lastSavedTimestamp); ?> Uhr.
                </p>
            <?php else : ?>
                <p class="status-message status-orange">Noch keine Speicherung erfasst.</p>
            <?php endif; ?>
        </div>
        <h2>Charakter-Datenbank Editor</h2>
        <p>Verwalte hier Charakter-Stammdaten und gruppiere sie per Drag & Drop. Die Reihenfolge in den Gruppen bestimmt die Anzeige auf der Webseite.</p>
    </div>

    <div id="message-box" class="hidden-by-default"></div>

    <!-- 1. CHARAKTER STAMMDATEN -->
    <div class="collapsible-section expanded">
        <div class="collapsible-header">
            <h3>1. Charakter-Stammdaten</h3>
            <div class="group-actions">
                <button class="button add-character-btn" title="Neuen Charakter anlegen"><i class="fas fa-plus"></i> Neu</button>
            </div>
        </div>
        <div class="collapsible-content">
            <p class="instructions">Hier sind alle verfügbaren Charaktere aufgelistet. Neue Charaktere müssen hier erstellt werden, bevor sie Gruppen zugewiesen werden können.</p>
            <div id="character-master-list" class="master-list-container">
                <!-- JS-generierter Inhalt -->
            </div>
        </div>
    </div>

    <!-- 2. GRUPPEN ZUWEISUNG -->
    <div class="collapsible-section expanded">
        <div class="collapsible-header">
            <h3>2. Gruppen-Zuweisung & Sortierung</h3>
            <div class="group-actions">
                <button class="button add-group-btn" title="Neue Gruppe hinzufügen"><i class="fas fa-folder-plus"></i> Neue Gruppe</button>
            </div>
        </div>
        <div class="collapsible-content">
            <p class="instructions">Ziehe Charaktere zwischen Gruppen hin und her oder sortiere sie innerhalb einer Gruppe. Nutze das "+" Icon in der Gruppenleiste für Massenzuweisungen.</p>
            <div id="character-groups-container" class="char-editor-container">
                <!-- JS-generierter Inhalt -->
            </div>
        </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div id="fixed-buttons-container">
        <button id="save-all-btn" class="button button-green"><i class="fas fa-save"></i> Alle Änderungen speichern</button>
    </div>

    <br>

    <div class="editor-footer-info">
        <p><small><strong>Info:</strong> Die URL eines Charakters wird automatisch aus dem Namen generiert. Leerzeichen werden durch Unterstriche (`_`) ersetzt. Bei Namensänderung wird die zugehörige PHP-Datei automatisch umbenannt.</small></p>
    </div>
</div>

<!-- Modal 1: Charakter Bearbeiten (Advanced Layout) -->
<div id="edit-char-modal" class="modal hidden-by-default">
    <div class="modal-content modal-advanced-layout">
        <!-- HEADER -->
        <div class="modal-header-wrapper">
            <h2 id="modal-title">Charakter bearbeiten</h2>
            <button class="modal-close close-button" aria-label="Schließen">&times;</button>
        </div>

        <!-- FORMULAR (SCROLLBAR) -->
        <div class="modal-scroll-content">
            <form id="edit-form" class="admin-form">
                <input type="hidden" id="modal-char-id">

                <div class="form-group">
                    <label for="modal-id-display">System-ID:</label>
                    <input type="text" id="modal-id-display" disabled class="disabled-input">
                </div>

                <div class="form-group">
                    <label for="modal-name">Charakter-Name (Pflichtfeld):</label>
                    <input type="text" id="modal-name" required placeholder="z.B. Trace Legacy">
                    <small>Wird für die URL und die Anzeige verwendet. Muss eindeutig sein.</small>
                </div>

                <div class="form-group">
                    <label for="modal-pic-url">Bild-Dateiname:</label>
                    <input type="text" id="modal-pic-url" placeholder="z.B. Trace.webp">
                    <small>Dateiname aus <code><?php echo DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES; ?></code></small>
                </div>

                <div class="form-group preview-container">
                    <label>Bild-Vorschau:</label>
                    <div>
                        <img id="modal-image-preview" src="https://placehold.co/100x100/cccccc/333333?text=?" alt="Vorschau">
                    </div>
                </div>

                <div class="form-group">
                    <label for="modal-description">Interne Beschreibung / Notiz:</label>
                    <textarea id="modal-description" rows="4"></textarea>
                </div>
            </form>
        </div>

        <!-- FOOTER ACTIONS -->
        <div class="modal-footer-actions">
            <div class="modal-buttons">
                <button type="button" id="modal-save-btn" class="button button-green">Übernehmen</button>
                <button type="button" class="button delete cancel-btn">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Zur Gruppe Hinzufügen (Advanced Layout) -->
<div id="add-to-group-modal" class="modal hidden-by-default">
    <div class="modal-content modal-advanced-layout">
        <!-- HEADER -->
        <div class="modal-header-wrapper">
            <h2 id="add-group-modal-title">Zur Gruppe hinzufügen</h2>
            <button class="modal-close close-button" aria-label="Schließen">&times;</button>
        </div>

        <!-- SCROLL CONTENT -->
        <div class="modal-scroll-content">
            <form id="add-to-group-form">
                <input type="hidden" id="add-group-name">
                <p>Wähle einen oder mehrere Charaktere aus, um sie der Gruppe hinzuzufügen:</p>
                <div id="char-selection-grid" class="char-selection-grid">
                    <!-- JS generiert hier die Items -->
                </div>
            </form>
        </div>

        <!-- FOOTER ACTIONS -->
        <div class="modal-footer-actions">
            <div class="modal-buttons">
                <button type="button" id="modal-add-group-save-btn" class="button button-green">Ausgewählte hinzufügen</button>
                <button type="button" class="button delete cancel-btn">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        // Daten Initialisierung (Sicher encodiert)
        let characterData = <?php echo json_encode($allCharaktereData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

        // Fallback Struktur, falls JSON leer/defekt
        if (!characterData || typeof characterData !== 'object') characterData = {};
        if (!characterData.characters) characterData.characters = {};
        if (!characterData.groups) characterData.groups = {};

        const baseUrl = '<?php echo DIRECTORY_PUBLIC_URL; ?>';
        const charProfileUrlBase = '<?php echo Url::getImgCharactersProfilesUrl(''); ?>';
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';

        // UI Referenzen
        const masterListContainer = document.getElementById('character-master-list');
        const groupsContainer = document.getElementById('character-groups-container');
        const saveAllBtn = document.getElementById('save-all-btn');
        const messageBox = document.getElementById('message-box');
        const lastRunContainer = document.getElementById('last-run-container');

        // Modals
        const editModal = document.getElementById('edit-char-modal');
        const addToGroupModal = document.getElementById('add-to-group-modal');
        const editForm = document.getElementById('edit-form');
        const charSelectionGrid = document.getElementById('char-selection-grid');

        // Platzhalter
        const placeholderUrl = 'https://placehold.co/80x80/cccccc/333333?text=Bild?';
        const errorUrl = 'https://placehold.co/80x80/dc3545/ffffff?text=Fehlt';

        // State
        let selectedCharsForGroup = new Set();

        // --- RENDER FUNKTIONEN ---

        const getSortedCharacters = () => {
            return Object.entries(characterData.characters).sort(([, a], [, b]) => {
                return (a.name || '').localeCompare(b.name || '', 'de', {
                    sensitivity: 'base'
                });
            });
        };

        const renderMasterList = () => {
            masterListContainer.innerHTML = '';
            const sortedChars = getSortedCharacters();

            if (sortedChars.length === 0) {
                masterListContainer.innerHTML = '<p class="empty-table-message">Keine Charaktere gefunden.</p>';
                return;
            }

            sortedChars.forEach(([id, char]) => {
                const entryDiv = document.createElement('div');
                entryDiv.className = 'master-char-entry';
                entryDiv.dataset.charId = id;

                const imgSrc = char.pic_url ? `${charProfileUrlBase}/${char.pic_url}` : placeholderUrl;

                entryDiv.innerHTML = `
                    <div>
                        <img src="${imgSrc}" alt="Charakterbild" loading="lazy">
                        <strong>${escapeHtml(char.name)}</strong>
                        <small>ID: ${id}</small>
                    </div>
                    <div class="character-actions">
                        <button type="button" class="button edit edit-master-btn" title="Bearbeiten"><i class="fas fa-edit"></i></button>
                        <button type="button" class="button delete delete-master-btn" title="Löschen"><i class="fas fa-trash-alt"></i></button>
                    </div>`;
                masterListContainer.appendChild(entryDiv);
            });
        };

        const renderGroups = () => {
            const groupOrder = Object.keys(characterData.groups);
            groupsContainer.innerHTML = '';

            // Haupt-Container sortierbar machen (Gruppen verschieben)
            new Sortable(groupsContainer, {
                animation: 150,
                handle: 'h3', // Nur am Header greifbar
                ghostClass: 'sortable-ghost'
            });

            if (groupOrder.length === 0) {
                groupsContainer.innerHTML = '<p class="empty-table-message">Keine Gruppen definiert.</p>';
                return;
            }

            groupOrder.forEach(groupName => {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'character-group';
                groupDiv.dataset.groupName = groupName;

                groupDiv.innerHTML = `
                <div class="character-group-header">
                    <h3>${escapeHtml(groupName)}</h3>
                    <div class="group-actions">
                        <button type="button" class="button edit edit-group-btn" title="Gruppe umbenennen"><i class="fas fa-pen"></i></button>
                        <button type="button" class="button add add-char-to-group-btn" title="Charaktere hinzufügen"><i class="fas fa-plus"></i></button>
                        <button type="button" class="button delete delete-group-btn" title="Gruppe löschen"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
                <div class="character-list-container"></div>`;

                const listContainer = groupDiv.querySelector('.character-list-container');
                const groupChars = characterData.groups[groupName] || [];

                if (groupChars.length === 0) {
                    listContainer.innerHTML = '<div class="char-list-empty">(Leer)</div>';
                }

                groupChars.forEach(charId => {
                    const char = characterData.characters[charId];
                    // Fallback für gelöschte IDs in Gruppen
                    const displayName = char ? char.name : `<span class="char-id-invalid">[ID ungültig: ${charId}]</span>`;
                    const picUrl = char ? char.pic_url : '';
                    const imgSrc = picUrl ? `${charProfileUrlBase}/${picUrl}` : placeholderUrl;

                    const charEntry = document.createElement('div');
                    charEntry.className = 'character-entry';
                    charEntry.dataset.charId = charId;
                    charEntry.innerHTML = `
                    <img src="${imgSrc}" alt="Avatar" loading="lazy">
                    <div class="character-info">
                        <strong>${displayName}</strong>
                        <span class="char-id-display">${charId}</span>
                    </div>
                    <div class="character-actions">
                        <button type="button" class="button delete remove-char-btn" title="Aus Gruppe entfernen"><i class="fas fa-times"></i></button>
                    </div>`;
                    listContainer.appendChild(charEntry);
                });

                groupsContainer.appendChild(groupDiv);

                // Sortable für die Liste IN der Gruppe (Drag & Drop zwischen Gruppen)
                new Sortable(listContainer, {
                    animation: 150,
                    group: 'shared-chars', // Erlaubt Dragging zwischen Listen
                    ghostClass: 'sortable-ghost'
                });
            });
        };

        // --- HELPER ---

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        const handleImageError = (event) => {
            if (event.target.tagName === 'IMG') {
                // Verhindert Endlosschleife, wenn Placeholder auch fehlt
                if (event.target.src !== errorUrl && event.target.src !== placeholderUrl) {
                    event.target.src = errorUrl;
                }
            }
        };
        // Globaler Listener für Image Errors im Admin-Container
        document.querySelector('.content-section').addEventListener('error', handleImageError, true);
        document.getElementById('char-selection-grid').addEventListener('error', handleImageError, true);

        function showMessage(message, type, duration = 5000) {
            messageBox.textContent = message;
            messageBox.className = `status-message status-${type} visible`;
            messageBox.style.display = 'block';
            setTimeout(() => {
                messageBox.style.display = 'none';
                messageBox.classList.remove('visible');
            }, duration);
        }

        function updateTimestamp() {
            const now = new Date();
            const str = `Letzte Speicherung am ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr.`;
            if (lastRunContainer) {
                let p = lastRunContainer.querySelector('p');
                if(!p) {
                    p = document.createElement('p');
                    p.className = 'status-message status-info';
                    lastRunContainer.appendChild(p);
                }
                p.textContent = str;
            }
        }

        // --- MODAL LOGIK ---

        const openEditModal = (charId = null) => {
            editForm.reset();
            const idDisplay = document.getElementById('modal-id-display');
            const title = document.getElementById('modal-title');

            if (charId) {
                const char = characterData.characters[charId];
                if (!char) return; // Sicherheitscheck
                title.textContent = 'Charakter bearbeiten';
                document.getElementById('modal-char-id').value = charId;
                idDisplay.value = charId;
                document.getElementById('modal-name').value = char.name;
                document.getElementById('modal-pic-url').value = char.pic_url || '';
                document.getElementById('modal-description').value = char.description || '';
            } else {
                const newId = 'char_' + Date.now();
                title.textContent = 'Neuen Charakter anlegen';
                document.getElementById('modal-char-id').value = newId;
                idDisplay.value = newId;
            }
            updateImagePreview();
            editModal.style.display = 'flex';
        };

        const updateImagePreview = () => {
            const path = document.getElementById('modal-pic-url').value;
            const preview = document.getElementById('modal-image-preview');
            preview.src = path ? `${charProfileUrlBase}/${path}` : placeholderUrl;
        };

        // Event Listener für Bild-Input
        document.getElementById('modal-pic-url').addEventListener('input', updateImagePreview);

        const openAddToGroupModal = (groupName) => {
            document.getElementById('add-group-name').value = groupName;
            document.getElementById('add-group-modal-title').textContent = `Zu Gruppe "${groupName}" hinzufügen`;

            // Grid leeren und neu befüllen
            charSelectionGrid.innerHTML = '';
            selectedCharsForGroup.clear();

            const charsInGroup = new Set(characterData.groups[groupName] || []);
            let countAvailable = 0;

            getSortedCharacters().forEach(([id, char]) => {
                // Nur anzeigen, wenn noch nicht in der Gruppe
                if (!charsInGroup.has(id)) {
                    countAvailable++;
                    const item = document.createElement('div');
                    item.className = 'char-selection-item';
                    item.dataset.charId = id;

                    const imgSrc = char.pic_url ? `${charProfileUrlBase}/${char.pic_url}` : placeholderUrl;

                    item.innerHTML = `
                        <img src="${imgSrc}" alt="${escapeHtml(char.name)}" loading="lazy">
                        <span>${escapeHtml(char.name)}</span>
                    `;

                    item.addEventListener('click', () => {
                        if (selectedCharsForGroup.has(id)) {
                            selectedCharsForGroup.delete(id);
                            item.classList.remove('selected');
                        } else {
                            selectedCharsForGroup.add(id);
                            item.classList.add('selected');
                        }
                    });

                    charSelectionGrid.appendChild(item);
                }
            });

            if (countAvailable === 0) {
                charSelectionGrid.innerHTML = '<p class="empty-table-message">Alle verfügbaren Charaktere sind bereits in dieser Gruppe.</p>';
            }

            addToGroupModal.style.display = 'flex';
        };

        // --- BUTTON HANDLERS ---

        // Event Delegation für dynamische Elemente
        document.querySelector('.content-section').addEventListener('click', e => {
            // Master List Buttons
            if (e.target.closest('.add-character-btn')) openEditModal();

            const editMasterBtn = e.target.closest('.edit-master-btn');
            if (editMasterBtn) openEditModal(editMasterBtn.closest('.master-char-entry').dataset.charId);

            const deleteMasterBtn = e.target.closest('.delete-master-btn');
            if (deleteMasterBtn) {
                const entry = deleteMasterBtn.closest('.master-char-entry');
                const charName = entry.querySelector('strong').textContent;
                if (confirm(`"${charName}" wirklich endgültig löschen? Er wird aus ALLEN Gruppen entfernt.`)) {
                    delete characterData.characters[entry.dataset.charId];
                    // Auch aus allen Gruppen entfernen
                    Object.keys(characterData.groups).forEach(key => {
                        characterData.groups[key] = characterData.groups[key].filter(id => id !== entry.dataset.charId);
                    });
                    renderMasterList();
                    renderGroups();
                    showMessage('Charakter gelöscht. "Speichern" nicht vergessen!', 'orange');
                }
            }

            // Group Buttons
            if (e.target.closest('.add-group-btn')) {
                const name = prompt("Name der neuen Gruppe:");
                if (name && name.trim()) {
                    const cleanName = name.trim();
                    if (characterData.groups[cleanName] === undefined) {
                        characterData.groups[cleanName] = [];
                        renderGroups();
                        showMessage('Gruppe erstellt.', 'info');
                    } else alert("Gruppe existiert bereits.");
                }
            }

            const editGroupBtn = e.target.closest('.edit-group-btn');
            if (editGroupBtn) {
                const groupDiv = editGroupBtn.closest('.character-group');
                const oldName = groupDiv.dataset.groupName;
                const newName = prompt("Neuen Namen für die Gruppe eingeben:", oldName);

                if (newName && newName.trim() && newName.trim() !== oldName) {
                    const cleanNewName = newName.trim();
                    if (characterData.groups.hasOwnProperty(cleanNewName)) {
                        alert("Eine Gruppe mit diesem Namen existiert bereits.");
                        return;
                    }
                    // Reihenfolge erhalten: Neues Objekt bauen
                    const newGroups = {};
                    Object.keys(characterData.groups).forEach(key => {
                        if (key === oldName) {
                            newGroups[cleanNewName] = characterData.groups[oldName];
                        } else {
                            newGroups[key] = characterData.groups[key];
                        }
                    });
                    characterData.groups = newGroups;
                    renderGroups();
                    showMessage('Gruppe umbenannt.', 'info');
                }
            }

            const deleteGroupBtn = e.target.closest('.delete-group-btn');
            if (deleteGroupBtn) {
                const groupDiv = deleteGroupBtn.closest('.character-group');
                if (confirm(`Gruppe "${groupDiv.dataset.groupName}" wirklich löschen? Die Charaktere selbst bleiben erhalten.`)) {
                    delete characterData.groups[groupDiv.dataset.groupName];
                    renderGroups();
                    showMessage('Gruppe gelöscht.', 'orange');
                }
            }

            const addCharToGroupBtn = e.target.closest('.add-char-to-group-btn');
            if (addCharToGroupBtn) {
                openAddToGroupModal(addCharToGroupBtn.closest('.character-group').dataset.groupName);
            }

            const removeCharBtn = e.target.closest('.remove-char-btn');
            if (removeCharBtn) {
                if (confirm('Charakter wirklich aus dieser Gruppe entfernen?')) {
                    const entry = removeCharBtn.closest('.character-entry');
                    const groupName = entry.closest('.character-group').dataset.groupName;
                    const charId = entry.dataset.charId;

                    if (characterData.groups[groupName]) {
                        characterData.groups[groupName] = characterData.groups[groupName].filter(id => id !== charId);
                        renderGroups();
                        showMessage('Charakter aus Gruppe entfernt.', 'info');
                    }
                }
            }
        });

        // Modal Save Handlers
        document.getElementById('modal-save-btn').addEventListener('click', () => {
            const id = document.getElementById('modal-char-id').value;
            const name = document.getElementById('modal-name').value.trim();
            const pic_url = document.getElementById('modal-pic-url').value.trim();
            const description = document.getElementById('modal-description').value.trim();

            if (!name) return alert('Name darf nicht leer sein.');

            // Dubletten Check
            const isDuplicate = Object.entries(characterData.characters).some(([charId, char]) =>
                char.name.toLowerCase() === name.toLowerCase() && charId !== id
            );

            if (isDuplicate) return alert('Ein Charakter mit diesem Namen existiert bereits.');

            characterData.characters[id] = {
                name,
                pic_url,
                description
            };

            renderMasterList();
            // Auch Gruppen neu rendern, da sich Namen/Bilder geändert haben könnten
            renderGroups();

            editModal.style.display = 'none';
            showMessage('Charakterdaten übernommen. "Speichern" nicht vergessen!', 'info');
        });

        document.getElementById('modal-add-group-save-btn').addEventListener('click', () => {
            const groupName = document.getElementById('add-group-name').value;

            if (groupName && characterData.groups[groupName]) {
                if (selectedCharsForGroup.size > 0) {
                    selectedCharsForGroup.forEach(charId => {
                        if (!characterData.groups[groupName].includes(charId)) {
                            characterData.groups[groupName].push(charId);
                        }
                    });
                    renderGroups();
                    showMessage(`${selectedCharsForGroup.size} Charakter(e) hinzugefügt. "Speichern" nicht vergessen!`, 'info');
                }
            }
            addToGroupModal.style.display = 'none';
        });

        // Close Handlers für alle Modals
        document.querySelectorAll('.modal .close-button, .modal .cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal').style.display = 'none';
            });
        });

        // SAVE ALL (AJAX)
        saveAllBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            saveAllBtn.disabled = true;
            saveAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichere...';

            const dataToSave = {
                schema_version: 2,
                characters: {
                    ...characterData.characters
                },
                groups: {}
            };

            // Reihenfolge aus DOM übernehmen (wegen Drag & Drop)
            document.querySelectorAll('#character-groups-container .character-group').forEach(groupDiv => {
                const groupName = groupDiv.dataset.groupName;
                const charIdsInGroup = [];
                groupDiv.querySelectorAll('.character-entry').forEach(entry => {
                    charIdsInGroup.push(entry.dataset.charId);
                });
                dataToSave.groups[groupName] = charIdsInGroup;
            });

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('characterData', JSON.stringify(dataToSave));

                const response = await fetch('', { // Post an sich selbst
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                try {
                    const data = JSON.parse(responseText);
                    if (response.ok && data.success) {
                        characterData = dataToSave; // State update
                        showMessage(data.message, 'green');
                        updateTimestamp(); // UI Update
                    } else {
                        showMessage(data.message || 'Ein Fehler ist aufgetreten.', 'red');
                    }
                } catch (e) {
                    console.error('JSON Error:', e, responseText);
                    throw new Error(`Ungültige Antwort vom Server.`);
                }
            } catch (error) {
                showMessage(`Netzwerkfehler: ${error.message}`, 'red');
            } finally {
                saveAllBtn.disabled = false;
                saveAllBtn.innerHTML = '<i class="fas fa-save"></i> Alle Änderungen speichern';
            }
        });

        // Initialisierung
        if (characterData && characterData.schema_version >= 2) {
            renderMasterList();
            renderGroups();
        } else {
            groupsContainer.innerHTML = '<p class="status-message status-red"><strong>Fehler:</strong> Die <code>charaktere.json</code> hat ein veraltetes Format oder fehlt.</p>';
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
