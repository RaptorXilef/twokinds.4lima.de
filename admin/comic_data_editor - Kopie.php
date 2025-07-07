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
$comicTypeOptions = ['Comicseite', 'Lückenfüller']; 
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
    // Korrektur für leere Transkripte: <p><br></p> als leer behandeln
    $transcriptContent = trim(strip_tags($data['transcript'], '<br>')); // Entferne HTML-Tags, außer <br>
    $isTranscriptEffectivelyEmpty = (empty($transcriptContent) || $transcriptContent === '<br>' || $transcriptContent === '&nbsp;');

    if (empty($data['type'])) {
        $missingFields[] = 'type';
    }
    if (empty($data['name'])) {
        $missingFields[] = 'name';
    }
    if ($isTranscriptEffectivelyEmpty) { // Verwende die korrigierte Prüfung
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

// Ermittle, welche Seiten in der Paginierung unvollständige Daten haben
$pagesWithIncompleteData = [];
if (!empty($incompleteInfoReportFull)) {
    $allComicIds = array_keys($fullComicData);
    foreach ($incompleteInfoReportFull as $id => $fields) {
        $index = array_search($id, $allComicIds);
        if ($index !== false) {
            $pageNumber = floor($index / ITEMS_PER_PAGE) + 1;
            $pagesWithIncompleteData[$pageNumber] = true;
        }
    }
}


// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    /* Allgemeine Layout-Anpassungen */
    .admin-container {
        padding: 20px;
        max-width: 1200px;
        margin: 20px auto;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    body.theme-night .admin-container {
        background-color: #00334c; /* Dunklerer Hintergrund für den Container im Dark Mode */
        color: #fff;
    }

    .message-box {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
        display: none; /* Standardmäßig ausgeblendet */
    }

    .message-box.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message-box.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    body.theme-night .message-box.success {
        background-color: #28a745; /* Dunkelgrün */
        color: #fff;
        border-color: #218838;
    }

    body.theme-night .message-box.error {
        background-color: #dc3545; /* Dunkelrot */
        color: #fff;
        border-color: #c82333;
    }

    /* Formular- und Button-Stile */
    .form-section {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    body.theme-night .form-section {
        background-color: #00425c; /* Dunklerer Hintergrund für Formularbereiche */
    }

    .form-section h2 {
        color: #333;
        margin-bottom: 20px;
        font-size: 1.5em;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    body.theme-night .form-section h2 {
        color: #fff;
        border-bottom-color: #005a7e;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    body.theme-night .form-group label {
        color: #ccc;
    }

    .form-group input[type="text"],
    .form-group select,
    .form-group textarea {
        width: calc(100% - 22px); /* Padding berücksichtigen */
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1em;
        box-sizing: border-box; /* Padding und Border in der Breite enthalten */
        background-color: #fff;
        color: #333;
    }

    body.theme-night .form-group input[type="text"],
    body.theme-night .form-group select,
    body.theme-night .form-group textarea {
        background-color: #005a7e;
        border-color: #007bb5;
        color: #fff;
    }

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .button-group {
        text-align: right;
        margin-top: 20px;
    }

    button, .button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        background-color: #007bff;
        color: white;
        font-size: 1em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        text-decoration: none; /* Für .button Klasse */
        display: inline-block; /* Für .button Klasse */
        margin-left: 10px; /* Abstand zwischen Buttons */
    }

    button:hover, .button:hover {
        background-color: #0056b3;
    }

    button.delete, .button.delete {
        background-color: #dc3545;
    }

    button.delete:hover, .button.delete:hover {
        background-color: #c82333;
    }

    button.edit, .button.edit {
        background-color: #ffc107;
        color: #333;
    }

    button.edit:hover, .button.edit:hover {
        background-color: #e0a800;
    }

    body.theme-night button, body.theme-night .button {
        background-color: #2a6177;
    }

    body.theme-night button:hover, body.theme-night .button:hover {
        background-color: #48778a;
    }

    body.theme-night button.delete, body.theme-night .button.delete {
        background-color: #dc3545;
    }

    body.theme-night button.delete:hover, body.theme-night .button.delete:hover {
        background-color: #c82333;
    }

    body.theme-night button.edit, body.theme-night .button.edit {
        background-color: #ffc107;
        color: #333; /* Textfarbe bleibt dunkel für Kontrast */
    }

    body.theme-night button.edit:hover, body.theme-night .button.edit:hover {
        background-color: #e0a800;
    }

    /* Comic-Liste und Tabelle */
    .comic-list-section {
        margin-top: 30px;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    body.theme-night .comic-list-section {
        background-color: #00425c;
    }

    .comic-list-section h2 {
        color: #333;
        margin-bottom: 20px;
        font-size: 1.5em;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    body.theme-night .comic-list-section h2 {
        color: #fff;
        border-bottom-color: #005a7e;
    }

    .comic-table-container {
        overflow-x: auto; /* Ermöglicht horizontales Scrollen bei kleinen Bildschirmen */
    }

    .comic-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .comic-table th, .comic-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }

    body.theme-night .comic-table th, body.theme-night .comic-table td {
        border-color: #005a7e;
    }

    .comic-table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    body.theme-night .comic-table th {
        background-color: #005a7e;
        color: #fff;
    }

    .comic-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    body.theme-night .comic-table tr:nth-child(even) {
        background-color: #004c6b;
    }

    .comic-table tr:hover {
        background-color: #f1f1f1;
    }

    body.theme-night .comic-table tr:hover {
        background-color: #006690;
    }

    .comic-table td .editable-field {
        width: 100%;
        padding: 5px;
        border: 1px solid #eee; /* Leichter Rand im Nicht-Bearbeitungsmodus */
        border-radius: 3px;
        box-sizing: border-box;
        background-color: transparent; /* Standardmäßig transparent */
        cursor: pointer; /* Zeigt an, dass es klickbar ist */
        min-height: 30px; /* Mindesthöhe für leere Felder */
        display: block; /* Stellt sicher, dass es die volle Breite einnimmt */
        color: #333; /* Standardtextfarbe */
    }

    body.theme-night .comic-table td .editable-field {
        border-color: #005a7e;
        color: #fff;
    }

    .comic-table td .editable-field:hover {
        border-color: #ccc; /* Rand beim Hover */
    }

    body.theme-night .comic-table td .editable-field:hover {
        border-color: #007bb5;
    }

    .comic-table td .editable-field.editing {
        border-color: #007bff; /* Blauer Rand im Bearbeitungsmodus */
        background-color: #fff; /* Weißer Hintergrund im Bearbeitungsmodus */
        cursor: text;
    }

    body.theme-night .comic-table td .editable-field.editing {
        background-color: #005a7e;
        border-color: #007bff;
    }

    .comic-table td .editable-field.missing-info {
        border: 2px solid #dc3545; /* Roter Rand für fehlende Infos */
        background-color: #f8d7da; /* Hellroter Hintergrund */
    }

    body.theme-night .comic-table td .editable-field.missing-info {
        border-color: #ff4d4d; /* Helleres Rot */
        background-color: #721c24; /* Dunkleres Rot */
    }

    .comic-table td .actions {
        white-space: nowrap; /* Buttons bleiben in einer Zeile */
    }

    .comic-table td .actions button {
        margin-left: 5px;
        padding: 5px 10px;
        font-size: 0.9em;
    }

    /* Paginierung */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        flex-wrap: wrap; /* Ermöglicht Umbruch auf kleineren Bildschirmen */
    }

    .pagination a, .pagination span {
        padding: 8px 12px;
        margin: 0 4px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #007bff;
        background-color: #fff;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.theme-night .pagination a, body.theme-night .pagination span {
        border-color: #005a7e;
        color: #7bbdff;
        background-color: #004c6b;
    }

    .pagination a:hover {
        background-color: #e9ecef;
        color: #0056b3;
    }

    body.theme-night .pagination a:hover {
        background-color: #005a7e;
        color: #a0d0ff;
    }

    .pagination .current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        font-weight: bold;
    }

    body.theme-night .pagination .current-page {
        background-color: #007bff; /* Bleibt blau, da es die aktive Seite ist */
        border-color: #007bff;
        color: white;
    }

    .pagination .incomplete-page {
        background-color: #f8d7da; /* Hellrot */
        border-color: #f5c6cb;
        color: #721c24;
    }

    body.theme-night .pagination .incomplete-page {
        background-color: #dc3545; /* Dunkelrot */
        border-color: #c82333;
        color: #fff;
    }

    .pagination .incomplete-page.current-page {
        background-color: #dc3545; /* Roter Hintergrund für aktuelle unvollständige Seite */
        color: white;
    }

    .pagination .incomplete-page:hover {
        background-color: #f5c6cb;
    }

    body.theme-night .pagination .incomplete-page:hover {
        background-color: #c82333;
    }

    .pagination .disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Bericht über fehlende Informationen */
    .report-section {
        margin-top: 30px;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    body.theme-night .report-section {
        background-color: #00425c;
    }

    .report-section h2 {
        color: #333;
        margin-bottom: 20px;
        font-size: 1.5em;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    body.theme-night .report-section h2 {
        color: #fff;
        border-bottom-color: #005a7e;
    }

    .report-section ul {
        list-style: none;
        padding: 0;
    }

    .report-section li {
        margin-bottom: 8px;
        color: #555;
    }

    body.theme-night .report-section li {
        color: #ccc;
    }

    .report-section li strong {
        color: #dc3545; /* Rot für fehlende Felder */
    }

    body.theme-night .report-section li strong {
        color: #ff4d4d; /* Helleres Rot */
    }

    .report-section .missing-other-pages-warning {
        margin-top: 15px;
        padding: 10px;
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 5px;
        color: #856404;
    }

    body.theme-night .report-section .missing-other-pages-warning {
        background-color: #6c5b00; /* Dunkleres Gelb */
        border-color: #927c00;
        color: #fff;
    }

    /* Floating Save Button */
    .floating-save-button {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #28a745; /* Grün */
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        font-size: 1.2em;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 1000;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .floating-save-button:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }

    .floating-save-button.disabled {
        background-color: #6c757d; /* Grau */
        cursor: not-allowed;
        box-shadow: none;
    }

    body.theme-night .floating-save-button {
        background-color: #1e7e34; /* Dunkelgrün */
    }

    body.theme-night .floating-save-button:hover {
        background-color: #1c7430;
    }

    body.theme-night .floating-save-button.disabled {
        background-color: #495057;
    }

    /* Floating Add Button */
    .floating-add-button {
        position: fixed;
        bottom: 90px; /* Über dem Save Button */
        right: 20px;
        background-color: #17a2b8; /* Cyan */
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        font-size: 1.2em;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 1000;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .floating-add-button:hover {
        background-color: #138496;
        transform: translateY(-2px);
    }

    body.theme-night .floating-add-button {
        background-color: #117a8b;
    }

    body.theme-night .floating-add-button:hover {
        background-color: #0f6674;
    }

    /* Responsive Anpassungen */
    @media (max-width: 768px) {
        .admin-container {
            padding: 10px;
            margin: 10px auto;
        }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: calc(100% - 20px); /* Anpassung für kleinere Bildschirme */
        }

        .comic-table th, .comic-table td {
            padding: 6px;
            font-size: 0.9em;
        }

        .comic-table td .actions button {
            padding: 3px 6px;
            font-size: 0.8em;
            margin-left: 2px;
        }

        .pagination a, .pagination span {
            padding: 6px 10px;
            margin: 0 2px;
            font-size: 0.9em;
        }

        .floating-save-button, .floating-add-button {
            padding: 12px 20px;
            font-size: 1em;
            bottom: 10px;
            right: 10px;
        }

        .floating-add-button {
            bottom: 70px; /* Anpassung für kleinere Bildschirme */
        }
    }
</style>

<div class="admin-container">
    <div id="message-box" class="message-box"></div>

    <section class="form-section">
        <h2>Comic-Eintrag bearbeiten / hinzufügen</h2>
        <form id="comic-edit-form">
            <input type="hidden" id="original-comic-id" name="original_comic_id" value="">
            <div class="form-group">
                <label for="comic-id">Comic ID (Datum JJJJMMTT):</label>
                <input type="text" id="comic-id" name="comic_id" pattern="\d{8}" title="Bitte geben Sie eine 8-stellige Zahl (JJJJMMTT) ein." required>
            </div>
            <div class="form-group">
                <label for="comic-type">Typ:</label>
                <select id="comic-type" name="comic_type" required>
                    <?php foreach ($comicTypeOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="comic-name">Name:</label>
                <input type="text" id="comic-name" name="comic_name" required>
            </div>
            <div class="form-group">
                <label for="comic-transcript">Transkript (HTML erlaubt):</label>
                <textarea id="comic-transcript" name="comic_transcript"></textarea>
            </div>
            <div class="form-group">
                <label for="comic-chapter">Kapitel:</label>
                <select id="comic-chapter" name="comic_chapter" required>
                    <option value="">Bitte auswählen</option>
                    <?php foreach ($chapterOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="button-group">
                <button type="submit" id="save-single-button">Speichern</button>
                <button type="button" id="cancel-edit-button">Abbrechen</button>
            </div>
        </form>
    </section>

    <section class="comic-list-section">
        <h2>Bearbeitungsübersicht Comic-Daten</h2>
        <div class="comic-table-container">
            <table class="comic-table" id="comic-data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Typ</th>
                        <th>Name</th>
                        <th>Transkript</th>
                        <th>Kapitel</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedComicData)): ?>
                        <tr>
                            <td colspan="6">Keine Comic-Daten gefunden oder auf dieser Seite verfügbar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedComicData as $id => $data):
                            $isMissingInfo = isset($incompleteInfoReportCurrentPage[$id]);
                        ?>
                            <tr data-comic-id="<?php echo htmlspecialchars($id); ?>" class="<?php echo $isMissingInfo ? 'missing-info-row' : ''; ?>">
                                <td class="comic-id-display"><?php echo htmlspecialchars($id); ?></td>
                                <td><span class="editable-field comic-type-display <?php echo (isset($incompleteInfoReportCurrentPage[$id]) && in_array('type', $incompleteInfoReportCurrentPage[$id])) ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['type']); ?></span></td>
                                <td><span class="editable-field comic-name-display <?php echo (isset($incompleteInfoReportCurrentPage[$id]) && in_array('name', $incompleteInfoReportCurrentPage[$id])) ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['name']); ?></span></td>
                                <td><span class="editable-field comic-transcript-display <?php echo (isset($incompleteInfoReportCurrentPage[$id]) && in_array('transcript', $incompleteInfoReportCurrentPage[$id])) ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['transcript']); ?></span></td>
                                <td><span class="editable-field comic-chapter-display <?php echo (isset($incompleteInfoReportCurrentPage[$id]) && in_array('chapter', $incompleteInfoReportCurrentPage[$id])) ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['chapter'] ?? ''); ?></span></td>
                                <td class="actions">
                                    <button type="button" class="edit-button button edit"><i class="fas fa-edit"></i> Bearbeiten</button>
                                    <button type="button" class="delete-button button delete"><i class="fas fa-trash-alt"></i> Löschen</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <button type="button" id="add-new-comic-button" class="button"><i class="fas fa-plus"></i> Neuen Comic-Eintrag hinzufügen (+)</button>

        <!-- Paginierung -->
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=1" title="Erste Seite"><i class="fas fa-angle-double-left"></i></a>
                <a href="?page=<?php echo $currentPage - 1; ?>" title="Vorherige Seite"><i class="fas fa-angle-left"></i></a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                <span class="disabled"><i class="fas fa-angle-left"></i></span>
            <?php endif; ?>

            <?php
            // Zeige nur einen Bereich von Seitenlinks an
            $range = 2; // Zeigt 2 Seiten vor und nach der aktuellen Seite
            $startPage = max(1, $currentPage - $range);
            $endPage = min($totalPages, $currentPage + $range);

            if ($startPage > 1) {
                echo '<span>...</span>';
            }

            for ($i = $startPage; $i <= $endPage; $i++):
                $pageClass = ($i == $currentPage) ? 'current-page' : '';
                if (isset($pagesWithIncompleteData[$i])) {
                    $pageClass .= ' incomplete-page';
                }
            ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo $pageClass; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php
            if ($endPage < $totalPages) {
                echo '<span>...</span>';
            }
            ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>" title="Nächste Seite"><i class="fas fa-angle-right"></i></a>
                <a href="?page=<?php echo $totalPages; ?>" title="Letzte Seite"><i class="fas fa-angle-double-right"></i></a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-angle-right"></i></span>
                <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
            <?php endif; ?>
        </div>
    </section>

    <section class="report-section">
        <h2>Bericht über fehlende Comic-Informationen (Gesamt)</h2>
        <?php if (!empty($incompleteInfoReportFull)): ?>
            <ul>
                <?php foreach ($incompleteInfoReportFull as $id => $fields): ?>
                    <li>Comic ID: <strong><?php echo htmlspecialchars($id); ?></strong> - Fehlende Felder: <?php echo implode(', ', $fields); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($hasIncompleteOtherPages): ?>
                <p class="missing-other-pages-warning">
                    <i class="fas fa-exclamation-triangle"></i> Hinweis: Es gibt auch unvollständige Comic-Einträge auf anderen Seiten. Bitte überprüfen Sie alle Seiten.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p>Alle Comic-Einträge sind vollständig!</p>
        <?php endif; ?>
    </section>
</div>

<button id="save-all-button" class="floating-save-button">
    <i class="fas fa-save"></i> Alle Änderungen speichern
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const comicEditForm = document.getElementById('comic-edit-form');
    const comicIdInput = document.getElementById('comic-id');
    const originalComicIdInput = document.getElementById('original-comic-id');
    const comicTypeSelect = document.getElementById('comic-type');
    const comicNameInput = document.getElementById('comic-name');
    const comicTranscriptTextarea = document.getElementById('comic-transcript');
    const comicChapterSelect = document.getElementById('comic-chapter');
    const saveSingleButton = document.getElementById('save-single-button');
    const cancelEditButton = document.getElementById('cancel-edit-button');
    const addComicButton = document.getElementById('add-new-comic-button');
    const comicDataTable = document.getElementById('comic-data-table');
    const messageBoxElement = document.getElementById('message-box');
    const saveAllButton = document.getElementById('save-all-button');

    let hasUnsavedChanges = false;
    let editedRows = new Map(); // Speichert die IDs der bearbeiteten Zeilen und ihre Daten
    let deletedRows = new Set(); // Speichert die IDs der gelöschten Zeilen

    // Initialisiere Formularfelder mit Standardwerten oder leere sie
    function resetForm() {
        comicEditForm.reset();
        comicIdInput.value = '';
        originalComicIdInput.value = ''; // Wichtig für neue Einträge
        comicIdInput.readOnly = false; // ID ist bearbeitbar für neue Einträge
        saveSingleButton.textContent = 'Speichern';
        saveSingleButton.classList.remove('edit');
        saveSingleButton.classList.add('button');
        comicEditForm.scrollIntoView({ behavior: 'smooth' }); // Zum Formular scrollen
    }

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
            // Fallback, sollte aber nicht mehr auftreten
            console.warn("Message box element not found, falling back to alert: " + msg);
            // alert(msg); // Dies sollte nicht mehr verwendet werden
        }
    }

    // Funktion zum Setzen des "ungespeicherte Änderungen"-Flags
    function setUnsavedChanges(status) {
        hasUnsavedChanges = status;
        if (saveAllButton) {
            if (status) {
                saveAllButton.classList.remove('disabled');
            } else {
                saveAllButton.classList.add('disabled');
            }
        }
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

    // Event Listener für Bearbeiten-Buttons
    comicDataTable.addEventListener('click', function(event) {
        const target = event.target;
        // Überprüfe, ob der Klick auf den Button selbst oder ein Icon darin erfolgte
        const editButton = target.closest('.edit-button');
        const deleteButton = target.closest('.delete-button');

        if (editButton) {
            const row = editButton.closest('tr');
            const comicId = row.dataset.comicId;
            const comicType = row.querySelector('.comic-type-display').textContent;
            const comicName = row.querySelector('.comic-name-display').textContent;
            const comicTranscript = row.querySelector('.comic-transcript-display').textContent;
            const comicChapter = row.querySelector('.comic-chapter-display').textContent;

            // Formularfelder füllen
            comicIdInput.value = comicId;
            originalComicIdInput.value = comicId; // Speichert die Original-ID für den Fall, dass die ID geändert wird
            comicIdInput.readOnly = true; // ID ist nicht bearbeitbar im Bearbeitungsmodus
            comicTypeSelect.value = comicType;
            comicNameInput.value = comicName;
            comicTranscriptTextarea.value = comicTranscript;
            comicChapterSelect.value = comicChapter;

            saveSingleButton.textContent = 'Änderungen speichern';
            saveSingleButton.classList.add('edit');
            saveSingleButton.classList.remove('button');

            comicEditForm.scrollIntoView({ behavior: 'smooth' }); // Zum Formular scrollen
        } else if (deleteButton) {
            const row = deleteButton.closest('tr');
            const comicId = row.dataset.comicId;

            // Bestätigungsdialog vor dem Löschen
            if (confirm(`Sind Sie sicher, dass Sie den Comic-Eintrag mit der ID ${comicId} löschen möchten? Diese Änderung wird erst nach dem Klick auf "Alle Änderungen speichern" permanent.`)) {
                // Markiere die Zeile als gelöscht (visuell und intern)
                row.style.textDecoration = 'line-through';
                row.style.opacity = '0.5';
                row.querySelectorAll('button').forEach(btn => btn.disabled = true); // Buttons deaktivieren

                // Füge die ID zur deletedRows Set hinzu
                deletedRows.add(comicId);
                // Entferne die ID aus editedRows, falls sie dort war
                editedRows.delete(comicId);

                setUnsavedChanges(true);
                showMessage(`Comic-Eintrag ${comicId} zum Löschen markiert. Klicken Sie auf "Alle Änderungen speichern", um dies zu bestätigen.`, 'success');
            }
        }
    });

    // Event Listener für das Hinzufügen eines neuen Eintrags
    addComicButton.addEventListener('click', function() {
        resetForm();
        saveSingleButton.textContent = 'Hinzufügen';
        saveSingleButton.classList.add('button');
        saveSingleButton.classList.remove('edit');
        comicIdInput.readOnly = false; // ID ist bearbeitbar für neue Einträge
        comicEditForm.scrollIntoView({ behavior: 'smooth' }); // Zum Formular scrollen
    });

    // Event Listener für Abbrechen-Button
    cancelEditButton.addEventListener('click', function() {
        resetForm();
        showMessage('Bearbeitung abgebrochen.', 'success');
    });

    // Event Listener für das Speichern eines einzelnen Eintrags (Formular-Submit)
    comicEditForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Standard-Formular-Submit verhindern

        const comicId = comicIdInput.value.trim();
        const originalComicId = originalComicIdInput.value.trim(); // Die ID, die tatsächlich bearbeitet wird (falls ID geändert wurde)
        const comicType = comicTypeSelect.value.trim();
        const comicName = comicNameInput.value.trim();
        const comicTranscript = comicTranscriptTextarea.value.trim();
        const comicChapter = comicChapterSelect.value.trim();

        // Einfache Validierung
        if (!comicId || !comicType || !comicName || !comicChapter) {
            showMessage('Bitte füllen Sie alle Felder (ID, Typ, Name, Kapitel) aus.', 'error');
            return;
        }
        if (!/^\d{8}$/.test(comicId)) {
            showMessage('Comic ID muss eine 8-stellige Zahl (JJJJMMTT) sein.', 'error');
            return;
        }

        const comicData = {
            comic_id: comicId,
            comic_type: comicType,
            comic_name: comicName,
            comic_transcript: comicTranscript,
            comic_chapter: parseInt(comicChapter)
        };

        // Wenn originalComicId gesetzt ist und sich von comicId unterscheidet, bedeutet dies eine ID-Änderung
        if (originalComicId && originalComicId !== comicId) {
            // Markiere die alte ID als zu löschen
            deletedRows.add(originalComicId);
            // Entferne die alte ID aus editedRows, falls vorhanden
            editedRows.delete(originalComicId);
        }

        // Füge die Daten zur Map der bearbeiteten Zeilen hinzu
        editedRows.set(comicId, comicData);
        // Entferne die ID aus deletedRows, falls sie dort fälschlicherweise war (z.B. Bearbeitung nach Lösch-Markierung)
        deletedRows.delete(comicId);

        // Aktualisiere die Tabellenzeile oder füge eine neue hinzu (visuell)
        let row = comicDataTable.querySelector(`tr[data-comic-id="${htmlspecialchars(originalComicId || comicId)}"]`);
        if (!row) {
            // Neue Zeile hinzufügen, wenn nicht gefunden
            row = comicDataTable.tBodies[0].insertRow();
            row.dataset.comicId = comicId;
            row.innerHTML = `
                <td class="comic-id-display"></td>
                <td><span class="editable-field comic-type-display"></span></td>
                <td><span class="editable-field comic-name-display"></span></td>
                <td><span class="editable-field comic-transcript-display"></span></td>
                <td><span class="editable-field comic-chapter-display"></span></td>
                <td class="actions">
                    <button type="button" class="edit-button button edit"><i class="fas fa-edit"></i> Bearbeiten</button>
                    <button type="button" class="delete-button button delete"><i class="fas fa-trash-alt"></i> Löschen</button>
                </td>
            `;
        }

        // Aktualisiere die angezeigten Werte
        row.dataset.comicId = comicId; // Wichtig, falls ID geändert wurde
        row.querySelector('.comic-id-display').textContent = comicId;
        row.querySelector('.comic-type-display').textContent = comicType;
        row.querySelector('.comic-name-display').textContent = comicName;
        row.querySelector('.comic-transcript-display').textContent = comicTranscript;
        row.querySelector('.comic-chapter-display').textContent = comicChapter;

        // Entferne visuelle Lösch-Markierungen, falls die Zeile bearbeitet wurde
        row.style.textDecoration = 'none';
        row.style.opacity = '1';
        row.querySelectorAll('button').forEach(btn => btn.disabled = false);

        // Prüfe auf fehlende Informationen und markiere visuell
        const isTranscriptEffectivelyEmpty = (comicTranscript === '' || comicTranscript === '<p><br></p>' || comicTranscript === '&nbsp;');
        const missingFields = [];
        if (comicType === '') missingFields.push('type');
        if (comicName === '') missingFields.push('name');
        if (isTranscriptEffectivelyEmpty) missingFields.push('transcript');
        if (comicChapter === '' || parseInt(comicChapter) <= 0) missingFields.push('chapter');

        if (missingFields.length > 0) {
            row.classList.add('missing-info-row');
            if (missingFields.includes('type')) row.querySelector('.comic-type-display').classList.add('missing-info'); else row.querySelector('.comic-type-display').classList.remove('missing-info');
            if (missingFields.includes('name')) row.querySelector('.comic-name-display').classList.add('missing-info'); else row.querySelector('.comic-name-display').classList.remove('missing-info');
            if (missingFields.includes('transcript')) row.querySelector('.comic-transcript-display').classList.add('missing-info'); else row.querySelector('.comic-transcript-display').classList.remove('missing-info');
            if (missingFields.includes('chapter')) row.querySelector('.comic-chapter-display').classList.add('missing-info'); else row.querySelector('.comic-chapter-display').classList.remove('missing-info');
        } else {
            row.classList.remove('missing-info-row');
            row.querySelectorAll('.missing-info').forEach(el => el.classList.remove('missing-info'));
        }

        setUnsavedChanges(true);
        resetForm();
        showMessage('Eintrag lokal gespeichert. Klicken Sie auf "Alle Änderungen speichern", um dies zu bestätigen.', 'success');
    });

    // Event Listener für den "Alle Änderungen speichern" Button
    saveAllButton.addEventListener('click', function() {
        if (!hasUnsavedChanges) {
            showMessage('Keine ungespeicherten Änderungen vorhanden.', 'info');
            return;
        }

        // Sammle alle zu speichernden/löschenden Daten
        const dataToSend = {
            pages: Array.from(editedRows.values()),
            deleted_ids: Array.from(deletedRows)
        };

        // Sende Daten an den Server
        fetch(window.location.href, { // Sendet an die aktuelle PHP-Datei
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataToSend)
        })
        .then(response => {
            if (!response.ok) {
                // Wenn der Server einen HTTP-Fehlercode zurückgibt
                throw new Error(`HTTP-Fehler! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                showMessage(data.message + ' Die Seite wird neu geladen, um die Änderungen anzuzeigen.', 'success');
                setUnsavedChanges(false);
                editedRows.clear(); // Lokale Änderungen löschen
                deletedRows.clear(); // Lokale Löschungen löschen
                // Kurze Verzögerung vor dem Neuladen, damit die Nachricht sichtbar ist
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage('Fehler beim Speichern der Daten: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showMessage('Ein Netzwerkfehler ist aufgetreten oder die Serverantwort war unerwartet: ' + error.message, 'error');
        });
    });

    // Initialer Status des "Alle Änderungen speichern" Buttons
    setUnsavedChanges(false);

    // Live-Bearbeitung in der Tabelle
    comicDataTable.addEventListener('dblclick', function(event) {
        const target = event.target;
        if (target.classList.contains('editable-field')) {
            const row = target.closest('tr');
            const comicId = row.dataset.comicId;
            const fieldName = target.classList[1].replace('-display', ''); // z.B. 'comic-type'

            if (target.classList.contains('editing')) {
                return; // Bereits im Bearbeitungsmodus
            }

            // Alle anderen Bearbeitungsfelder schließen
            document.querySelectorAll('.editable-field.editing').forEach(field => {
                field.classList.remove('editing');
                const originalContent = field.dataset.originalContent;
                if (originalContent !== undefined) {
                    field.innerHTML = originalContent; // Setze den Inhalt zurück
                }
            });

            // Speichere den Originalinhalt
            target.dataset.originalContent = target.innerHTML;
            target.classList.add('editing');

            let inputElement;
            if (fieldName === 'comic-type') {
                inputElement = document.createElement('select');
                <?php foreach ($comicTypeOptions as $option): ?>
                    const optionType = document.createElement('option');
                    optionType.value = "<?php echo htmlspecialchars($option); ?>";
                    optionType.textContent = "<?php echo htmlspecialchars($option); ?>";
                    inputElement.appendChild(optionType);
                <?php endforeach; ?>
                inputElement.value = target.textContent;
            } else if (fieldName === 'comic-chapter') {
                inputElement = document.createElement('select');
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.textContent = "Bitte auswählen";
                inputElement.appendChild(defaultOption);
                <?php foreach ($chapterOptions as $option): ?>
                    const optionChapter = document.createElement('option');
                    optionChapter.value = "<?php echo htmlspecialchars($option); ?>";
                    optionChapter.textContent = "<?php echo htmlspecialchars($option); ?>";
                    inputElement.appendChild(optionChapter);
                <?php endforeach; ?>
                inputElement.value = target.textContent;
            } else if (fieldName === 'comic-transcript') {
                inputElement = document.createElement('textarea');
                inputElement.value = target.textContent;
                inputElement.style.width = '100%';
                inputElement.style.minHeight = '80px';
                inputElement.style.boxSizing = 'border-box';
            } else {
                inputElement = document.createElement('input');
                inputElement.type = 'text';
                inputElement.value = target.textContent;
                inputElement.style.width = '100%';
                inputElement.style.boxSizing = 'border-box';
            }

            target.innerHTML = '';
            target.appendChild(inputElement);
            inputElement.focus();

            inputElement.addEventListener('blur', function() {
                // Überprüfe, ob sich der Wert geändert hat
                const newValue = inputElement.value.trim();
                const originalValue = target.dataset.originalContent.trim();

                target.classList.remove('editing');
                target.innerHTML = htmlspecialchars(newValue); // Zeige den neuen Wert an

                if (newValue !== originalValue) {
                    // Daten für die Speicherung vorbereiten
                    let currentData = editedRows.get(comicId) || {
                        comic_id: comicId,
                        comic_type: row.querySelector('.comic-type-display').textContent,
                        comic_name: row.querySelector('.comic-name-display').textContent,
                        comic_transcript: row.querySelector('.comic-transcript-display').textContent,
                        comic_chapter: parseInt(row.querySelector('.comic-chapter-display').textContent)
                    };

                    if (fieldName === 'comic-type') currentData.comic_type = newValue;
                    else if (fieldName === 'comic-name') currentData.comic_name = newValue;
                    else if (fieldName === 'comic-transcript') currentData.comic_transcript = newValue;
                    else if (fieldName === 'comic-chapter') currentData.comic_chapter = parseInt(newValue);

                    editedRows.set(comicId, currentData);
                    setUnsavedChanges(true);
                    showMessage('Änderung für ' + comicId + ' (' + fieldName + ') lokal gespeichert. Klicken Sie auf "Alle Änderungen speichern".', 'info');

                    // Aktualisiere die "missing-info" Klasse basierend auf dem neuen Wert
                    const isTranscriptEffectivelyEmpty = (currentData.comic_transcript === '' || currentData.comic_transcript === '<p><br></p>' || currentData.comic_transcript === '&nbsp;');
                    const isChapterEffectivelyEmpty = (currentData.comic_chapter === null || isNaN(currentData.comic_chapter) || currentData.comic_chapter <= 0);

                    if (fieldName === 'comic-type') {
                        if (currentData.comic_type === '') target.classList.add('missing-info');
                        else target.classList.remove('missing-info');
                    } else if (fieldName === 'comic-name') {
                        if (currentData.comic_name === '') target.classList.add('missing-info');
                        else target.classList.remove('missing-info');
                    } else if (fieldName === 'comic-transcript') {
                        if (isTranscriptEffectivelyEmpty) target.classList.add('missing-info');
                        else target.classList.remove('missing-info');
                    } else if (fieldName === 'comic-chapter') {
                        if (isChapterEffectivelyEmpty) target.classList.add('missing-info');
                        else target.classList.remove('missing-info');
                    }

                    // Prüfe, ob die gesamte Zeile jetzt vollständig ist oder nicht
                    const rowType = row.querySelector('.comic-type-display').textContent;
                    const rowName = row.querySelector('.comic-name-display').textContent;
                    const rowTranscript = row.querySelector('.comic-transcript-display').textContent;
                    const rowChapter = row.querySelector('.comic-chapter-display').textContent;

                    const rowIsTranscriptEffectivelyEmpty = (rowTranscript === '' || rowTranscript === '<p><br></p>' || rowTranscript === '&nbsp;');
                    const rowIsChapterEffectivelyEmpty = (rowChapter === '' || parseInt(rowChapter) <= 0);

                    if (rowType === '' || rowName === '' || rowIsTranscriptEffectivelyEmpty || rowIsChapterEffectivelyEmpty) {
                        row.classList.add('missing-info-row');
                    } else {
                        row.classList.remove('missing-info-row');
                    }

                } else {
                    // Wenn keine Änderung, setze den Originalinhalt zurück (falls HTML-Tags entfernt wurden)
                    target.innerHTML = originalValue;
                }
            });

            // Ermögliche Speichern mit Enter für Textfelder
            if (inputElement.tagName === 'INPUT' || inputElement.tagName === 'TEXTAREA') {
                inputElement.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && inputElement.tagName === 'INPUT') { // Nur für INPUT, nicht TEXTAREA
                        inputElement.blur(); // Verlässt das Feld, was den blur-Event auslöst und speichert
                    }
                });
            }
        }
    });
});
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>
