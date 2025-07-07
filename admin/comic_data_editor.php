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

// --- Paginierungseinstellungen ---
const ITEMS_PER_PAGE = 50;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

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
 * Diese Funktion führt nun ein Merge durch, um nur die übergebenen Daten zu aktualisieren.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $newDataSubset Die neuen oder aktualisierten Daten (Subset der gesamten Daten).
 * @param array $deletedIds Eine Liste von IDs, die gelöscht werden sollen.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveComicData(string $path, array $newDataSubset, array $deletedIds = []): bool {
    $existingData = loadComicData($path); // Lade die bestehenden Daten
    
    // Aktualisiere bestehende Daten mit dem Subset und füge neue hinzu
    foreach ($newDataSubset as $id => $data) {
        $existingData[$id] = $data;
    }

    // Entferne gelöschte IDs
    foreach ($deletedIds as $id) {
        unset($existingData[$id]);
    }

    // Sortiere das Array alphabetisch nach Schlüsseln (Comic-IDs)
    ksort($existingData);
    $jsonContent = json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

    $updatedComicDataSubset = [];
    $deletedIds = [];

    if (isset($requestData['pages']) && is_array($requestData['pages'])) {
        foreach ($requestData['pages'] as $page) {
            $comicId = trim($page['comic_id']);
            if (empty($comicId)) {
                // Wenn die ID leer ist, wurde die Zeile im Frontend als gelöscht markiert oder ist ungültig
                // Wir fügen sie zur Liste der zu löschenden IDs hinzu, falls sie existiert
                if (isset($page['original_comic_id']) && !empty($page['original_comic_id'])) {
                    $deletedIds[] = $page['original_comic_id'];
                }
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

            $updatedComicDataSubset[$comicId] = [
                'type' => $type,
                'name' => $name,
                'transcript' => $transcript,
                'chapter' => $chapter
            ];
        }
    }

    // Speichere das Subset der Daten und verarbeite gelöschte IDs.
    if (saveComicData($comicVarJsonPath, $updatedComicDataSubset, $deletedIds)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Comic-Daten erfolgreich gespeichert!']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Speichern der Comic-Daten.']);
        exit;
    }
}

// Lade die gesamte Comic-Datenbank für Paginierung und fehlende IDs
$fullComicData = loadComicData($comicVarJsonPath);
$imageComicIds = getComicIdsFromImages($comicLowresDirPath, $comicHiresDirPath);

// Füge fehlende Comic-IDs aus den Bildern hinzu
foreach ($imageComicIds as $id) {
    if (!isset($fullComicData[$id])) {
        $fullComicData[$id] = [
            'type' => '', // Leer lassen für neue Einträge
            'name' => '', // Leer lassen
            'transcript' => '', // Leer lassen
            'chapter' => null // Leer lassen
        ];
    }
}

// Sortiere die gesamten Daten nach Comic-ID, bevor paginiert wird
ksort($fullComicData);

// --- Paginierungslogik anwenden ---
$totalItems = count($fullComicData);
$totalPages = ceil($totalItems / ITEMS_PER_PAGE);

// Sicherstellen, dass die aktuelle Seite nicht außerhalb des Bereichs liegt
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
} elseif ($totalPages == 0 && $totalItems > 0) { // Fallback, wenn totalPages 0 ist aber Items da sind (sollte nicht passieren)
     $currentPage = 1;
} elseif ($totalItems == 0) { // Wenn gar keine Items da sind
    $currentPage = 1;
    $totalPages = 1;
}


$offset = ($currentPage - 1) * ITEMS_PER_PAGE;
$paginatedComicData = array_slice($fullComicData, $offset, ITEMS_PER_PAGE, true); // true, um Keys zu erhalten

// Bericht über unvollständige Informationen (basierend auf allen Daten)
$incompleteInfoReportFull = [];
foreach ($fullComicData as $id => $data) {
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
        $incompleteInfoReportFull[$id] = $missingFields;
    }
}

// Bericht für die aktuelle Seite
$incompleteInfoReportCurrentPage = [];
foreach ($paginatedComicData as $id => $data) {
    if (isset($incompleteInfoReportFull[$id])) {
        $incompleteInfoReportCurrentPage[$id] = $incompleteInfoReportFull[$id];
    }
}

// Prüfen, ob auf anderen Seiten unvollständige Informationen vorhanden sind
$hasIncompleteOtherPages = false;
foreach ($incompleteInfoReportFull as $id => $fields) {
    if (!isset($paginatedComicData[$id])) { // Wenn die ID nicht auf der aktuellen Seite ist
        $hasIncompleteOtherPages = true;
        break;
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
    .comic-data-table select {
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

    /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        gap: 5px;
    }
    .pagination a, .pagination span {
        padding: 5px 10px;
        border: 1px solid #ccc;
        border-radius: 3px;
        text-decoration: none;
        color: #333;
        background-color: #f0f0f0;
    }
    .pagination a:hover {
        background-color: #e0e0e0;
    }
    .pagination span.current-page {
        background-color: #ddead7;
        font-weight: bold;
        color: #333;
        border-color: #ddead7;
    }
    .pagination a.disabled {
        pointer-events: none;
        opacity: 0.5;
    }

    /* Dark Theme Pagination */
    body.theme-night .pagination a,
    body.theme-night .pagination span {
        background-color: #2a6177;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .pagination a:hover {
        background-color: #48778a;
    }
    body.theme-night .pagination span.current-page {
        background-color: #48778a;
        color: #fff;
        border-color: #48778a;
    }

    /* In-place editing specific styles */
    .comic-data-table .display-mode,
    .comic-data-table .edit-mode {
        display: block; /* Default to block for proper layout */
    }

    .comic-data-table .edit-mode {
        display: none; /* Hidden by default */
    }

    .comic-data-table tr.editing .display-mode {
        display: none; /* Hide display elements when editing */
    }

    .comic-data-table tr.editing .edit-mode {
        display: block; /* Show edit elements when editing */
    }

    /* Adjust Summernote for edit mode */
    .comic-data-table .edit-mode .note-editor {
        display: block !important; /* Ensure Summernote is visible in edit mode */
    }

    .comic-data-table .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: flex-end;
    }
    .comic-data-table .action-buttons .edit-button,
    .comic-data-table .action-buttons .save-button,
    .comic-data-table .action-buttons .cancel-button {
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.9em;
        white-space: nowrap; /* Prevent buttons from wrapping */
    }
    .comic-data-table .action-buttons .edit-button {
        background-color: #007bff;
        color: white;
        border: none;
    }
    .comic-data-table .action-buttons .edit-button:hover {
        background-color: #0056b3;
    }
    .comic-data-table .action-buttons .save-button {
        background-color: #28a745;
        color: white;
        border: none;
    }
    .comic-data-table .action-buttons .save-button:hover {
        background-color: #218838;
    }
    .comic-data-table .action-buttons .cancel-button {
        background-color: #6c757d;
        color: white;
        border: none;
    }
    .comic-data-table .action-buttons .cancel-button:hover {
        background-color: #5a6268;
    }
    /* Hide save/cancel buttons in display mode */
    .comic-data-table .action-buttons .save-button,
    .comic-data-table .action-buttons .cancel-button {
        display: none;
    }
    .comic-data-table tr.editing .action-buttons .edit-button {
        display: none;
    }
    .comic-data-table tr.editing .action-buttons .save-button,
    .comic-data-table tr.editing .action-buttons .cancel-button {
        display: inline-block; /* Show in edit mode */
    }

    /* Dark Theme for action buttons */
    body.theme-night .comic-data-table .action-buttons .edit-button {
        background-color: #48778a;
    }
    body.theme-night .comic-data-table .action-buttons .edit-button:hover {
        background-color: #628492;
    }
    body.theme-night .comic-data-table .action-buttons .save-button {
        background-color: #2a6177;
    }
    body.theme-night .comic-data-table .action-buttons .save-button:hover {
        background-color: #48778a;
    }
    body.theme-night .comic-data-table .action-buttons .cancel-button {
        background-color: #6c757d;
    }
    body.theme-night .comic-data-table .action-buttons .cancel-button:hover {
        background-color: #5a6268;
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
                <?php if (!empty($paginatedComicData)): ?>
                    <?php foreach ($paginatedComicData as $id => $data):
                        // Bestimme, ob der Eintrag unvollständig ist, um die Klasse zu setzen
                        $isTypeMissing = empty($data['type']);
                        $isNameMissing = empty($data['name']);
                        $isTranscriptMissing = empty($data['transcript']);
                        $isChapterMissing = ($data['chapter'] === null || $data['chapter'] <= 0);
                        $rowClass = ($isTypeMissing || $isNameMissing || $isTranscriptMissing || $isChapterMissing) ? 'incomplete-entry' : '';
                    ?>
                        <tr class="<?php echo $rowClass; ?>" data-comic-id="<?php echo htmlspecialchars($id); ?>">
                            <td>
                                <span class="display-mode"><?php echo htmlspecialchars($id); ?></span>
                                <div class="edit-mode">
                                    <input type="text" name="comic_id[]" value="<?php echo htmlspecialchars($id); ?>" readonly>
                                    <input type="hidden" name="original_comic_id[]" value="<?php echo htmlspecialchars($id); ?>"> <!-- Hidden field for original ID for deletion -->
                                </div>
                            </td>
                            <td>
                                <span class="display-mode"><?php echo htmlspecialchars($data['type']); ?></span>
                                <div class="edit-mode">
                                    <select name="comic_type[]">
                                        <option value="" <?php echo ($data['type'] == '') ? 'selected' : ''; ?>>-- Auswählen --</option>
                                        <?php foreach ($comicTypeOptions as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($data['type'] == $option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <span class="display-mode"><?php echo htmlspecialchars($data['name']); ?></span>
                                <div class="edit-mode">
                                    <input type="text" name="comic_name[]" value="<?php echo htmlspecialchars($data['name']); ?>">
                                </div>
                            </td>
                            <td>
                                <span class="display-mode transcript-display"><?php echo $data['transcript']; ?></span>
                                <div class="edit-mode">
                                    <textarea name="comic_transcript[]" class="transcript-textarea" id="transcript-<?php echo htmlspecialchars($id); ?>"><?php echo $data['transcript']; ?></textarea>
                                </div>
                            </td>
                            <td>
                                <span class="display-mode"><?php echo htmlspecialchars($data['chapter'] ?? ''); ?></span>
                                <div class="edit-mode">
                                    <input type="number" name="comic_chapter[]" value="<?php echo htmlspecialchars($data['chapter'] ?? ''); ?>" min="1">
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="edit-button">Bearbeiten</button>
                                    <button type="button" class="save-button">Speichern</button>
                                    <button type="button" class="cancel-button">Abbrechen</button>
                                    <button type="button" class="remove-row">Entfernen</button>
                                </div>
                            </td>
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
            <!-- Der globale Speichern-Button wurde entfernt, da das Speichern pro Zeile erfolgt. -->
        </div>
    </form>

    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>" class="prev-page">Zurück</a>
        <?php else: ?>
            <span class="prev-page disabled">Zurück</span>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <span class="current-page"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>" class="next-page">Weiter</a>
        <?php else: ?>
            <span class="next-page disabled">Weiter</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($incompleteInfoReportFull)): ?>
        <div class="incomplete-report">
            <h3>Informationen fehlen:</h3>
            <?php if (!empty($incompleteInfoReportCurrentPage)): ?>
                <h4>Auf dieser Seite (Seite <?php echo $currentPage; ?>):</h4>
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
                        <?php foreach ($paginatedComicData as $id => $data): // Hier paginierte Daten nutzen
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
            <?php else: ?>
                <p>Auf dieser Seite (Seite <?php echo $currentPage; ?>) sind alle Informationen vollständig.</p>
            <?php endif; ?>

            <?php if ($hasIncompleteOtherPages): ?>
                <p style="margin-top: 15px;">**Hinweis:** Es fehlen auch Informationen auf anderen Seiten.</p>
            <?php endif; ?>
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
    const messageBoxElement = document.querySelector('.message-box');

    let hasUnsavedChanges = false;
    const originalRowData = new Map(); // Store original data for each row for "Cancel"

    // Summernote Initialisierung
    function initializeSummernote(selector) {
        $(selector).summernote({
            height: 150,
            minHeight: null,
            maxHeight: null,
            focus: false,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['font', ['fontsize', 'color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'hr']],
                ['view', ['codeview', 'fullscreen', 'help']]
            ],
            callbacks: {
                onChange: function(contents, $editable) {
                    $(this).val(contents);
                    updateRowCompleteness(this);
                    setUnsavedChanges(true);
                },
                onKeyup: function(e) {
                    updateRowCompleteness(this);
                    setUnsavedChanges(true);
                },
                onPaste: function(e) {
                    setUnsavedChanges(true);
                }
            }
        });
    }

    // Funktion zum Umschalten des Bearbeitungsmodus einer Zeile
    function toggleEditMode(row, enable) {
        if (enable) {
            // Speichere die Originaldaten vor dem Bearbeiten
            const comicId = row.dataset.comicId;
            const typeSelect = row.querySelector('select[name="comic_type[]"]');
            const nameInput = row.querySelector('input[name="comic_name[]"]');
            const transcriptTextarea = row.querySelector('textarea[name="comic_transcript[]"]');
            const chapterInput = row.querySelector('input[name="comic_chapter[]"]');

            originalRowData.set(comicId, {
                type: typeSelect.value,
                name: nameInput.value,
                transcript: transcriptTextarea.value, // HTML content
                chapter: chapterInput.value
            });

            row.classList.add('editing');
            // Initialisiere Summernote, wenn die Textarea sichtbar wird
            initializeSummernote('#' + transcriptTextarea.id);
        } else {
            row.classList.remove('editing');
            // Zerstöre Summernote, wenn die Textarea ausgeblendet wird
            const textarea = row.querySelector('.transcript-textarea');
            if (textarea && $(textarea).data('summernote')) {
                $(textarea).summernote('destroy');
            }
        }
    }

    // Funktion zum Hinzufügen einer neuen Zeile
    function addRow(comic = {id: '', type: '', name: '', transcript: '', chapter: null}, isNew = true) {
        if (noEntriesRow) {
            noEntriesRow.remove();
        }

        const newRow = document.createElement('tr');
        if (isNew) {
            newRow.classList.add('incomplete-entry');
            newRow.classList.add('editing'); // Neue Zeilen starten im Bearbeitungsmodus
        }
        
        const newTextareaId = 'transcript-textarea-' + Date.now();
        const comicIdDisplay = comic.id ? htmlspecialchars(comic.id) : 'Neue ID eingeben';
        const typeDisplay = comic.type ? htmlspecialchars(comic.type) : '---';
        const nameDisplay = comic.name ? htmlspecialchars(comic.name) : '---';
        const transcriptDisplay = comic.transcript ? comic.transcript : '---'; // HTML content
        const chapterDisplay = comic.chapter ? htmlspecialchars(comic.chapter) : '---';

        let typeOptionsHtml = '<option value="" ' + (comic.type === '' ? 'selected' : '') + '>-- Auswählen --</option>';
        <?php foreach ($comicTypeOptions as $option): ?>
            typeOptionsHtml += `<option value="<?php echo htmlspecialchars($option); ?>" ${comic.type === "<?php echo htmlspecialchars($option); ?>" ? 'selected' : ''}>
                                <?php echo htmlspecialchars($option); ?>
                            </option>`;
        <?php endforeach; ?>

        newRow.innerHTML = `
            <td>
                <span class="display-mode">${comicIdDisplay}</span>
                <div class="edit-mode">
                    <input type="text" name="comic_id[]" value="${htmlspecialchars(comic.id)}" ${isNew ? '' : 'readonly'}>
                    <input type="hidden" name="original_comic_id[]" value="${htmlspecialchars(comic.id)}">
                </div>
            </td>
            <td>
                <span class="display-mode">${typeDisplay}</span>
                <div class="edit-mode">
                    <select name="comic_type[]">
                        ${typeOptionsHtml}
                    </select>
                </div>
            </td>
            <td>
                <span class="display-mode">${nameDisplay}</span>
                <div class="edit-mode">
                    <input type="text" name="comic_name[]" value="${htmlspecialchars(comic.name)}">
                </div>
            </td>
            <td>
                <span class="display-mode transcript-display">${transcriptDisplay}</span>
                <div class="edit-mode">
                    <textarea name="comic_transcript[]" class="transcript-textarea" id="${newTextareaId}">${comic.transcript}</textarea>
                </div>
            </td>
            <td>
                <span class="display-mode">${chapterDisplay}</span>
                <div class="edit-mode">
                    <input type="number" name="comic_chapter[]" value="${htmlspecialchars(comic.chapter ?? '')}" min="1">
                </div>
            </td>
            <td>
                <div class="action-buttons">
                    <button type="button" class="edit-button">Bearbeiten</button>
                    <button type="button" class="save-button">Speichern</button>
                    <button type="button" class="cancel-button">Abbrechen</button>
                    <button type="button" class="remove-row">Entfernen</button>
                </div>
            </td>
        `;
        tableBody.appendChild(newRow);

        // Setze die data-comic-id für die neue Zeile
        if (isNew) {
            newRow.dataset.comicId = comic.id; // Initial leer, wird beim Speichern gesetzt
            // Initialisiere Summernote für die neu hinzugefügte Textarea
            initializeSummernote('#' + newTextareaId);
        } else {
            // Für bestehende Zeilen, die beim Laden gerendert werden, aber nicht im Bearbeitungsmodus starten
            // Die Summernote-Initialisierung erfolgt erst beim Klick auf "Bearbeiten"
        }

        // Event Listener für Input-Änderungen, um die "incomplete-entry" Klasse zu aktualisieren
        newRow.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', () => {
                updateRowCompleteness(input);
                setUnsavedChanges(true);
            });
        });
        // Initial die Vollständigkeit der neuen Zeile prüfen
        updateRowCompleteness(newRow.querySelector('input, select, textarea'));
        setUnsavedChanges(true); // Neue Zeile bedeutet ungespeicherte Änderungen
    }

    // Event Listener für "Neuen Comic-Eintrag hinzufügen" Button
    addEntryButton.addEventListener('click', function() {
        addRow();
    });

    // Event Listener für Buttons (Delegation)
    tableBody.addEventListener('click', async function(event) {
        const target = event.target;
        const row = target.closest('tr');
        if (!row) return;

        const comicId = row.dataset.comicId; // Die ID der Zeile

        if (target.classList.contains('edit-button')) {
            toggleEditMode(row, true);
        } else if (target.classList.contains('save-button')) {
            event.preventDefault(); // Formular-Submission verhindern

            const comicIdInput = row.querySelector('input[name="comic_id[]"]');
            const typeSelect = row.querySelector('select[name="comic_type[]"]');
            const nameInput = row.querySelector('input[name="comic_name[]"]');
            const transcriptTextarea = row.querySelector('.transcript-textarea');
            const chapterInput = row.querySelector('input[name="comic_chapter[]"]');

            // Summernote Inhalt abrufen
            const transcriptContent = $(transcriptTextarea).summernote('code'); // Get HTML content

            const dataToSave = {
                comic_id: comicIdInput.value.trim(),
                comic_type: typeSelect.value.trim(),
                comic_name: nameInput.value.trim(),
                comic_transcript: transcriptContent,
                comic_chapter: chapterInput.value.trim() !== '' ? parseInt(chapterInput.value) : null
            };

            // Validierung für neue Einträge: Comic ID muss 8 Ziffern sein
            if (comicId === '' && !/^\d{8}$/.test(dataToSave.comic_id)) {
                showMessage('Neue Comic ID muss 8 Ziffern enthalten.', 'error');
                return;
            }

            // Wenn die ID geändert wurde, muss die alte ID zum Löschen mitgesendet werden
            let originalComicId = row.querySelector('input[name="original_comic_id[]"]').value;
            let deletedIds = [];
            if (originalComicId && originalComicId !== dataToSave.comic_id) {
                deletedIds.push(originalComicId);
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ pages: [dataToSave], deleted_ids: deletedIds })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showMessage(result.message, 'success');
                    setUnsavedChanges(false);
                    // Aktualisiere die angezeigten Werte und schalte den Modus um
                    row.querySelector('.display-mode:nth-child(1)').textContent = dataToSave.comic_id;
                    row.querySelector('.display-mode:nth-child(2)').textContent = dataToSave.comic_type;
                    row.querySelector('.display-mode:nth-child(3)').textContent = dataToSave.comic_name;
                    row.querySelector('.transcript-display').innerHTML = dataToSave.comic_transcript; // Use innerHTML for HTML content
                    row.querySelector('.display-mode:nth-child(5)').textContent = dataToSave.comic_chapter;
                    
                    // Aktualisiere die data-comic-id der Zeile, falls die ID geändert wurde
                    row.dataset.comicId = dataToSave.comic_id;
                    row.querySelector('input[name="original_comic_id[]"]').value = dataToSave.comic_id;

                    toggleEditMode(row, false); // Zurück zum Anzeigemodus
                    updateRowCompleteness(row); // Status der Zeile aktualisieren
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Fehler beim Speichern der Comic-Daten:', error);
                showMessage('Ein Netzwerkfehler ist aufgetreten oder der Server hat nicht geantwortet.', 'error');
            }
        } else if (target.classList.contains('cancel-button')) {
            const comicId = row.dataset.comicId;
            const originalData = originalRowData.get(comicId);

            if (originalData) {
                // Setze die Felder auf die Originalwerte zurück
                row.querySelector('select[name="comic_type[]"]').value = originalData.type;
                row.querySelector('input[name="comic_name[]"]').value = originalData.name;
                row.querySelector('textarea[name="comic_transcript[]"]').value = originalData.transcript;
                row.querySelector('input[name="comic_chapter[]"]').value = originalData.chapter;
            } else {
                // Wenn keine Originaldaten vorhanden (z.B. neue, noch nicht gespeicherte Zeile), entfernen
                const textarea = row.querySelector('.transcript-textarea');
                if (textarea && $(textarea).data('summernote')) {
                    $(textarea).summernote('destroy');
                }
                row.remove();
                if (tableBody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.id = 'no-entries-row';
                    emptyRow.innerHTML = '<td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>';
                    tableBody.appendChild(emptyRow);
                }
            }
            toggleEditMode(row, false); // Zurück zum Anzeigemodus
            updateRowCompleteness(row); // Status der Zeile aktualisieren
            setUnsavedChanges(false); // Änderungen wurden verworfen
        } else if (target.classList.contains('remove-row')) {
            event.preventDefault();
            if (!confirm('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?')) {
                return;
            }

            const comicIdToDelete = row.dataset.comicId;
            if (!comicIdToDelete) { // Für neu hinzugefügte, noch nicht gespeicherte Zeilen
                const textarea = row.querySelector('.transcript-textarea');
                if (textarea && $(textarea).data('summernote')) {
                    $(textarea).summernote('destroy');
                }
                row.remove();
                if (tableBody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.id = 'no-entries-row';
                    emptyRow.innerHTML = '<td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>';
                    tableBody.appendChild(emptyRow);
                }
                setUnsavedChanges(true);
                return;
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ deleted_ids: [comicIdToDelete] })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showMessage('Eintrag erfolgreich gelöscht!', 'success');
                    setUnsavedChanges(false);
                    const textarea = row.querySelector('.transcript-textarea');
                    if (textarea && $(textarea).data('summernote')) {
                        $(textarea).summernote('destroy');
                    }
                    row.remove();
                    if (tableBody.children.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.id = 'no-entries-row';
                        emptyRow.innerHTML = '<td colspan="6" style="text-align: center;">Keine Comic-Einträge vorhanden. Füge neue hinzu oder lade Bilder hoch.</td>';
                        tableBody.appendChild(emptyRow);
                    }
                    // Optional: Seite neu laden, um die aktualisierte Paginierung zu sehen
                    window.location.reload(); 
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Fehler beim Löschen des Eintrags:', error);
                showMessage('Ein Netzwerkfehler ist aufgetreten oder der Server hat nicht geantwortet.', 'error');
            }
        }
    });

    // Funktion zur Überprüfung der Vollständigkeit einer Zeile
    function updateRowCompleteness(element) {
        // Find the closest row element, regardless of the input element type
        let row;
        if (element && element.jquery) { // If element is a jQuery object (from Summernote callback)
            row = element[0].closest('tr');
        } else if (element) { // If element is a native DOM element (from regular input event)
            row = element.closest('tr');
        } else { // Fallback if no element is provided, try to find current active row
            row = document.querySelector('tr.editing');
        }
        
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

    // Initialisiere die Vollständigkeitsprüfung für alle vorhandenen Zeilen im Anzeigemodus
    document.querySelectorAll('#comic-data-editor-table tbody tr').forEach(row => {
        // Initial die Vollständigkeit der Zeile prüfen (für Anzeigemodus)
        // Die Bearbeitungsfelder sind noch nicht initialisiert, daher prüfen wir die angezeigten Spans
        const comicIdDisplay = row.querySelector('.display-mode:nth-child(1)').textContent.trim();
        const typeDisplay = row.querySelector('.display-mode:nth-child(2)').textContent.trim();
        const nameDisplay = row.querySelector('.display-mode:nth-child(3)').textContent.trim();
        const transcriptDisplay = row.querySelector('.transcript-display').innerHTML.trim();
        const chapterDisplay = row.querySelector('.display-mode:nth-child(5)').textContent.trim();

        const isComplete = comicIdDisplay !== '' &&
                           typeDisplay !== '---' && // Prüfe auf Standard-Platzhalter
                           nameDisplay !== '---' &&
                           transcriptDisplay !== '---' &&
                           chapterDisplay !== '---' &&
                           parseInt(chapterDisplay) > 0;

        if (isComplete) {
            row.classList.remove('incomplete-entry');
        } else {
            row.classList.add('incomplete-entry');
        }
    });

    // Funktion zum Anzeigen von Nachrichten
    function showMessage(msg, type) {
        if (messageBoxElement) {
            messageBoxElement.textContent = msg;
            messageBoxElement.className = 'message-box ' + type;
            messageBoxElement.style.display = 'block';
            setTimeout(() => {
                messageBoxElement.style.display = 'none'; // Nachricht nach 5 Sekunden ausblenden
            }, 5000);
        } else {
            alert(msg);
        }
    }

    // Funktion zum Setzen des "ungespeicherte Änderungen"-Flags
    function setUnsavedChanges(status) {
        hasUnsavedChanges = status;
    }

    // Warnung vor dem Verlassen der Seite bei ungespeicherten Änderungen
    window.addEventListener('beforeunload', function(event) {
        if (hasUnsavedChanges) {
            event.preventDefault(); // Standardaktion (Seite verlassen) verhindern
            event.returnValue = ''; // Für ältere Browser
            return ''; // Für moderne Browser
        }
    });

    // Hilfsfunktion für HTML-Escaping in JavaScript (für Input-Werte)
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
