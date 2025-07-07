<?php
/**
 * Dies ist die Administrationsseite zum Bearbeiten der comic_var.json Konfigurationsdatei.
 * Sie ermöglicht das Hinzufügen, Bearbeiten und Löschen von Comic-Einträgen
 * über eine benutzerfreundliche Oberfläche.
 *
 * Zusätzlich werden fehlende Comic-IDs aus den Bildordnern automatisch hinzugefügt
 * und unvollständige Einträge visuell hervorgehoben.
 * Ein Bericht über fehlende Informationen wird am Ende der Seite angezeigt.
 */

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Zerstöre die Session.
    session_destroy();

    // Weiterleitung zur Login-Seite (index.php im Admin-Bereich).
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/components/comic_var.json';
$comicLowresDirPath = __DIR__ . '/../assets/comic_lowres/';
$comicHiresDirPath = __DIR__ . '/../assets/comic_hires/';

// Setze Parameter für den Header.
$pageTitle = 'Comic Daten Editor';
$pageHeader = 'Comic Daten Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen

$message = '';
$messageType = ''; // 'success' or 'error'

// Optionen für 'type' und 'chapter'
$comicTypeOptions = ['Comicseite vom ', 'Lückenfüller']; // "Comicseite vom " ist der Standard für neue Seiten
$chapterOptions = range(1, 100); // Beispiel: Kapitel 1 bis 100

/**
 * Lädt Comic-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @return array Die dekodierten Daten als assoziatives Array oder ein leeres Array im Fehlerfall.
 */
function loadComicData(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        return [];
    }
    return is_array($data) ? $data : [];
}

/**
 * Speichert Comic-Daten in die JSON-Datei, alphabetisch sortiert.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveComicData(string $path, array $data): bool {
    // Sortiere das Array alphabetisch nach Schlüsseln (Comic-IDs)
    ksort($data);
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren von Comic-Daten: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) === false) {
        error_log("Fehler beim Schreiben der Comic-Daten nach " . $path);
        return false;
    }
    return true;
}

/**
 * Scannt die Comic-Bildverzeichnisse nach vorhandenen Comic-IDs.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung), sortiert.
 */
function getComicIdsFromImages(string $lowresDir, string $hiresDir): array {
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    $dirs = [$lowresDir, $hiresDir];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                // Ignoriere . und .. sowie versteckte Dateien und "in_translation" Bilder
                if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false) {
                    continue;
                }
                $info = pathinfo($file);
                if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), $imageExtensions)) {
                    $imageIds[$info['filename']] = true; // Verwende assoziatives Array für Eindeutigkeit
                }
            }
        }
    }
    $sortedIds = array_keys($imageIds);
    sort($sortedIds);
    return $sortedIds;
}


// Verarbeite POST-Anfragen zum Speichern (AJAX-Handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    // JSON-Daten aus dem Request Body lesen
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Dekodieren der JSON-Daten: ' . json_last_error_msg()]);
        exit;
    }

    $updatedComicData = [];
    if (isset($requestData['pages']) && is_array($requestData['pages'])) {
        foreach ($requestData['pages'] as $page) {
            $comicId = trim($page['comic_id']);
            if (empty($comicId)) {
                continue; // Überspringe leere IDs
            }

            $type = isset($page['comic_type']) ? trim($page['comic_type']) : '';
            $name = isset($page['comic_name']) ? trim($page['comic_name']) : '';
            $transcript = isset($page['comic_transcript']) ? $page['comic_transcript'] : '';
            $chapter = isset($page['comic_chapter']) ? (int)$page['comic_chapter'] : null;

            // Validierung für Chapter (muss eine Zahl sein)
            if (!is_numeric($chapter) || $chapter <= 0) {
                $chapter = null; // Setze auf null oder einen Standardwert, wenn ungültig
            }

            $updatedComicData[$comicId] = [
                'type' => $type,
                'name' => $name,
                'transcript' => $transcript,
                'chapter' => $chapter
            ];
        }
    }

    if (saveComicData($comicVarJsonPath, $updatedComicData)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Comic-Daten erfolgreich gespeichert!']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Speichern der Comic-Daten.']);
        exit;
    }
}

// Lade die aktuelle Konfiguration für die Anzeige (für GET-Anfragen)
$currentComicData = loadComicData($comicVarJsonPath);
$imageComicIds = getComicIdsFromImages($comicLowresDirPath, $comicHiresDirPath);

// Füge fehlende Comic-IDs aus den Bildern hinzu
foreach ($imageComicIds as $id) {
    if (!isset($currentComicData[$id])) {
        $currentComicData[$id] = [
            'type' => '', // Leer lassen für neue Einträge
            'name' => '', // Leer lassen
            'transcript' => '', // Leer lassen
            'chapter' => null // Leer lassen
        ];
    }
}

// Sortiere die Daten für die Anzeige nach Comic-ID
ksort($currentComicData);

// Bericht über unvollständige Informationen
$incompleteInfoReport = [];
foreach ($currentComicData as $id => $data) {
    $missingFields = [];
    if (empty($data['type'])) {
        $missingFields[] = 'type';
    }
    if (empty($data['name'])) {
        $missingFields[] = 'name';
    }
    if (empty($data['transcript'])) {
        $missingFields[] = 'transcript';
    }
    if ($data['chapter'] === null || $data['chapter'] <= 0) {
        $missingFields[] = 'chapter';
    }

    if (!empty($missingFields)) {
        $incompleteInfoReport[$id] = $missingFields;
    }
}

// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<style>
    /* Allgemeine Tabellenstile */
    .comic-data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .comic-data-table th, .comic-data-table td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
        vertical-align: top; /* Für Textarea */
    }
    .comic-data-table th {
        background-color: #ddead7;
    }
    .comic-data-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .comic-data-table input[type="text"],
    .comic-data-table input[type="number"],
    .comic-data-table select,
    .comic-data-table textarea {
        width: 100%;
        padding: 5px;
        box-sizing: border-box;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    .comic-data-table textarea {
        min-height: 80px; /* Mindesthöhe für Transcript */
        resize: both; /* Vertikal und Horizontal skalierbar */
        font-family: 'Open Sans', sans-serif; /* Konsistente Schriftart */
        font-size: 15px;
    }
    .comic-data-table button.remove-row {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
    }
    .comic-data-table button.remove-row:hover {
        background-color: #d32f2f;
    }
    .button-container {
        margin-top: 20px;
        text-align: right;
    }
    .button-container .button {
        margin-left: 10px;
    }
    .message-box {
        margin-top: 20px;
        padding: 10px;
        border-radius: 5px;
        color: #155724; /* Standardfarbe für Erfolg */
        border: 1px solid #c3e6cb; /* Standardfarbe für Erfolg */
        background-color: #d4edda; /* Standardfarbe für Erfolg */
    }
    .message-box.error {
        color: #721c24;
        border-color: #f5c6cb;
        background-color: #f8d7da;
    }
    /* Hervorhebung für unvollständige Einträge */
    .incomplete-entry {
        background-color: #f8d7da !important; /* Rosa/Rot */
    }
    /* Dark Theme Anpassungen */
    body.theme-night .comic-data-table th {
        background-color: #48778a;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .comic-data-table td {
        border-color: #002b3c;
    }
    body.theme-night .comic-data-table tr:nth-child(even) {
        background-color: #00334c;
    }
    body.theme-night .comic-data-table input[type="text"],
    body.theme-night .comic-data-table input[type="number"],
    body.theme-night .comic-data-table select,
    body.theme-night .comic-data-table textarea {
        background-color: #2a6177;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .comic-data-table button.remove-row {
        background-color: #a00;
    }
    body.theme-night .comic-data-table button.remove-row:hover {
        background-color: #c00;
    }
    body.theme-night .message-box {
        color: #d4edda; /* Textfarbe für Erfolg im Dark Theme */
        border-color: #2a6177;
        background-color: #00334c;
    }
    body.theme-night .message-box.error {
        color: #f5c6cb;
        border-color: #721c24;
        background-color: #5a0000;
    }
    body.theme-night .incomplete-entry {
        background-color: #721c24 !important; /* Dunkleres Rot für Dark Mode */
    }

    /* Summernote Styles */
    .note-editor.note-frame {
        border-radius: 3px;
        border: 1px solid #ddd; /* Match existing input borders */
    }
    body.theme-night .note-editor.note-frame {
        background-color: #2a6177; /* Darker background for editor frame */
        border-color: #002b3c;
    }
    body.theme-night .note-editor .note-toolbar {
        background-color: #2a6177; /* Darker toolbar */
        border-bottom: 1px solid #002b3c;
    }
    body.theme-night .note-editor .note-editing-area .note-editable {
        background-color: #00334c; /* Darker editing area */
        color: #fff;
    }
    body.theme-night .note-editor .note-statusbar {
        background-color: #2a6177;
        border-top: 1px solid #002b3c;
        color: #fff;
    }
    body.theme-night .note-editor .btn-group .btn {
        background-color: #48778a; /* Button background */
        color: #fff; /* Button text */
        border-color: #002b3c;
    }
    body.theme-night .note-editor .btn-group .btn:hover {
        background-color: #628492; /* Button hover */
    }
    body.theme-night .note-editor .btn-group .btn.active {
        background-color: #628492; /* Active button */
    }
    body.theme-night .note-editor .dropdown-menu {
        background-color: #2a6177;
        border-color: #002b3c;
    }
    body.theme-night .note-editor .dropdown-menu a {
        color: #fff;
    }
    body.theme-night .note-editor .dropdown-menu a:hover {
        background-color: #48778a;
    }
    body.theme-night .note-popover .popover-content {
        background-color: #2a6177;
        color: #fff;
    }
    body.theme-night .note-popover .popover-content .btn-group .btn {
        background-color: #48778a;
        color: #fff;
    }
    body.theme-night .note-popover .popover-content .btn-group .btn:hover {
        background-color: #628492;
    }


    /* Incomplete Report Table */
    .incomplete-report-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .incomplete-report-table th,
    .incomplete-report-table td {
        border: 1px solid #f5c6cb;
        padding: 6px;
        text-align: center;
        font-size: 0.9em;
    }
    .incomplete-report-table th {
        background-color: #f5c6cb;
        color: #721c24;
    }
    .incomplete-report-table td {
        background-color: #f8d7da;
        color: #721c24;
    }
    body.theme-night .incomplete-report-table th {
        background-color: #721c24;
        color: #f5c6cb;
    }
    body.theme-night .incomplete-report-table td {
        background-color: #5a0000;
        color: #f5c6cb;
    }
    .status-icon {
        font-weight: bold;
        font-size: 1.1em;
    }
    .status-icon.complete {
        color: #28a745; /* Grün für Haken */
    }
    .status-icon.incomplete {
        color: #dc3545; /* Rot für Kreuz */
    }
    body.theme-night .status-icon.complete {
        color: #90ee90; /* Helleres Grün für Dark Mode */
    }
    body.theme-night .status-icon.incomplete {
        color: #ff6347; /* Helleres Rot für Dark Mode */
    }
</style>

<section>
    <h2 class="page-header">Comic Daten bearbeiten</h2>

    <?php if (!empty($message)): ?>
        <div class="message-box <?php echo $messageType; ?>">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <form id="comic-data-form">
        <table class="comic-data-table" id="comic-data-editor-table">
            <thead>
                <tr>
                    <th>Comic ID</th>
                    <th>Typ</th>
                    <th>Name</th>
                    <th>Transkript</th>
                    <th>Kapitel</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($currentComicData)): ?>
                    <?php foreach ($currentComicData as $id => $data):
                        // Bestimme, ob der Eintrag unvollständig ist, um die Klasse zu setzen
                        $isTypeMissing = empty($data['type']);
                        $isNameMissing = empty($data['name']);
                        $isTranscriptMissing = empty($data['transcript']);
                        $isChapterMissing = ($data['chapter'] === null || $data['chapter'] <= 0);
                        $rowClass = ($isTypeMissing || $isNameMissing || $isTranscriptMissing || $isChapterMissing) ? 'incomplete-entry' : '';
                    ?>
                        <tr class="<?php echo $rowClass; ?>" data-comic-id="<?php echo htmlspecialchars($id); ?>">
                            <td><input type="text" name="comic_id[]" value="<?php echo htmlspecialchars($id); ?>" readonly></td>
                            <td>
                                <select name="comic_type[]">
                                    <option value="" <?php echo ($data['type'] == '') ? 'selected' : ''; ?>>-- Auswählen --</option>
                                    <?php foreach ($comicTypeOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($data['type'] == $option) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="comic_name[]" value="<?php echo htmlspecialchars($data['name']); ?>"></td>
                            <td>
                                <!-- Summernote wird hier initialisiert. Der Wert ist reines HTML. -->
                                <textarea name="comic_transcript[]" class="transcript-textarea" id="transcript-<?php echo htmlspecialchars($id); ?>"><?php echo $data['transcript']; ?></textarea>
                            </td>
                            <td>
                                <input type="number" name="comic_chapter[]" value="<?php echo htmlspecialchars($data['chapter'] ?? ''); ?>" min="1">
                            </td>
                            <td><button type="button" class="remove-row">Entfernen</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="no-entries-row">
                        <td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="button-container">
            <button type="button" id="add-new-comic-entry" class="button">Neuen Comic-Eintrag hinzufügen (+)</button>
            <button type="submit" id="save-comic-data" class="button">Änderungen speichern</button>
        </div>
    </form>

    <?php if (!empty($incompleteInfoReport)): ?>
        <div class="incomplete-report">
            <h3>Informationen fehlen bei:</h3>
            <table class="incomplete-report-table">
                <thead>
                    <tr>
                        <th>Comic ID</th>
                        <th>Typ</th>
                        <th>Name</th>
                        <th>Transkript</th>
                        <th>Kapitel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentComicData as $id => $data):
                        $isTypeMissing = empty($data['type']);
                        $isNameMissing = empty($data['name']);
                        $isTranscriptMissing = empty($data['transcript']);
                        $isChapterMissing = ($data['chapter'] === null || $data['chapter'] <= 0);
                        
                        // Nur Zeilen anzeigen, die tatsächlich unvollständig sind
                        if ($isTypeMissing || $isNameMissing || $isTranscriptMissing || $isChapterMissing):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($id); ?></td>
                            <td><span class="status-icon <?php echo $isTypeMissing ? 'incomplete' : 'complete'; ?>"><?php echo $isTypeMissing ? '❌' : '✔'; ?></span></td>
                            <td><span class="status-icon <?php echo $isNameMissing ? 'incomplete' : 'complete'; ?>"><?php echo $isNameMissing ? '❌' : '✔'; ?></span></td>
                            <td><span class="status-icon <?php echo $isTranscriptMissing ? 'incomplete' : 'complete'; ?>"><?php echo $isTranscriptMissing ? '❌' : '✔'; ?></span></td>
                            <td><span class="status-icon <?php echo $isChapterMissing ? 'incomplete' : 'complete'; ?>"><?php echo $isChapterMissing ? '❌' : '✔'; ?></span></td>
                        </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- jQuery (Summernote Dependency) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<!-- Summernote CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<!-- Summernote JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#comic-data-editor-table tbody');
    const addEntryButton = document.getElementById('add-new-comic-entry');
    const saveButton = document.getElementById('save-comic-data');
    const noEntriesRow = document.getElementById('no-entries-row');
    const messageBoxElement = document.querySelector('.message-box');

    // Optionen für die Dropdowns (müssen im JS wiederholt werden, da PHP-Variablen nicht direkt zugänglich sind)
    const comicTypeOptions = <?php echo json_encode($comicTypeOptions); ?>;
    const chapterOptions = <?php echo json_encode($chapterOptions); ?>;

    // Summernote Initialisierung
    function initializeSummernote(selector) {
        $(selector).summernote({
            height: 150, // Set initial height
            minHeight: null, // Set minimum height
            maxHeight: null, // Set maximum height
            focus: true, // Set focus to editable area after initializing summernote
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['font', ['fontsize', 'color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'hr']],
                ['view', ['codeview', 'fullscreen', 'help']]
            ],
            // Custom CSS for Dark Theme within Summernote iframe (if it creates an iframe)
            // Summernote Lite does not create an iframe by default, so direct CSS is usually enough.
            // If it behaves unexpectedly with dark theme, additional CSS might be needed for .note-editable
            callbacks: {
                onChange: function(contents, $editable) {
                    // Update the underlying textarea value
                    $(this).val(contents);
                    // Trigger completeness check
                    updateRowCompleteness(this);
                },
                onKeyup: function(e) {
                    updateRowCompleteness(this);
                },
                onPaste: function(e) {
                    // Summernote has built-in paste cleanup for Word content
                    // You can add custom paste handling here if needed
                }
            }
        });
    }

    // Initialisiere Summernote für alle vorhandenen Textareas
    document.querySelectorAll('textarea.transcript-textarea').forEach(textarea => {
        initializeSummernote('#' + textarea.id);
    });

    // Funktion zum Hinzufügen einer neuen Zeile
    function addRow(comic = {id: '', type: '', name: '', transcript: '', chapter: null}, isNew = true) {
        // Wenn die "Keine Einträge vorhanden"-Zeile existiert, entfernen
        if (noEntriesRow) {
            noEntriesRow.remove();
        }

        const newRow = document.createElement('tr');
        // Neue Einträge sind initial unvollständig, daher die Klasse
        if (isNew) {
            newRow.classList.add('incomplete-entry');
        }
        
        // Generiere eine temporäre ID für das neue Textarea
        const newTextareaId = 'transcript-textarea-' + Date.now();

        let typeOptionsHtml = '';
        comicTypeOptions.forEach(option => {
            typeOptionsHtml += `<option value="${htmlspecialchars(option)}" ${comic.type === option ? 'selected' : ''}>${htmlspecialchars(option)}</option>`;
        });

        let chapterInputHtml = `<input type="number" name="comic_chapter[]" value="${htmlspecialchars(comic.chapter ?? '')}" min="1">`;

        newRow.innerHTML = `
            <td><input type="text" name="comic_id[]" value="${htmlspecialchars(comic.id)}" ${isNew ? '' : 'readonly'}></td>
            <td>
                <select name="comic_type[]">
                    <option value="" ${comic.type === '' ? 'selected' : ''}>-- Auswählen --</option>
                    ${typeOptionsHtml}
                </select>
            </td>
            <td><input type="text" name="comic_name[]" value="${htmlspecialchars(comic.name)}"></td>
            <td>
                <textarea name="comic_transcript[]" class="transcript-textarea" id="${newTextareaId}">${comic.transcript}</textarea>
            </td>
            <td>
                ${chapterInputHtml}
            </td>
            <td><button type="button" class="remove-row">Entfernen</button></td>
        `;
        tableBody.appendChild(newRow);

        // Initialisiere Summernote für die neu hinzugefügte Textarea
        initializeSummernote('#' + newTextareaId);

        // Event Listener für Input-Änderungen, um die "incomplete-entry" Klasse zu aktualisieren
        newRow.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', () => updateRowCompleteness(input));
        });
        // Initial die Vollständigkeit der neuen Zeile prüfen
        updateRowCompleteness(newRow.querySelector('input, select, textarea'));
    }

    // Event Listener für "Neuen Comic-Eintrag hinzufügen" Button
    addEntryButton.addEventListener('click', function() {
        addRow();
    });

    // Event Listener für "Entfernen" Buttons (Delegation)
    tableBody.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-row')) {
            const rowToRemove = event.target.closest('tr');
            const textarea = rowToRemove.querySelector('.transcript-textarea');
            if (textarea && $(textarea).data('summernote')) { // Check if Summernote is initialized
                $(textarea).summernote('destroy'); // Summernote Instanz zerstören
            }
            rowToRemove.remove();

            // Wenn keine Zeilen mehr vorhanden, die "Keine Einträge vorhanden"-Zeile wieder hinzufügen
            if (tableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.id = 'no-entries-row';
                emptyRow.innerHTML = '<td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>';
                tableBody.appendChild(emptyRow);
            }
        }
    });

    // Funktion zur Überprüfung der Vollständigkeit einer Zeile
    function updateRowCompleteness(element) {
        const row = $(element).closest('tr')[0]; // Get the native DOM element
        if (!row) return;

        const comicIdInput = row.querySelector('input[name="comic_id[]"]');
        const typeSelect = row.querySelector('select[name="comic_type[]"]');
        const nameInput = row.querySelector('input[name="comic_name[]"]');
        const transcriptTextarea = row.querySelector('textarea[name="comic_transcript[]"]');
        const chapterInput = row.querySelector('input[name="comic_chapter[]"]');

        // Für Summernote: Inhalt über den Editor abrufen
        const transcriptContent = $(transcriptTextarea).summernote('isEmpty') ? '' : $(transcriptTextarea).summernote('code'); // Get HTML content

        const isComplete = comicIdInput.value.trim() !== '' &&
                           typeSelect.value.trim() !== '' &&
                           nameInput.value.trim() !== '' &&
                           transcriptContent.trim() !== '' && // Prüfe den HTML-Inhalt des Editors
                           chapterInput.value.trim() !== '' &&
                           parseInt(chapterInput.value) > 0;

        if (isComplete) {
            row.classList.remove('incomplete-entry');
        } else {
            row.classList.add('incomplete-entry');
        }
    }

    // Initialisiere die Vollständigkeitsprüfung für alle vorhandenen Zeilen
    document.querySelectorAll('#comic-data-editor-table tbody tr').forEach(row => {
        // Füge Event Listener für Input-Änderungen hinzu
        row.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', () => updateRowCompleteness(input));
        });
        // Summernote ruft onChange/onKeyup selbst auf, was updateRowCompleteness triggert
        // Initial die Vollständigkeit der Zeile prüfen
        updateRowCompleteness(row.querySelector('textarea.transcript-textarea'));
    });

    // Event Listener für den Speichern-Button (AJAX-Submission)
    saveButton.addEventListener('click', async function(event) {
        event.preventDefault(); // Standard-Formular-Submission verhindern

        const allComicData = [];
        document.querySelectorAll('#comic-data-editor-table tbody tr').forEach(row => {
            const comicIdInput = row.querySelector('input[name="comic_id[]"]');
            const typeSelect = row.querySelector('select[name="comic_type[]"]');
            const nameInput = row.querySelector('input[name="comic_name[]"]');
            const transcriptTextarea = row.querySelector('textarea[name="comic_transcript[]"]');
            const chapterInput = row.querySelector('input[name="comic_chapter[]"]');

            // Summernote Inhalt abrufen
            const transcriptContent = $(transcriptTextarea).summernote('code'); // Get HTML content

            allComicData.push({
                comic_id: comicIdInput.value.trim(),
                comic_type: typeSelect.value.trim(),
                comic_name: nameInput.value.trim(),
                comic_transcript: transcriptContent,
                comic_chapter: chapterInput.value.trim() !== '' ? parseInt(chapterInput.value) : null
            });
        });

        try {
            const response = await fetch(window.location.href, { // Sende an dieselbe Seite
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ pages: allComicData }) // Sende als JSON
            });

            const result = await response.json();

            if (result.status === 'success') {
                showMessage(result.message, 'success');
                // Optional: Seite neu laden, um die sortierte Liste zu sehen
                // window.location.reload(); 
            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Fehler beim Speichern der Comic-Daten:', error);
            showMessage('Ein Netzwerkfehler ist aufgetreten oder der Server hat nicht geantwortet.', 'error');
        }
    });

    // Funktion zum Anzeigen von Nachrichten
    function showMessage(msg, type) {
        if (messageBoxElement) {
            messageBoxElement.textContent = msg;
            messageBoxElement.className = 'message-box ' + type;
            messageBoxElement.style.display = 'block';
        } else {
            // Fallback, falls die Message Box nicht gefunden wird
            alert(msg);
        }
    }

    // Hilfsfunktion für HTML-Escaping in JavaScript (für Input-Werte, nicht für TinyMCE-Inhalt)
    function htmlspecialchars(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
}
?>
