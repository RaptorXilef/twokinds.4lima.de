<?php
/**
 * Administrationsseite zum Bearbeiten der charaktere.json.
 * V1.4: Fügt Drag-and-Drop-Sortierung für Charaktere innerhalb ihrer Gruppen hinzu.
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
    // CSRF-Token aus den Headern oder dem Body holen (flexibel für Fetch)
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token)) {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
    }

    if (!verify_csrf_token($token, true)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ungültiges CSRF-Token.']);
        exit;
    }

    $inputData = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($inputData)) {
        // Backup der alten Datei erstellen
        if (file_exists($charaktereJsonPath)) {
            copy($charaktereJsonPath, $charaktereJsonPath . '.bak');
        }

        // Neue Daten schreiben
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

// === HEADER-VARIABLEN SETZEN ===
$pageTitle = 'Charakter-Datenbank Editor';
$pageHeader = 'Charakter-Datenbank Editor';
$robotsContent = 'noindex, nofollow';
$bodyClass = 'admin-page';

// Zusätzliche Skripte für diese Seite
$additionalScripts = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js" nonce="' . htmlspecialchars($nonce) . '"></script>';
$additionalScripts .= '<script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript" src="src/js/data_editor_charaktere.js"></script>';


include $headerPath;
?>

<div class="admin-container">
    <div id="message-container"></div>
    <div class="controls">
        <button class="button add-character-btn">Neuen Charakter hinzufügen</button>
    </div>

    <div id="character-editor-container" class="data-editor-container">
        <!-- Charakter-Gruppen und -Einträge werden hier per JS eingefügt -->
    </div>

    <div class="controls" style="margin-top: 20px;">
        <button class="button add-character-btn">Neuen Charakter hinzufügen</button>
        <button id="save-all-btn" class="button save-button">Änderungen speichern</button>
    </div>
</div>

<!-- Modal zum Bearbeiten/Hinzufügen von Charakteren -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modal-title">Charakter bearbeiten</h2>
        <form id="edit-form">
            <div class="form-group">
                <label for="modal-group">Charakter-Gruppe:</label>
                <select id="modal-group" name="group" required>
                    <!-- Optionen werden per JS gefüllt -->
                </select>
                <input type="text" id="modal-new-group" name="new_group" placeholder="Oder neue Gruppe eingeben...">
            </div>
            <div class="form-group">
                <label for="modal-name">Charakter-Name:</label>
                <input type="text" id="modal-name" name="name" required>
                <small>Dies ist der interne Schlüssel (z.B. "Trace", "Red", "Trace_böse"). Keine Leerzeichen.</small>
            </div>
            <div class="form-group">
                <label for="modal-pic-url">Bild-Pfad:</label>
                <input type="text" id="modal-pic-url" name="charaktere_pic_url">
                <small>Relativer Pfad vom Hauptverzeichnis, z.B.
                    "assets/img/charaktere/charaktere_1x1_webp/Trace.webp"</small>
            </div>
            <div class="form-group preview-container">
                <label>Bild-Vorschau:</label>
                <img id="modal-image-preview" src="https://placehold.co/100x100/cccccc/333333?text=Kein+Bild"
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
    }

    .character-actions button {
        margin-left: 5px;
    }

    /* --- NEU: Stile für Drag & Drop --- */
    .character-list-sortable .character-entry {
        cursor: grab;
    }

    .character-list-sortable .character-entry:active {
        cursor: grabbing;
    }

    .sortable-ghost {
        opacity: 0.4;
        background: #c8ebfb;
    }

    .sortable-drag {
        opacity: 1 !important;
        background: #e2f3fe;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }


    /* Modal Styles */
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

    /* --- Dark Mode Anpassungen --- */
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
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    // PHP-Daten an JavaScript übergeben
    window.characterData = <?php echo json_encode($allCharaktereData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
    window.baseUrl = '<?php echo $baseUrl; ?>';
    window.csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
</script>

<?php include $footerPath; ?>