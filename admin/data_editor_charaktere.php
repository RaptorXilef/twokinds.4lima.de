<?php
/**
 * Administrationsseite zum Bearbeiten der charaktere.json.
 *
 * @file      /admin/data_editor_charaktere.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.8.2
 * @since     2.3.0 Erlaubt Leerzeichen in Charakternamen und automatisiert das Erstellen/Löschen von Charakter-PHP-Dateien.
 * @since     2.3.1 UI-Anpassungen und Code-Refactoring für Konsistenz mit dem Comic-Daten-Editor.
 * @since     2.4.0 Wiederherstellung des ursprünglichen UI-Layouts und Integration neuer Features.
 * @since     2.4.1 Behebt einen Fehler, der das Bearbeiten von Charakternamen verhinderte.
 * @since     2.4.2 Behebt CSP-Fehler durch Ersetzen von 'onerror' durch Event-Listener.
 * @since     2.5.0 Finale Zusammenführung von Original-UI mit allen neuen Features und Bugfixes.
 * @since     2.6.0 Entfernt Steckbrief-URL, erstellt/aktualisiert index.php für Charaktere, behebt Positions-Bug nach Bearbeitung.
 * @since     2.6.1 Entfernt die automatische Erstellung und Aktualisierung der Charakter-Übersichtsseite (index.php).
 * @since     2.7.0 Entfernt das automatische Neuladen nach dem Speichern für eine bessere Benutzererfahrung.
 * @since     2.8.0 Implementiert Drag & Drop zum Sortieren der Charaktergruppen.
 * @since     2.8.1 Behebt zwei Fehler: Korrigiert das Drag-Handle-Icon vor Gruppentiteln und stellt die korrekten Bild-Platzhalter wieder her.
 * @since     2.8.2 Korrigiert die Darstellung des Hamburger-Icons durch Anpassung der CSS-Syntax.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$charaktereJsonPath = __DIR__ . '/../src/config/charaktere.json';
$charakterePhpPath = __DIR__ . '/../charaktere/';

// --- HELPER FUNKTIONEN ---
function loadCharakterJsonData(string $path): array
{
    if (!file_exists($path) || filesize($path) === 0)
        return [];
    $content = file_get_contents($path);
    if ($content === false)
        return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function get_all_char_names(array $data): array
{
    $names = [];
    foreach ($data as $categoryData) {
        if (is_array($categoryData)) {
            $names = array_merge($names, array_keys($categoryData));
        }
    }
    return array_unique($names);
}

function getCharPhpFiles(string $pagesDir): array
{
    $phpFiles = [];
    if (is_dir($pagesDir)) {
        $files = scandir($pagesDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'index.php') {
                $charName = str_replace('_', ' ', pathinfo($file, PATHINFO_FILENAME));
                $phpFiles[] = $charName;
            }
        }
    }
    return $phpFiles;
}

// --- DATENVERARBEITUNG BEI POST-REQUEST (VIA FETCH API) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_end_clean();
    header('Content-Type: application/json');

    $token = '';
    $headers = getallheaders();
    $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
    if (isset($normalizedHeaders['x-csrf-token'])) {
        $token = $normalizedHeaders['x-csrf-token'];
    }

    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF-Token-Validierung fehlgeschlagen.']);
        exit;
    }

    $inputData = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($inputData)) {
        $dataToSave = $inputData;

        $currentData = loadCharakterJsonData($charaktereJsonPath);
        $currentCharNames = get_all_char_names($currentData);
        $newCharNames = get_all_char_names($dataToSave);

        $deletedNames = array_diff($currentCharNames, $newCharNames);
        $deletedCount = 0;
        foreach ($deletedNames as $name) {
            $fileName = str_replace(' ', '_', $name) . '.php';
            $filePath = $charakterePhpPath . $fileName;
            if (file_exists($filePath) && unlink($filePath)) {
                $deletedCount++;
            }
        }

        $createdCount = 0;
        foreach ($newCharNames as $name) {
            $fileName = str_replace(' ', '_', $name) . '.php';
            $filePath = $charakterePhpPath . $fileName;
            if (!file_exists($filePath)) {
                $phpContent = "<?php require_once __DIR__ . '/../src/components/character_page_renderer.php'; ?>";
                if (file_put_contents($filePath, $phpContent) !== false) {
                    $createdCount++;
                }
            }
        }

        if (file_put_contents($charaktereJsonPath, json_encode($dataToSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            $message = "Charakter-Daten erfolgreich gespeichert.";
            if ($createdCount > 0)
                $message .= " $createdCount PHP-Datei(en) erstellt.";
            if ($deletedCount > 0)
                $message .= " $deletedCount PHP-Datei(en) gelöscht.";

            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Fehler beim Schreiben der JSON-Datei.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige JSON-Daten empfangen.']);
    }
    exit;
}

$allCharaktereData = loadCharakterJsonData($charaktereJsonPath);
$existingPhpFiles = getCharPhpFiles($charakterePhpPath);
$lastSavedTimestamp = file_exists($charaktereJsonPath) ? filemtime($charaktereJsonPath) : null;

$pageTitle = 'Charakter-Datenbank Editor';
$pageHeader = 'Charakter-Datenbank Editor';
$robotsContent = 'noindex, nofollow';
$bodyClass = 'admin-page';
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>';

include $headerPath;
?>

<div class="admin-container">
    <div id="last-run-container">
        <?php if ($lastSavedTimestamp): ?>
            <p class="status-message status-info">Letzte Speicherung am
                <?php echo date('d.m.Y \u\m H:i:s', $lastSavedTimestamp); ?> Uhr.
            </p>
        <?php endif; ?>
    </div>

    <div class="controls">
        <button class="button add-character-btn">Neuen Charakter hinzufügen</button>
    </div>

    <div id="character-editor-container" class="data-editor-container">
        <!-- JS-generierter Inhalt -->
    </div>

    <div class="controls bottom-controls">
        <button class="button add-character-btn">Neuen Charakter hinzufügen</button>
        <button id="save-all-btn" class="button save-button">Änderungen speichern</button>
    </div>

    <div class="editor-footer-info">
        <p><strong>Info:</strong> Die finale URL eines Charakters wird aus dem Namen generiert. Leerzeichen werden
            durch Unterstriche (`_`) ersetzt. Z.B. wird "Red Haired Guy" zu `Red_Haired_Guy.php`.</p>
        <div id="message-box" style="display: none;"></div>
    </div>
</div>

<!-- Modal -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modal-title">Charakter bearbeiten</h2>
        <form id="edit-form">
            <div class="form-group">
                <label for="modal-group">Charakter-Gruppe:</label>
                <select id="modal-group" name="group" required></select>
                <input type="text" id="modal-new-group" name="new_group" placeholder="Oder neue Gruppe eingeben...">
            </div>
            <div class="form-group">
                <label for="modal-name">Charakter-Name:</label>
                <input type="text" id="modal-name" name="name" required>
                <small>Interner Schlüssel (z.B. "Trace", "Red Haired Guy"). Leerzeichen sind erlaubt.</small>
            </div>
            <div class="form-group">
                <label for="modal-pic-url">Bild-Pfad:</label>
                <input type="text" id="modal-pic-url" name="charaktere_pic_url">
                <small>Relativer Pfad, z.B. "assets/img/charaktere/faces/icon_trace.gif"</small>
            </div>
            <div class="form-group preview-container">
                <label>Bild-Vorschau:</label>
                <img id="modal-image-preview" src="https://placehold.co/100x100/cccccc/333333?text=?"
                    alt="Charakter Vorschau">
            </div>
            <div class="modal-buttons">
                <button type="submit" id="modal-save-btn" class="button save-button">Speichern</button>
                <button type="button" id="modal-cancel-btn" class="button delete-button">Abbrechen</button>
            </div>
        </form>
    </div>
</div>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    /* Stile für Statusmeldungen */
    .status-message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
        border: 1px solid transparent;
    }

    .status-green {
        background-color: #dff0d8;
        border-color: #d6e9c6;
        color: #3c763d;
    }

    .status-red {
        background-color: #f2dede;
        border-color: #ebccd1;
        color: #a94442;
    }

    .status-info {
        background-color: #d9edf7;
        border-color: #bce8f1;
        color: #31708f;
    }

    #message-box {
        margin-top: 10px;
    }

    #last-run-container .status-message {
        margin-bottom: 20px;
    }

    .editor-footer-info {
        margin-top: 20px;
        border-top: 1px solid #ddd;
        padding-top: 15px;
    }

    .admin-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }

    .controls {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
    }

    .bottom-controls {
        margin-top: 20px;
        margin-bottom: 0;
    }

    .button {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
    }

    .save-button {
        background-color: #6c8f6c;
        color: white;
    }

    .save-button:hover {
        background-color: #85aa85;
    }

    .delete-button {
        background-color: #d9534f;
        color: white;
    }

    .delete-button:hover {
        background-color: #c9302c;
    }

    .character-group {
        margin-bottom: 25px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #fff;
    }

    .character-group h3 {
        background-color: #eee;
        padding: 10px;
        margin: 0;
        border-bottom: 1px solid #ddd;
        border-radius: 5px 5px 0 0;
        cursor: move;
    }

    .character-group h3::before {
        content: '\2630';
        margin-right: 10px;
        color: #999;
    }

    .character-entry {
        display: flex;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #eee;
        gap: 15px;
    }

    .character-entry:last-child {
        border-bottom: none;
    }

    .character-entry img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        background-color: #ccc;
    }

    .character-info {
        flex-grow: 1;
    }

    .character-info strong {
        font-size: 1.1em;
    }

    .character-info p {
        margin: 2px 0;
        color: #666;
        font-family: monospace;
        word-break: break-all;
    }

    .character-actions {
        display: flex;
        gap: 5px;
    }

    .sortable-ghost {
        opacity: 0.4;
        background: #c8ebfb;
    }

    .character-entry:hover {
        cursor: grab;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
    }

    .close-button {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    #modal-new-group {
        margin-top: 5px;
    }

    .form-group small {
        color: #888;
        font-size: 0.8em;
    }

    .preview-container img {
        max-width: 100px;
        max-height: 100px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-top: 5px;
    }

    .modal-buttons {
        text-align: right;
        margin-top: 20px;
    }

    .status-indicator {
        font-size: 0.8em;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: bold;
        color: white;
    }

    .status-green {
        background-color: #28a745;
    }

    .status-red {
        background-color: #dc3545;
    }

    .banner {
        z-index: 99 !important;
    }

    /* Dark Mode */
    body.theme-night .admin-container {
        background-color: #002B3C;
        color: #eee;
    }

    body.theme-night .character-group {
        background-color: #00425c;
        border-color: #2a6177;
    }

    body.theme-night .character-group h3 {
        background-color: #00334C;
        border-bottom-color: #2a6177;
    }

    body.theme-night .character-group h3::before {
        color: #888;
    }

    body.theme-night .character-entry {
        border-bottom-color: #2a6177;
    }

    body.theme-night .character-info p {
        color: #bbb;
    }

    body.theme-night .modal-content {
        background-color: #00425c;
        color: #fff;
    }

    body.theme-night .form-group input,
    body.theme-night .form-group select {
        background-color: #002B3C;
        border-color: #2a6177;
        color: #fff;
    }

    body.theme-night .form-group small {
        color: #aaa;
    }

    body.theme-night .preview-container img {
        border-color: #2a6177;
    }

    body.theme-night .close-button {
        color: #ccc;
    }

    body.theme-night .close-button:hover {
        color: #fff;
    }

    body.theme-night .editor-footer-info {
        border-top-color: #2a6177;
    }

    body.theme-night .status-green {
        background-color: #2a6177;
        border-color: #3c763d;
        color: #dff0d8;
    }

    body.theme-night .status-red {
        background-color: #a94442;
        border-color: #ebccd1;
        color: #f2dede;
    }

    body.theme-night .status-info {
        background-color: #31708f;
        border-color: #bce8f1;
        color: #d9edf7;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        let characterData = <?php echo json_encode($allCharaktereData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
        let existingPhpFiles = <?php echo json_encode($existingPhpFiles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        const editorContainer = document.getElementById('character-editor-container');
        const saveAllBtn = document.getElementById('save-all-btn');
        const messageBox = document.getElementById('message-box');
        const modal = document.getElementById('edit-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalForm = document.getElementById('edit-form');
        const groupSelect = document.getElementById('modal-group');
        const newGroupInput = document.getElementById('modal-new-group');
        const nameInput = document.getElementById('modal-name');
        const picUrlInput = document.getElementById('modal-pic-url');
        const imagePreview = document.getElementById('modal-image-preview');
        let currentEdit = { group: null, id: null };

        // KORREKTUR: Platzhalter-URLs als Konstanten definieren
        const placeholderUrlUnknown = 'https://placehold.co/50x50/cccccc/333333?text=Pfad?';
        const placeholderUrlMissing = 'https://placehold.co/50x50/dc3545/ffffff?text=Fehlt';
        const modalPlaceholderUrlUnknown = 'https://placehold.co/100x100/cccccc/333333?text=Pfad?';
        const modalPlaceholderUrlMissing = 'https://placehold.co/100x100/dc3545/ffffff?text=Fehlt';


        const renderEditor = () => {
            editorContainer.innerHTML = '';
            Object.keys(characterData).forEach(groupName => {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'character-group';
                groupDiv.dataset.group = groupName;
                const title = document.createElement('h3');
                title.textContent = groupName;
                groupDiv.appendChild(title);
                const listContainer = document.createElement('div');
                listContainer.className = 'character-list-container';
                groupDiv.appendChild(listContainer);

                if (characterData[groupName]) {
                    Object.keys(characterData[groupName]).forEach(charId => {
                        const charData = characterData[groupName][charId] || {};
                        const charEntry = document.createElement('div');
                        charEntry.className = 'character-entry';
                        charEntry.dataset.id = charId;

                        const hasPic = charData.charaktere_pic_url && charData.charaktere_pic_url.trim() !== '';
                        const hasPhpFile = existingPhpFiles.includes(charId);

                        const img = document.createElement('img');
                        // KORREKTUR: Korrekten Placeholder verwenden, wenn kein Pfad existiert
                        img.src = hasPic ? `../${charData.charaktere_pic_url}` : placeholderUrlUnknown;
                        img.alt = charId;
                        img.addEventListener('error', function () {
                            this.onerror = null;
                            // KORREKTUR: Korrekten Placeholder für Ladefehler verwenden
                            this.src = placeholderUrlMissing;
                        }, { once: true });

                        const infoDiv = document.createElement('div');
                        infoDiv.className = 'character-info';
                        infoDiv.innerHTML = `<strong>${charId}</strong><p>${charData.charaktere_pic_url || '<em>Kein Bildpfad</em>'}</p>`;

                        const statusDiv = document.createElement('div');
                        statusDiv.className = 'status-cell';
                        statusDiv.innerHTML = `<span class="status-indicator ${hasPhpFile ? 'status-green' : 'status-red'}" title="PHP-Datei vorhanden">P</span>`;

                        const actionsDiv = document.createElement('div');
                        actionsDiv.className = 'character-actions';
                        actionsDiv.innerHTML = `
                        <button class="button edit-btn">Bearbeiten</button>
                        <button class="button delete delete-btn">Löschen</button>
                    `;

                        charEntry.appendChild(img);
                        charEntry.appendChild(infoDiv);
                        charEntry.appendChild(statusDiv);
                        charEntry.appendChild(actionsDiv);
                        listContainer.appendChild(charEntry);
                    });
                }
                editorContainer.appendChild(groupDiv);
                new Sortable(listContainer, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    group: 'shared',
                    onEnd: () => {
                        const newCharacterData = {};
                        document.querySelectorAll('.character-group').forEach(groupDiv => {
                            const groupName = groupDiv.dataset.group;
                            newCharacterData[groupName] = {};
                            groupDiv.querySelectorAll('.character-entry').forEach(entry => {
                                const charId = entry.dataset.id;
                                newCharacterData[groupName][charId] = findCharacterData(charId);
                            });
                        });
                        characterData = newCharacterData;
                        showMessage('Reihenfolge geändert. Speichern nicht vergessen!', 'status-info');
                    }
                });
            });

            new Sortable(editorContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: 'h3',
                onEnd: () => {
                    const newCharacterData = {};
                    document.querySelectorAll('.character-group').forEach(groupDiv => {
                        const groupName = groupDiv.dataset.group;
                        newCharacterData[groupName] = characterData[groupName];
                    });
                    characterData = newCharacterData;
                    showMessage('Gruppen-Reihenfolge geändert. Speichern nicht vergessen!', 'status-info');
                }
            });
        };

        const populateGroupDropdown = () => {
            groupSelect.innerHTML = '';
            Object.keys(characterData).forEach(groupName => {
                const option = document.createElement('option');
                option.value = groupName;
                option.textContent = groupName;
                groupSelect.appendChild(option);
            });
        };

        const openModal = (charId = null, groupName = null) => {
            currentEdit = { id: charId, group: groupName };
            populateGroupDropdown();

            nameInput.disabled = false;

            if (charId && groupName) {
                modalTitle.textContent = 'Charakter bearbeiten';
                const data = characterData[groupName][charId] || {};
                nameInput.value = charId;
                groupSelect.value = groupName;
                newGroupInput.value = '';
                picUrlInput.value = data.charaktere_pic_url || '';
            } else {
                modalTitle.textContent = 'Neuen Charakter hinzufügen';
                nameInput.value = '';
                newGroupInput.value = '';
                picUrlInput.value = '';
            }
            updateImagePreview();
            modal.style.display = 'block';
        };

        const closeModal = () => {
            modal.style.display = 'none';
            modalForm.reset();
        };

        const updateImagePreview = () => {
            const path = picUrlInput.value;
            // KORREKTUR: Korrekten Placeholder für das Modal verwenden
            imagePreview.src = path ? `../${path}` : modalPlaceholderUrlUnknown;
            imagePreview.onerror = () => {
                // KORREKTUR: Korrekten Fehler-Placeholder für das Modal verwenden
                imagePreview.src = modalPlaceholderUrlMissing;
            };
        };
        picUrlInput.addEventListener('input', updateImagePreview);

        editorContainer.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                const entry = editBtn.closest('.character-entry');
                const group = entry.closest('.character-group').dataset.group;
                openModal(entry.dataset.id, group);
            }

            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                const entry = deleteBtn.closest('.character-entry');
                const group = entry.closest('.character-group').dataset.group;
                if (confirm(`Sicher, dass du "${entry.dataset.id}" löschen willst?`)) {
                    delete characterData[group][entry.dataset.id];
                    if (Object.keys(characterData[group]).length === 0) {
                        delete characterData[group];
                    }
                    renderEditor();
                    showMessage('Charakter zum Löschen vorgemerkt. Speichern, um die Änderung zu übernehmen.', 'status-info');
                }
            }
        });

        document.querySelectorAll('.add-character-btn').forEach(btn => btn.addEventListener('click', () => openModal()));
        modal.querySelector('.close-button').addEventListener('click', closeModal);
        document.getElementById('modal-cancel-btn').addEventListener('click', closeModal);

        modalForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const originalId = currentEdit.id;
            const originalGroup = currentEdit.group;
            const newName = nameInput.value.trim();
            const newGroup = newGroupInput.value.trim() || groupSelect.value;
            const picUrl = picUrlInput.value.trim();

            if (!newName) {
                alert('Der Charaktername darf nicht leer sein.');
                return;
            }

            if (!originalId || originalId !== newName) {
                let exists = false;
                Object.values(characterData).forEach(g => {
                    if (g && g[newName]) exists = true;
                });
                if (exists) {
                    alert('Ein Charakter mit diesem Namen existiert bereits.');
                    return;
                }
            }

            const entryData = { charaktere_pic_url: picUrl };

            if (originalId && characterData[originalGroup] && characterData[originalGroup][originalId]) {
                Object.assign(entryData, characterData[originalGroup][originalId], { charaktere_pic_url: picUrl });
            }

            if (originalId && (originalId !== newName || originalGroup !== newGroup)) {
                if (characterData[originalGroup] && characterData[originalGroup][originalId]) {
                    delete characterData[originalGroup][originalId];
                    if (Object.keys(characterData[originalGroup]).length === 0) {
                        delete characterData[originalGroup];
                    }
                }
            } else if (originalId) { // This handles simple edits where only the pic_url might change
                characterData[originalGroup][originalId] = entryData;
            }

            if (!characterData[newGroup]) {
                characterData[newGroup] = {};
            }
            characterData[newGroup][newName] = entryData;

            renderEditor();
            closeModal();
            showMessage('Änderung vorgemerkt. Speichern nicht vergessen!', 'status-info');
        });


        saveAllBtn.addEventListener('click', async () => {
            const newCharacterData = {};
            document.querySelectorAll('.character-group').forEach(groupDiv => {
                const groupName = groupDiv.dataset.group;
                newCharacterData[groupName] = {};
                groupDiv.querySelectorAll('.character-entry').forEach(entry => {
                    const charId = entry.dataset.id;
                    newCharacterData[groupName][charId] = findCharacterData(charId);
                });
            });
            characterData = newCharacterData;

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'
                    },
                    body: JSON.stringify(characterData)
                });
                const result = await response.json();
                if (result.success) {
                    showMessage(result.message, 'status-green');
                    const lastRunContainer = document.getElementById('last-run-container');
                    const now = new Date();
                    const date = now.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
                    const time = now.toLocaleTimeString('de-DE');
                    let pElement = lastRunContainer.querySelector('.status-message');
                    if (!pElement) {
                        pElement = document.createElement('p');
                        pElement.className = 'status-message status-info';
                        lastRunContainer.prepend(pElement);
                    }
                    pElement.innerHTML = `Letzte Speicherung am ${date} um ${time} Uhr.`;
                } else {
                    showMessage(result.message || 'Ein unbekannter Fehler ist aufgetreten.', 'status-red');
                }
            } catch (error) {
                showMessage('Netzwerkfehler: ' + error.message, 'status-red');
            }
        });

        function findCharacterData(charId) {
            for (const group in characterData) {
                if (characterData[group][charId]) {
                    return characterData[group][charId];
                }
            }
            return {};
        }

        function showMessage(message, className, duration = 5000) {
            messageBox.textContent = message;
            messageBox.className = `status-message ${className}`;
            messageBox.style.display = 'block';
            if (duration > 0) {
                setTimeout(() => { messageBox.style.display = 'none'; }, duration);
            }
        }

        renderEditor();
    });
</script>

<?php include $footerPath; ?>