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
 * @version   4.0.2
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
 * @since     2.8.3 Duplikatprüfung auf Gruppenebene für mehrfache Charakterzuweisungen.
 * @since     2.9.0 Umstellung auf eindeutige Charakter-IDs statt Namen als Schlüssel.
 * @since     3.0.0 Trennung von Charakter-Stammdaten und Gruppenzuweisung.
 * @since     3.1.0 Umstellung auf einfaches Textfeld für Beschreibung, Entfernung von Summernote.
 * @since     3.2.0 Vollständige Neuimplementierung basierend auf v2.8.3 zur korrekten Umsetzung aller ID-System-Anforderungen.
 * @since     3.2.1 CSS-Anpassungen und Hinzufügen der ID-Anzeige im Gruppeneditor.
 * @since     3.2.2 Behebt CSP-Fehler durch Ersetzen von 'onerror' durch Event-Listener.
 * @since     3.2.3 Fügt die Möglichkeit hinzu, bestehende Gruppennamen zu bearbeiten.
 * @since     3.3.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     3.4.0 Umstellung auf neue, granulare Asset-Pfad-Konstanten und Korrektur des Renderer-Pfades.
 * @since     4.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse und Vereinfachung der Bildpfade.
 * @since     4.0.1 Behebt einen fatalen Fehler durch Hinzufügen der fehlenden getRelativePath()-Hilfsfunktion.
 * @since     4.0.2 Behebt einen JavaScript ReferenceError (result vs. data), der fälschlicherweise als JSON-Fehler gemeldet wurde.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

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
    $from = explode('/', rtrim($from, '/'));
    $to = explode('/', rtrim($to, '/'));
    $toFilename = array_pop($to); // Dateinamen für später aufheben
    while (count($from) && count($to) && ($from[0] == $to[0])) {
        array_shift($from);
        array_shift($to);
    }
    $relativePath = str_repeat('../', count($from));
    $relativePath .= implode('/', $to);
    $relativePath .= '/' . $toFilename;
    return $relativePath;
}


function loadJsonData(string $path): array
{
    if (!file_exists($path) || filesize($path) === 0)
        return [];
    $content = file_get_contents($path);
    if ($content === false)
        return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

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
        $deletedIds = array_diff_key($currentCharObjects, $inputData['characters']);
        foreach ($deletedIds as $charId => $charObject) {
            $fileName = str_replace(' ', '_', $charObject['name']) . '.php';
            $filePath = $charakterePhpPath . $fileName;
            if (file_exists($filePath) && unlink($filePath)) {
                $deletedCount++;
            }
        }
        foreach ($inputData['characters'] as $charId => $charObject) {
            $newName = $charObject['name'];
            if (isset($currentCharObjects[$charId])) {
                $oldName = $currentCharObjects[$charId]['name'];
                if ($oldName !== $newName) {
                    $oldFilePath = $charakterePhpPath . str_replace(' ', '_', $oldName) . '.php';
                    $newFilePath = $charakterePhpPath . str_replace(' ', '_', $newName) . '.php';
                    if (file_exists($oldFilePath) && is_writable(dirname($oldFilePath)) && rename($oldFilePath, $newFilePath)) {
                        $renamedCount++;
                    }
                }
            } else {
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
        if (file_put_contents($charaktereJsonPath, json_encode($inputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            $message = "Charakter-Daten erfolgreich gespeichert.";
            if ($createdCount > 0)
                $message .= " $createdCount PHP-Datei(en) erstellt.";
            if ($deletedCount > 0)
                $message .= " $deletedCount PHP-Datei(en) gelöscht.";
            if ($renamedCount > 0)
                $message .= " $renamedCount PHP-Datei(en) umbenannt.";
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

$allCharaktereData = loadJsonData(Path::getDataPath('charaktere.json'));
$lastSavedTimestamp = file_exists(Path::getDataPath('charaktere.json')) ? filemtime(Path::getDataPath('charaktere.json')) : null;
$pageTitle = 'Charakter-Datenbank Editor';
$pageHeader = 'Charakter-Datenbank Editor';
$robotsContent = 'noindex, nofollow';
$bodyClass = 'admin-page';
$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>';

require_once Path::getPartialTemplatePath('header.php');
?>

<div class="admin-container">
    <div id="last-run-container">
        <?php if ($lastSavedTimestamp): ?>
            <p class="status-message status-info">Letzte Speicherung am
                <?php echo date('d.m.Y \u\m H:i:s', $lastSavedTimestamp); ?> Uhr.
            </p>
        <?php endif; ?>
    </div>

    <!-- 1. Charakter-Stammdaten -->
    <div class="section-container">
        <h2>Charakter-Stammdaten</h2>
        <div class="controls">
            <button class="button add-character-btn">Neuen Charakter anlegen</button>
        </div>
        <div id="character-master-list" class="master-list-container">
            <!-- JS-generierter Inhalt -->
        </div>
    </div>

    <hr class="section-divider">

    <!-- 2. Gruppen-Zuweisung -->
    <div class="section-container">
        <h2>Gruppen-Zuweisung</h2>
        <div class="controls">
            <button class="button add-group-btn">Neue Gruppe hinzufügen</button>
        </div>
        <div id="character-groups-container" class="data-editor-container">
            <!-- JS-generierter Inhalt -->
        </div>
    </div>

    <div class="controls bottom-controls">
        <button id="save-all-btn" class="button save-button">Alle Änderungen speichern</button>
    </div>

    <div class="editor-footer-info">
        <p><strong>Info:</strong> Die URL eines Charakters wird aus dem Namen generiert. Leerzeichen werden durch
            Unterstriche (`_`) ersetzt. Bei Namensänderung wird die zugehörige PHP-Datei automatisch umbenannt.</p>
        <div id="message-box" style="display: none;"></div>
    </div>
</div>

<!-- Modals -->
<div id="edit-char-modal" class="modal">
    <div class="modal-content wide">
        <span class="close-button">&times;</span>
        <h2 id="modal-title">Charakter bearbeiten</h2>
        <form id="edit-form">
            <input type="hidden" id="modal-char-id">
            <div class="form-group">
                <label for="modal-id-display">Charakter-ID:</label>
                <input type="text" id="modal-id-display" disabled>
            </div>
            <div class="form-group">
                <label for="modal-name">Charakter-Name:</label>
                <input type="text" id="modal-name" required>
                <small>Wird für die URL und Anzeige verwendet. Muss eindeutig sein.</small>
            </div>
            <div class="form-group">
                <label for="modal-pic-url">Bild-Dateiname:</label>
                <input type="text" id="modal-pic-url">
                <small>Dateiname der Bilddatei aus dem Ordner <?php echo DIRECTORY_PUBLIC_IMG_CHARAKTERS_PROFILES; ?>,
                    z.B. "Trace.webp"</small>
                <!-- <small>Relativer Pfad vom /public/ Ordner aus, z.B. "assets/img/charaktere/faces/icon_trace.gif"</small> -->
            </div>
            <div class="form-group preview-container">
                <label>Bild-Vorschau:</label>
                <img id="modal-image-preview" src="https://placehold.co/100x100/cccccc/333333?text=?"
                    alt="Charakter Vorschau">
            </div>
            <div class="form-group">
                <label for="modal-description">Beschreibung:</label>
                <textarea id="modal-description" rows="5"></textarea>
            </div>
            <div class="modal-buttons">
                <button type="submit" class="button save-button">Speichern</button>
                <button type="button" class="button delete-button cancel-btn">Abbrechen</button>
            </div>
        </form>
    </div>
</div>

<div id="add-to-group-modal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Charakter zu Gruppe hinzufügen</h2>
        <form id="add-to-group-form">
            <input type="hidden" id="add-group-name">
            <div class="form-group">
                <label for="char-select">Charakter auswählen:</label>
                <select id="char-select" required></select>
            </div>
            <div class="modal-buttons">
                <button type="submit" class="button save-button">Hinzufügen</button>
                <button type="button" class="button delete-button cancel-btn">Abbrechen</button>
            </div>
        </form>
    </div>
</div>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    .admin-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }

    .section-container {
        margin-bottom: 2rem;
    }

    .section-divider {
        border: 0;
        border-top: 1px solid #ddd;
        margin: 2rem 0;
    }

    .master-list-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .master-char-entry {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        text-align: center;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .master-char-entry img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 10px;
    }

    .master-char-entry strong {
        display: block;
        margin-bottom: 5px;
        word-wrap: break-word;
    }

    .master-char-entry small {
        display: block;
        color: #888;
        font-size: 0.8em;
        margin-bottom: 10px;
        word-wrap: break-word;
    }

    .character-group-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #eee;
        padding: 10px;
        margin: 0;
        border-bottom: 1px solid #ddd;
        border-radius: 5px 5px 0 0;
    }

    .character-group-header h3 {
        margin: 0;
        cursor: move;
        flex-grow: 1
    }

    .character-group-header h3::before {
        content: '\2630';
        margin-right: 10px;
        color: #999
    }

    .modal-content.wide {
        max-width: 800px;
    }

    .character-info {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-grow: 1;
    }

    .char-id-display {
        font-size: 0.8em;
        color: #888;
        padding: 2px 6px;
        border-radius: 4px;
    }

    body.theme-night .admin-container {
        background-color: #002b3c;
        color: #eee;
    }

    body.theme-night .master-char-entry {
        background-color: #00425c;
        border-color: #2a6177;
    }

    body.theme-night .master-char-entry small {
        color: #aaa;
    }

    body.theme-night .char-id-display {
        color: #aaa;
    }

    body.theme-night .character-group-header {
        background-color: #00334c;
        border-bottom-color: #2a6177;
    }

    body.theme-night hr.section-divider {
        border-top-color: #2a6177;
    }

    /* Inherited styles from v2.8.3 */
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

    .edit-group-btn {
        background-color: #f0ad4e;
        color: white;
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

    .character-actions {
        display: flex;
        gap: 5px;
        flex-direction: row;
        justify-content: center;
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
        font-weight: 700;
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
    .form-group select,
    .form-group textarea {
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
        border-radius: 50%;
        object-fit: cover;
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
        color: #fff;
    }

    .banner {
        z-index: 99 !important;
    }

    body.theme-night .admin-container {
        color: #eee;
    }

    body.theme-night .character-group {
        background-color: #00425c;
        border-color: #2a6177;
    }

    body.theme-night .character-entry {
        border-bottom-color: #2a6177;
    }

    body.theme-night .modal-content {
        background-color: #00425c;
        color: #fff;
    }

    body.theme-night .form-group input,
    body.theme-night .form-group select,
    body.theme-night .form-group textarea {
        background-color: #002b3c;
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

    body.theme-night .status-info {
        background-color: #31708f;
        border-color: #bce8f1;
        color: #d9edf7;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        let characterData = <?php echo json_encode($allCharaktereData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
        if (!characterData || typeof characterData !== 'object') characterData = {};
        if (!characterData.characters) characterData.characters = {};
        if (!characterData.groups) characterData.groups = {};

        const baseUrl = '<?php echo DIRECTORY_PUBLIC_URL; ?>';
        const charProfileUrlBase = '<?php echo Url::getImgCharactersProfilesUrl(''); ?>';
        // UI Elements
        const masterListContainer = document.getElementById('character-master-list');
        const groupsContainer = document.getElementById('character-groups-container');
        const saveAllBtn = document.getElementById('save-all-btn');
        const messageBox = document.getElementById('message-box');

        // Modals
        const editModal = document.getElementById('edit-char-modal');
        const editForm = document.getElementById('edit-form');
        const addToGroupModal = document.getElementById('add-to-group-modal');
        const addToGroupForm = document.getElementById('add-to-group-form');

        const placeholderUrl = 'https://placehold.co/80x80/cccccc/333333?text=Datei?';
        const errorUrl = 'https://placehold.co/80x80/dc3545/ffffff?text=Fehlt';

        const getSortedCharacters = () => Object.entries(characterData.characters).sort(([, a], [, b]) => a.name.localeCompare(b.name, 'de', { sensitivity: 'base' }));

        const renderMasterList = () => {
            masterListContainer.innerHTML = '';
            getSortedCharacters().forEach(([id, char]) => {
                const entryDiv = document.createElement('div');
                entryDiv.className = 'master-char-entry';
                entryDiv.dataset.charId = id;
                const imgSrc = char.pic_url ? `${charProfileUrlBase}${char.pic_url}` : placeholderUrl;
                entryDiv.innerHTML = `
                <div>
                    <img src="${imgSrc}" alt="${char.name}">
                    <strong>${char.name}</strong>
                    <small>ID: ${id}</small>
                </div>
                <div class="character-actions">
                    <button class="button edit-master-btn">Bearbeiten</button>
                    <button class="button delete-button delete-master-btn">Löschen</button>
                </div>`;
                masterListContainer.appendChild(entryDiv);
            });
        };

        const renderGroups = () => {
            const groupOrder = Object.keys(characterData.groups);
            groupsContainer.innerHTML = ''; // Clear before re-rendering
            new Sortable(groupsContainer, { animation: 150, handle: 'h3', ghostClass: 'sortable-ghost' });

            groupOrder.forEach(groupName => {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'character-group';
                groupDiv.dataset.groupName = groupName;
                groupDiv.innerHTML = `
                <div class="character-group-header">
                    <h3>${groupName}</h3>
                    <div>
                        <button class="button edit-group-btn" title="Gruppe umbenennen">&#9998;</button>
                        <button class="button add-char-to-group-btn" title="Charakter zu dieser Gruppe hinzufügen">+</button>
                        <button class="button delete-button delete-group-btn" title="Gruppe löschen">X</button>
                    </div>
                </div>
                <div class="character-list-container"></div>`;
                const listContainer = groupDiv.querySelector('.character-list-container');
                (characterData.groups[groupName] || []).forEach(charId => {
                    const char = characterData.characters[charId];
                    const displayName = char ? char.name : `<span style="color:red">[ID nicht gefunden: ${charId}]</span>`;
                    const picUrl = char ? char.pic_url : '';
                    const imgSrc = picUrl ? `${charProfileUrlBase}${picUrl}` : 'https://placehold.co/50x50/cccccc/333333?text=?';

                    const charEntry = document.createElement('div');
                    charEntry.className = 'character-entry';
                    charEntry.dataset.charId = charId;
                    charEntry.innerHTML = `
                    <img src="${imgSrc}" alt="${char ? char.name : 'Unbekannt'}">
                    <div class="character-info">
                        <strong>${displayName}</strong>
                        <span class="char-id-display">${charId}</span>
                    </div>
                    <div class="character-actions">
                        <button class="button delete-button remove-char-btn" title="Aus Gruppe entfernen">X</button>
                    </div>`;
                    listContainer.appendChild(charEntry);
                });
                groupsContainer.appendChild(groupDiv);
                new Sortable(listContainer, { animation: 150, group: 'shared-chars', ghostClass: 'sortable-ghost' });
            });
        };

        // Zentraler Error-Handler für Bilder
        const handleImageError = (event) => {
            if (event.target.tagName === 'IMG') {
                const errorSrc = event.target.naturalWidth <= 50 ? 'https://placehold.co/50x50/dc3545/ffffff?text=X' : errorUrl;
                if (event.target.src !== errorSrc) {
                    event.target.src = errorSrc;
                }
            }
        };

        masterListContainer.addEventListener('error', handleImageError, true);
        groupsContainer.addEventListener('error', handleImageError, true);


        const openEditModal = (charId = null) => {
            editForm.reset();
            const idDisplay = editModal.querySelector('#modal-id-display');
            if (charId) {
                const char = characterData.characters[charId];
                editModal.querySelector('#modal-title').textContent = 'Charakter bearbeiten';
                editModal.querySelector('#modal-char-id').value = charId;
                idDisplay.value = charId;
                editModal.querySelector('#modal-name').value = char.name;
                editModal.querySelector('#modal-pic-url').value = char.pic_url || '';
                editModal.querySelector('#modal-description').value = char.description || '';
            } else {
                const newId = 'char_' + Date.now();
                editModal.querySelector('#modal-title').textContent = 'Neuen Charakter anlegen';
                editModal.querySelector('#modal-char-id').value = newId;
                idDisplay.value = newId;
            }
            updateImagePreview();
            editModal.style.display = 'block';
        };

        const updateImagePreview = () => {
            const path = editModal.querySelector('#modal-pic-url').value;
            const preview = editModal.querySelector('#modal-image-preview');
            preview.src = path ? `${charProfileUrlBase}${path}` : 'https://placehold.co/100x100/cccccc/333333?text=?';
            preview.onerror = () => { preview.src = 'https://placehold.co/100x100/dc3545/ffffff?text=X'; };
        };

        const openAddToGroupModal = (groupName) => {
            const form = addToGroupModal.querySelector('form');
            form.reset();
            form.querySelector('#add-group-name').value = groupName;
            const select = form.querySelector('#char-select');
            select.innerHTML = '<option value="">-- Charakter wählen --</option>';
            const charsInGroup = new Set(characterData.groups[groupName] || []);
            getSortedCharacters().forEach(([id, char]) => {
                if (!charsInGroup.has(id)) {
                    select.innerHTML += `<option value="${id}">${char.name}</option>`;
                }
            });
            addToGroupModal.style.display = 'block';
        };

        document.querySelector('.admin-container').addEventListener('click', e => {
            if (e.target.matches('.add-character-btn')) openEditModal();
            if (e.target.matches('.edit-master-btn')) openEditModal(e.target.closest('.master-char-entry').dataset.charId);
            if (e.target.matches('.add-group-btn')) {
                const name = prompt("Name der neuen Gruppe:");
                if (name && name.trim()) {
                    if (characterData.groups[name.trim()] === undefined) {
                        characterData.groups[name.trim()] = [];
                        renderGroups();
                    } else alert("Gruppe existiert bereits.");
                }
            }
            if (e.target.matches('.edit-group-btn')) {
                const groupDiv = e.target.closest('.character-group');
                const oldName = groupDiv.dataset.groupName;
                const newName = prompt("Neuen Namen für die Gruppe eingeben:", oldName);

                if (newName && newName.trim() && newName.trim() !== oldName) {
                    const cleanNewName = newName.trim();
                    if (characterData.groups.hasOwnProperty(cleanNewName)) {
                        alert("Eine Gruppe mit diesem Namen existiert bereits.");
                        return;
                    }
                    // Create a new object to preserve order
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
                    showMessage('Gruppe umbenannt. Speichern nicht vergessen!', 'status-info');
                }
            }
            if (e.target.matches('.delete-master-btn')) {
                const entry = e.target.closest('.master-char-entry');
                if (confirm(`"${entry.querySelector('strong').textContent}" wirklich endgültig löschen? Er wird aus ALLEN Gruppen entfernt.`)) {
                    delete characterData.characters[entry.dataset.charId];
                    renderMasterList(); renderGroups();
                    showMessage('Charakter zum Löschen vorgemerkt. Speichern, um die Änderung zu übernehmen.', 'status-info');
                }
            }
            if (e.target.matches('.delete-group-btn')) {
                const groupDiv = e.target.closest('.character-group');
                if (confirm(`Gruppe "${groupDiv.dataset.groupName}" wirklich löschen?`)) {
                    delete characterData.groups[groupDiv.dataset.groupName];
                    renderGroups();
                    showMessage('Gruppe zum Löschen vorgemerkt. Speichern, um die Änderung zu übernehmen.', 'status-info');
                }
            }
            if (e.target.matches('.add-char-to-group-btn')) openAddToGroupModal(e.target.closest('.character-group').dataset.groupName);
            if (e.target.matches('.remove-char-btn')) {
                if (confirm('Charakter wirklich aus dieser Gruppe entfernen?')) {
                    e.target.closest('.character-entry').remove();
                    showMessage('Charakter aus Gruppe entfernt. Speichern nicht vergessen!', 'status-info');
                }
            }
        });

        editForm.addEventListener('submit', e => {
            e.preventDefault();
            const id = editModal.querySelector('#modal-char-id').value;
            const name = editModal.querySelector('#modal-name').value.trim();
            const pic_url = editModal.querySelector('#modal-pic-url').value.trim();
            const description = editModal.querySelector('#modal-description').value.trim();
            if (!name) return alert('Name darf nicht leer sein.');
            const isDuplicate = Object.entries(characterData.characters).some(([charId, char]) => char.name.toLowerCase() === name.toLowerCase() && charId !== id);
            if (isDuplicate) return alert('Ein Charakter mit diesem Namen existiert bereits.');
            characterData.characters[id] = { name, pic_url, description };
            renderMasterList(); renderGroups();
            editModal.style.display = 'none';
            showMessage('Charakter-Daten aktualisiert. Speichern nicht vergessen!', 'status-info');
        });

        addToGroupForm.addEventListener('submit', e => {
            e.preventDefault();
            const charId = addToGroupModal.querySelector('#char-select').value;
            const groupName = addToGroupModal.querySelector('#add-group-name').value;
            if (charId && groupName && characterData.groups[groupName]) {
                if (!characterData.groups[groupName].includes(charId)) {
                    characterData.groups[groupName].push(charId);
                }
                renderGroups();
            }
            addToGroupModal.style.display = 'none';
        });

        document.querySelectorAll('.modal .close-button, .modal .cancel-btn').forEach(btn => btn.addEventListener('click', () => {
            btn.closest('.modal').style.display = 'none';
        }));
        editModal.querySelector('#modal-pic-url').addEventListener('input', updateImagePreview);

        saveAllBtn.addEventListener('click', async (e) => {
            e.preventDefault(); // VERHINDERT DAS NEULADEN DER SEITE
            const dataToSave = { schema_version: 2, characters: {}, groups: {} };

            document.querySelectorAll('#character-groups-container .character-group').forEach(groupDiv => {
                const groupName = groupDiv.dataset.groupName;
                const charIdsInGroup = [];
                groupDiv.querySelectorAll('.character-entry').forEach(entry => {
                    charIdsInGroup.push(entry.dataset.charId);
                });
                dataToSave.groups[groupName] = charIdsInGroup;
            });

            dataToSave.characters = { ...characterData.characters };

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');
                formData.append('characterData', JSON.stringify(dataToSave));

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                try {
                    const data = JSON.parse(responseText);
                    if (response.ok && data.success) {
                        characterData = dataToSave;
                        renderMasterList();
                        renderGroups();
                        showMessage(data.message, 'status-green');
                    } else {
                        showMessage(data.message || 'Ein Fehler ist aufgetreten.', 'status-red');
                    }
                } catch (e) {
                    throw new Error(`Ungültige JSON-Antwort vom Server: ${responseText}`);
                }
            } catch (error) {
                const userFriendlyMessage = `Netzwerkfehler: Die Antwort vom Server war kein gültiges JSON. Oft liegt das an einem PHP-Fehler. Server-Antwort: ${error.message}`;
                showMessage(userFriendlyMessage, 'status-red');
            }
        });

        function showMessage(message, className, duration = 5000) {
            messageBox.textContent = message;
            messageBox.className = `status-message ${className}`;
            messageBox.style.display = 'block';
            setTimeout(() => { messageBox.style.display = 'none'; }, duration);
        }

        if (characterData && characterData.schema_version >= 2) {
            renderMasterList();
            renderGroups();
        } else {
            groupsContainer.innerHTML = '<p class="status-message status-red"><strong>Fehler:</strong> Die <code>charaktere.json</code> hat ein veraltetes Format. Bitte führe zuerst das Migrationsskript aus: <a href="${baseUrl}/admin/migration_char_id.php">migration_char_id.php</a></p>';
            masterListContainer.innerHTML = '<p class="status-message status-red">Bitte zuerst migrieren.</p>';
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>