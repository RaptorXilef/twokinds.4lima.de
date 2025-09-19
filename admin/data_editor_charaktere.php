<?php
/**
 * Administrationsseite zum Bearbeiten der charaktere.json.
 * V2.2: Passt die Position des Zeitstempels und der Erfolgsmeldung
 * für ein konsistentes Admin-UI an.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$charaktereJsonPath = __DIR__ . '/../src/config/charaktere.json';

// --- DATENVERARBEITUNG BEI POST-REQUEST (VIA FETCH API) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = '';
    $headers = getallheaders();
    $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);
    if (isset($normalizedHeaders['x-csrf-token'])) {
        $token = $normalizedHeaders['x-csrf-token'];
    }

    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF-Token-Validierung fehlgeschlagen. Die Seite wird neu geladen.']);
        exit;
    }

    $inputData = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($inputData)) {
        if (file_exists($charaktereJsonPath)) {
            copy($charaktereJsonPath, $charaktereJsonPath . '.bak');
        }

        if (file_put_contents($charaktereJsonPath, json_encode($inputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Charakter-Daten erfolgreich gespeichert.']);
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Fehler beim Schreiben der JSON-Datei.']);
        }
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige JSON-Daten empfangen.']);
    }
    exit;
}


// --- DATEN FÜR DIE SEITENANZEIGE LADEN ---
$allCharaktereData = [];
if (file_exists($charaktereJsonPath)) {
    $jsonContent = file_get_contents($charaktereJsonPath);
    $allCharaktereData = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $allCharaktereData = [];
        if ($debugMode) {
            error_log("Fehler beim Dekodieren von charaktere.json: " . json_last_error_msg());
        }
    }
}
$lastSavedTimestamp = file_exists($charaktereJsonPath) ? filemtime($charaktereJsonPath) : null;


// === HEADER-VARIABLEN SETZEN ===
$pageTitle = 'Charakter-Datenbank Editor';
$pageHeader = 'Charakter-Datenbank Editor';
$robotsContent = 'noindex, nofollow';
$bodyClass = 'admin-page';

// Zusätzliche Skripte
$additionalScripts = '
    <script nonce="' . htmlspecialchars($nonce) . '" src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="src/js/data_editor_charaktere.js"></script>';


include $headerPath;
?>

<div class="admin-container">

    <!-- Zeitstempel jetzt ganz oben -->
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

    <!-- Container für die Erfolgsmeldung unten -->
    <div class="editor-footer-info">
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
                <small>Interner Schlüssel (z.B. "Trace", "Red"). Keine Leerzeichen.</small>
            </div>
            <div class="form-group">
                <label for="modal-pic-url">Bild-Pfad:</label>
                <input type="text" id="modal-pic-url" name="charaktere_pic_url">
                <small>Relativer Pfad, z.B. "assets/img/charaktere/charaktere_1x1_webp/Trace.webp"</small>
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

    /* Abstand nach unten für den oberen Zeitstempel */
    .editor-footer-info {
        margin-top: 20px;
        border-top: 1px solid #ddd;
        padding-top: 15px;
    }

    /* Allgemeine Stile */
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

    .character-actions button {
        margin-left: 5px;
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
    window.characterData = <?php echo json_encode($allCharaktereData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
    window.baseUrl = '<?php echo $baseUrl; ?>';
    window.csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
</script>

<?php include $footerPath; ?>