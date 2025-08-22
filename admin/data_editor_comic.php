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

// === DEBUG-MODUS & KONFIGURATION ===
$debugMode = false; // Setze auf true, um DEBUG-Meldungen zu aktivieren.
$itemsPerPage = 50; // HIER: Lege die Anzahl der Einträge pro Seite fest.

if ($debugMode)
    error_log("DEBUG: data_editor_comic.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in data_editor_comic.php gestartet.");

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();
if ($debugMode)
    error_log("DEBUG: Session gestartet in data_editor_comic.php.");

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httpholy"]
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
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von data_editor_comic.php.");
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in data_editor_comic.php angemeldet.");

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$comicLowresDirPath = __DIR__ . '/../assets/comic_lowres/';
$comicHiresDirPath = __DIR__ . '/../assets/comic_hires/';
$comicThumbnailsDirPath = __DIR__ . '/../assets/comic_thumbnails/';
$comicSocialMediaDirPath = __DIR__ . '/../assets/comic_socialmedia/';
$comicPhpPagesPath = __DIR__ . '/../comic/';

if ($debugMode) {
    error_log("DEBUG: Pfade definiert: comicVarJsonPath=" . $comicVarJsonPath . ", comicPhpPagesPath=" . $comicPhpPagesPath);
}

// Setze Parameter für den Header.
$pageTitle = 'Comic Daten Editor';
$pageHeader = 'Comic Daten Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

// Optionen für 'type' und 'chapter'
$comicTypeOptions = ['Comicseite', 'Lückenfüller'];

/**
 * Lädt Comic-Metadaten aus einer JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Die dekodierten Daten als assoziatives Array oder ein leeres Array im Fehlerfall.
 */
function loadComicData(string $path, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: loadComicData() aufgerufen für: " . basename($path));
    if (!file_exists($path)) {
        if ($debugMode)
            error_log("DEBUG: Comic-JSON-Datei nicht gefunden: " . $path);
        return [];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        error_log("Fehler beim Lesen der JSON-Datei: " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen des Inhalts von: " . $path);
        return [];
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren von comic_var.json: " . json_last_error_msg());
        return [];
    }
    if ($debugMode)
        error_log("DEBUG: Comic-Daten erfolgreich geladen und dekodiert.");
    return is_array($data) ? $data : [];
}

/**
 * Speichert Comic-Daten in die JSON-Datei und gibt die vollständigen, sortierten Daten zurück.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $newDataSubset Die neuen oder aktualisierten Daten.
 * @param array $deletedIds Eine Liste von IDs, die gelöscht werden sollen.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array|false Die vollständigen, aktualisierten Daten bei Erfolg, sonst false.
 */
function saveComicDataAndReturnAll(string $path, array $newDataSubset, array $deletedIds = [], bool $debugMode)
{
    if ($debugMode)
        error_log("DEBUG: saveComicDataAndReturnAll() aufgerufen.");
    $existingData = loadComicData($path, $debugMode);

    foreach ($newDataSubset as $id => $data) {
        $existingData[$id] = $data;
    }

    foreach ($deletedIds as $id) {
        if (isset($existingData[$id])) {
            unset($existingData[$id]);
        }
    }

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

    if ($debugMode)
        error_log("DEBUG: Comic-Daten erfolgreich gespeichert. Gebe vollständige Daten zurück.");

    return $existingData;
}


/**
 * Scannt die Comic-Bildverzeichnisse nach vorhandenen Comic-IDs.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung), sortiert.
 */
function getComicIdsFromImages(string $lowresDir, string $hiresDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getComicIdsFromImages() aufgerufen für lowres: " . $lowresDir . " und hires: " . $hiresDir);
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $dirs = [$lowresDir, $hiresDir];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            if ($files === false) {
                if ($debugMode)
                    error_log("DEBUG: scandir() fehlgeschlagen für " . $dir);
                continue;
            }
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false) {
                    continue;
                }
                $info = pathinfo($file);
                if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), $imageExtensions)) {
                    $imageIds[$info['filename']] = true;
                }
            }
        }
    }
    $sortedIds = array_keys($imageIds);
    sort($sortedIds);
    if ($debugMode)
        error_log("DEBUG: " . count($sortedIds) . " Comic-IDs aus Bildern gefunden und sortiert.");
    return $sortedIds;
}

/**
 * Scannt das Comic-Seiten-Verzeichnis nach vorhandenen PHP-Dateien.
 * @param string $pagesDir Pfad zum Verzeichnis mit den Comic-PHP-Seiten.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne .php), sortiert.
 */
function getComicIdsFromPhpFiles(string $pagesDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getComicIdsFromPhpFiles() aufgerufen für: " . $pagesDir);
    $phpIds = [];
    if (is_dir($pagesDir)) {
        $files = scandir($pagesDir);
        if ($files === false) {
            if ($debugMode)
                error_log("DEBUG: scandir() fehlgeschlagen für " . $pagesDir);
            return [];
        }
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && preg_match('/^\d{8}$/', pathinfo($file, PATHINFO_FILENAME))) {
                $phpIds[pathinfo($file, PATHINFO_FILENAME)] = true;
            }
        }
    }
    $sortedIds = array_keys($phpIds);
    sort($sortedIds);
    if ($debugMode)
        error_log("DEBUG: " . count($sortedIds) . " Comic-IDs aus PHP-Dateien gefunden und sortiert.");
    return $sortedIds;
}

/**
 * NEU: Sucht das erste verfügbare lowres-Bild für eine Comic-ID und gibt den relativen Web-Pfad zurück.
 * @param string $comicId Die ID des Comics.
 * @param string $baseDir Der absolute Basispfad zum lowres-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return string Der relative Pfad zum Bild oder ein leerer String, wenn nichts gefunden wurde.
 */
function findLowresImagePath(string $comicId, string $baseDir, bool $debugMode): string
{
    if ($debugMode)
        error_log("DEBUG: findLowresImagePath() aufgerufen für Comic-ID: " . $comicId);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    // WICHTIG: Der Pfad, der vom Browser aus erreichbar ist.
    $relativeWebPath = '../assets/comic_lowres/';

    foreach ($imageExtensions as $ext) {
        if (file_exists($baseDir . $comicId . '.' . $ext)) {
            if ($debugMode)
                error_log("DEBUG: Bild gefunden: " . $baseDir . $comicId . '.' . $ext);
            return $relativeWebPath . $comicId . '.' . $ext;
        }
    }

    if ($debugMode)
        error_log("DEBUG: Kein lowres-Bild für ID " . $comicId . " gefunden.");
    return '';
}


/**
 * Checks for the existence of various image types for a given comic ID.
 * @param string $comicId The ID of the comic (e.g., 'JJJJMMTT').
 * @param array $directories An associative array of directory paths for each image type.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array An associative array indicating presence (true/false) for each image type.
 */
function checkImageExistenceForComic(string $comicId, array $directories, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: checkImageExistenceForComic() aufgerufen für Comic-ID: " . $comicId);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $results = [];

    foreach ($directories as $type => $dirPath) {
        $found = false;
        foreach ($imageExtensions as $ext) {
            $filePath = $dirPath . $comicId . '.' . $ext;
            if (file_exists($filePath)) {
                $found = true;
                break;
            }
        }
        $results[$type] = $found;
    }
    return $results;
}


// Verarbeite POST-Anfragen zum Speichern (AJAX-Handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    if ($debugMode)
        error_log("DEBUG: POST-Anfrage mit application/json Content-Type erkannt.");

    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Dekodieren der JSON-Daten: ' . json_last_error_msg()]);
        exit;
    }
    if ($debugMode)
        error_log("DEBUG: JSON-Daten erfolgreich empfangen und dekodiert: " . print_r($requestData, true));

    $action = $requestData['action'] ?? '';

    if ($action === 'save') {
        $pageData = $requestData['page'] ?? null;
        if (!$pageData) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Keine Seitendaten zum Speichern erhalten.']);
            exit;
        }

        $comicId = trim($pageData['comic_id']);
        $originalComicId = isset($pageData['original_comic_id']) && !empty($pageData['original_comic_id']) ? trim($pageData['original_comic_id']) : null;

        $type = isset($pageData['comic_type']) ? trim($pageData['comic_type']) : '';
        $name = isset($pageData['comic_name']) ? trim($pageData['comic_name']) : '';
        $transcript = isset($pageData['comic_transcript']) ? $pageData['comic_transcript'] : '';
        $chapter = $pageData['comic_chapter'] ?? '';

        if ($chapter === '') {
            $chapter = null;
        } else {
            $chapter = str_replace(',', '.', $chapter);
            if (!is_numeric($chapter) || (float) $chapter < 0) {
                $chapter = null;
            }
        }

        $updatedData = [
            $comicId => [
                'type' => $type,
                'name' => $name,
                'transcript' => $transcript,
                'chapter' => $chapter,
                'datum' => $comicId
            ]
        ];

        $deletedIds = [];
        if ($originalComicId && $originalComicId !== $comicId) {
            $deletedIds[] = $originalComicId;
        }

        $allDataFromFile = saveComicDataAndReturnAll($comicVarJsonPath, $updatedData, $deletedIds, $debugMode);

        if ($allDataFromFile !== false) {
            $completeDataForCalc = $allDataFromFile;
            $imageComicIds = getComicIdsFromImages($comicLowresDirPath, $comicHiresDirPath, $debugMode);
            $phpComicIds = getComicIdsFromPhpFiles($comicPhpPagesPath, $debugMode);
            $allFileIds = array_unique(array_merge($imageComicIds, $phpComicIds));

            foreach ($allFileIds as $id) {
                if (!isset($completeDataForCalc[$id])) {
                    $completeDataForCalc[$id] = ['type' => '', 'name' => '', 'transcript' => '', 'chapter' => null, 'datum' => $id];
                }
            }

            ksort($completeDataForCalc);

            $allIds = array_keys($completeDataForCalc);
            $index = array_search($comicId, $allIds);
            $pageNumber = ($index !== false) ? floor($index / $itemsPerPage) + 1 : 1;

            if ($debugMode) {
                error_log("DEBUG SAVE (CORRECTED): comicId=$comicId, index=$index, totalItems=" . count($allIds) . ", itemsPerPage=$itemsPerPage, calculatedPage=$pageNumber");
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Comic-Daten erfolgreich gespeichert!', 'comic_id' => $comicId, 'page' => $pageNumber]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Fehler beim Speichern der Comic-Daten.']);
            exit;
        }


    } elseif ($action === 'delete') {
        $comicId = $requestData['comic_id'] ?? null;
        if (!$comicId) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Keine Comic-ID zum Löschen erhalten.']);
            exit;
        }

        if (saveComicDataAndReturnAll($comicVarJsonPath, [], [$comicId], $debugMode) !== false) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Comic-Eintrag ' . htmlspecialchars($comicId) . ' erfolgreich gelöscht!']);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Fehler beim Löschen des Comic-Eintrags.']);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unbekannte Aktion.']);
        exit;
    }
}


// === DATEN-ZUSAMMENFÜHRUNGSLOGIK ===
$jsonData = loadComicData($comicVarJsonPath, $debugMode);
$imageIds = getComicIdsFromImages($comicLowresDirPath, $comicHiresDirPath, $debugMode);
$phpIds = getComicIdsFromPhpFiles($comicPhpPagesPath, $debugMode);

// Erstelle eine Master-Liste aller eindeutigen IDs aus allen Quellen
$allIds = array_unique(array_merge(array_keys($jsonData), $imageIds, $phpIds));
sort($allIds); // Sortiere die Master-Liste

$fullComicData = [];
foreach ($allIds as $id) {
    // Beginne mit den Daten aus der JSON-Datei, falls vorhanden, sonst mit einem leeren Template
    $fullComicData[$id] = $jsonData[$id] ?? [
        'type' => '',
        'name' => '',
        'transcript' => '',
        'chapter' => null,
        'datum' => $id
    ];

    // Füge die Quellen-Information hinzu
    $sources = [];
    if (isset($jsonData[$id])) {
        $sources[] = 'json';
    }
    if (in_array($id, $imageIds)) {
        $sources[] = 'image';
    }
    if (in_array($id, $phpIds)) {
        $sources[] = 'php';
    }
    $fullComicData[$id]['sources'] = $sources;
}

// Sammle Bildverfügbarkeitsdaten für alle Comics
$imageDirectories = [
    'lowres' => $comicLowresDirPath,
    'hires' => $comicHiresDirPath,
    'thumbnails' => $comicThumbnailsDirPath,
    'socialmedia' => $comicSocialMediaDirPath,
];

$imageExistenceReport = [];
foreach ($fullComicData as $id => $data) {
    $imageExistenceReport[$id] = checkImageExistenceForComic($id, $imageDirectories, $debugMode);
}


// --- Paginierungslogik anwenden ---
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($currentPage < 1)
    $currentPage = 1;

$totalItems = count($fullComicData);
$totalPages = ceil($totalItems / $itemsPerPage);

if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $itemsPerPage;
$paginatedComicData = array_slice($fullComicData, $offset, $itemsPerPage, true);

// === PAGINIERUNGS-HTML GENERIEREN ===
$paginationHtml = '';
if ($totalPages > 1) {
    ob_start();
    ?>
    <div class="pagination">
        <?php if ($currentPage > 1): ?>
            <a href="?page=1" title="Erste Seite"><i class="fas fa-angle-double-left"></i></a>
            <a href="?page=<?php echo $currentPage - 1; ?>" title="Vorherige Seite"><i class="fas fa-angle-left"></i></a>
        <?php else: ?>
            <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
            <span class="disabled"><i class="fas fa-angle-left"></i></span>
        <?php endif; ?>

        <?php
        $range = 2;
        $startPage = max(1, $currentPage - $range);
        $endPage = min($totalPages, $currentPage + $range);

        if ($startPage > 1) {
            echo '<span>...</span>';
        }

        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?>"
                class="<?php echo ($i == $currentPage) ? 'current-page' : ''; ?>"><?php echo $i; ?></a>
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
    <?php
    $paginationHtml = ob_get_clean();
}
// === ENDE PAGINIERUNGS-HTML GENERIERUNG ===


// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- Summernote CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">

<style>
    /* Allgemeine Layout-Anpassungen */
    .admin-container {
        padding: 20px;
        max-width: 1200px;
        margin: 20px auto;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    body.theme-night .admin-container {
        background-color: #00334c;
        color: #fff;
    }

    .message-box {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
        display: none;
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

    .message-box.info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .message-box.warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }


    body.theme-night .message-box.success {
        background-color: #28a745;
        color: #fff;
        border-color: #218838;
    }

    body.theme-night .message-box.error {
        background-color: #dc3545;
        color: #fff;
        border-color: #c82333;
    }

    body.theme-night .message-box.info {
        background-color: #17a2b8;
        color: #fff;
        border-color: #138496;
    }

    body.theme-night .message-box.warning {
        background-color: #6c5b00;
        color: #fff;
        border-color: #927c00;
    }

    /* Collapsible Sections */
    .collapsible-section {
        margin-bottom: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    body.theme-night .collapsible-section {
        background-color: #00425c;
    }

    .collapsible-header {
        cursor: pointer;
        padding: 15px 20px;
        background-color: #f2f2f2;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.5em;
        font-weight: bold;
        color: #333;
    }

    body.theme-night .collapsible-header {
        background-color: #005a7e;
        color: #fff;
        border-bottom-color: #007bb5;
    }

    .collapsible-header i {
        transition: transform 0.3s ease;
        margin-left: 10px;
    }

    .collapsible-section.expanded .collapsible-header i {
        transform: rotate(0deg);
    }

    .collapsible-section:not(.expanded) .collapsible-header i {
        transform: rotate(-90deg);
    }

    .collapsible-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        padding: 0 20px;
        display: block;
    }

    .collapsible-section.expanded .collapsible-content {
        max-height: 90000px;
        padding-top: 20px;
        padding-bottom: 20px;
        display: block !important;
    }

    .report-section.collapsible-section.expanded .collapsible-content {
        max-height: 459800px;
        display: block !important;
    }

    .form-section,
    .comic-list-section,
    .report-section {
        padding: 0;
    }

    .collapsible-section:not(.expanded) {
        border-radius: 8px;
    }

    .collapsible-section:not(.expanded) .collapsible-header {
        border-radius: 8px;
        border-bottom: none;
    }

    .collapsible-section.expanded .collapsible-header {
        border-radius: 8px 8px 0 0;
    }


    /* Formular- und Button-Stile */
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
    .form-group select {
        width: calc(100% - 22px);
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1em;
        box-sizing: border-box;
        background-color: #fff;
        color: #333;
    }

    .note-editor.note-frame {
        width: calc(100% - 22px) !important;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }


    body.theme-night .form-group input[type="text"],
    body.theme-night .form-group select,
    body.theme-night .note-editor.note-frame {
        background-color: #005a7e;
        border-color: #007bb5;
        color: #fff;
    }

    body.theme-night .note-editor .note-toolbar,
    body.theme-night .note-editor .note-editing-area .note-editable {
        background-color: #005a7e;
        color: #fff;
    }

    body.theme-night .note-editor .note-toolbar .btn-group .btn {
        background-color: #006690;
        color: #fff;
        border-color: #007bb5;
    }

    body.theme-night .note-editor .note-toolbar .btn-group .btn:hover {
        background-color: #007bb5;
    }

    body.theme-night .note-editor .note-toolbar .dropdown-menu {
        background-color: #005a7e;
        border-color: #007bb5;
    }

    body.theme-night .note-editor .note-toolbar .dropdown-menu a {
        color: #fff;
    }

    body.theme-night .note-editor .note-toolbar .dropdown-menu a:hover {
        background-color: #006690;
    }

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-group-checkbox {
        margin-top: 8px;
        display: flex;
        align-items: center;
    }

    .form-group-checkbox label {
        margin-bottom: 0;
        margin-left: 8px;
        font-weight: normal;
        cursor: pointer;
    }

    .button-group {
        text-align: right;
        margin-top: 20px;
    }

    button,
    .button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        background-color: #007bff;
        color: white;
        font-size: 1em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-left: 10px;
    }

    button:hover,
    .button:hover {
        background-color: #0056b3;
    }

    button.delete,
    .button.delete {
        background-color: #dc3545;
    }

    button.delete:hover,
    .button.delete:hover {
        background-color: #c82333;
    }

    button.edit,
    .button.edit {
        background-color: #ffc107;
        color: #333;
    }

    button.edit:hover,
    .button.edit:hover {
        background-color: #e0a800;
    }

    .comic-table td .actions button {
        background-color: transparent;
        border: 1px solid transparent;
        color: #007bff;
        padding: 5px;
        margin: 0 2px;
        font-size: 1.1em;
    }

    .comic-table td .actions button:hover {
        background-color: rgba(0, 123, 255, 0.1);
        border-color: #007bff;
    }

    body.theme-night .comic-table td .actions button {
        color: #7bbdff;
    }

    body.theme-night .comic-table td .actions button:hover {
        background-color: rgba(123, 189, 255, 0.1);
        border-color: #7bbdff;
    }


    body.theme-night button,
    body.theme-night .button {
        background-color: #2a6177;
    }

    body.theme-night button:hover,
    body.theme-night .button:hover {
        background-color: #48778a;
    }

    body.theme-night button.delete,
    body.theme-night .button.delete {
        background-color: #dc3545;
    }

    body.theme-night button.delete:hover,
    body.theme-night .button.delete:hover {
        background-color: #c82333;
    }


    body.theme-night button.edit,
    body.theme-night .button.edit {
        background-color: #ffc107;
        color: #333;
    }

    body.theme-night button.edit:hover,
    body.theme-night .button.edit:hover {
        background-color: #e0a800;
    }

    /* Comic-Liste und Tabelle */
    .comic-table-container {
        overflow-x: auto;
    }

    .comic-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .comic-table th,
    .comic-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        vertical-align: top;
    }

    body.theme-night .comic-table th,
    body.theme-night .comic-table td {
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
        border: 1px solid #eee;
        border-radius: 3px;
        box-sizing: border-box;
        background-color: transparent;
        cursor: pointer;
        min-height: 30px;
        display: block;
        color: #333;
    }

    body.theme-night .comic-table td .editable-field {
        border-color: #005a7e;
        color: #fff;
    }

    .comic-table td .editable-field:hover {
        border-color: #ccc;
    }

    body.theme-night .comic-table td .editable-field:hover {
        border-color: #007bb5;
    }

    .comic-table td .editable-field.editing {
        border-color: #007bff;
        background-color: #fff;
        cursor: text;
    }

    body.theme-night .comic-table td .editable-field.editing {
        background-color: #005a7e;
        border-color: #007bff;
    }

    .comic-table td .editable-field.missing-info {
        border: 2px solid #dc3545;
        background-color: #f8d7da;
    }

    body.theme-night .comic-table td .editable-field.missing-info {
        border-color: #ff4d4d;
        background-color: #721c24;
    }

    .comic-table td .actions {
        white-space: nowrap;
    }

    /* NEU: Styling für die Hervorhebung */
    @keyframes highlight-fade {
        from {
            background-color: #fff3cd;
        }

        to {
            background-color: transparent;
        }
    }

    @keyframes highlight-fade-dark {
        from {
            background-color: #6c5b00;
        }

        to {
            background-color: transparent;
        }
    }

    .highlight-row {
        animation: highlight-fade 4s ease-out forwards;
    }

    body.theme-night .highlight-row {
        animation: highlight-fade-dark 4s ease-out forwards;
    }

    /* KORREKTUR & NEU: Stile für Transkript-Anzeige */
    .transcript-content {
        display: block;
        cursor: pointer;
        transition: max-height 0.3s ease-in-out;
        overflow: hidden;
        word-break: break-word;
    }

    .transcript-collapsed {
        max-height: 3.2em;
        /* Ungefähr 2 Zeilen */
        position: relative;
    }

    .transcript-collapsed::after {
        content: "...";
        text-align: right;
        position: absolute;
        bottom: 0;
        right: 0;
        width: 40%;
        height: 1.6em;
        /* Höhe einer Zeile */
        background: linear-gradient(to right, transparent, white 50%);
    }

    body.theme-night .transcript-collapsed::after {
        background: linear-gradient(to right, transparent, #004c6b 50%);
    }

    .comic-table tr:nth-child(even) .transcript-collapsed::after {
        background: linear-gradient(to right, transparent, #f9f9f9 50%);
    }

    body.theme-night .comic-table tr:nth-child(even) .transcript-collapsed::after {
        background: linear-gradient(to right, transparent, #006690 50%);
    }

    .transcript-expanded {
        max-height: none;
        /* KORREKTUR: Keine Höhenbegrenzung */
    }

    .table-controls {
        display: flex;
        justify-content: space-between;
        /* Geändert für Legende links */
        align-items: center;
        margin-bottom: 15px;
        gap: 10px;
        flex-wrap: wrap;
        /* Für mobile Ansicht */
    }

    /* NEU: Styling für Quellen-Marker und Legende */
    .source-markers {
        margin-top: 5px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .source-marker {
        font-size: 0.7em;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: bold;
        color: white;
        cursor: help;
    }

    .source-json {
        background-color: #6c757d;
    }

    /* Grey */
    .source-image {
        background-color: #007bff;
    }

    /* Blue */
    .source-php {
        background-color: #28a745;
    }

    /* Green */

    body.theme-night .source-json {
        background-color: #5a6268;
    }

    body.theme-night .source-image {
        background-color: #0056b3;
    }

    body.theme-night .source-php {
        background-color: #1e7e34;
    }

    .marker-legend {
        display: flex;
        gap: 10px;
        align-items: center;
        font-size: 0.9em;
    }

    .marker-legend .source-marker {
        cursor: default;
    }

    /* Paginierung */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        margin-bottom: 20px;
        /* Abstand hinzugefügt */
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        margin: 0 4px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #007bff;
        background-color: #fff;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    body.theme-night .pagination a,
    body.theme-night .pagination span {
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
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    .pagination .incomplete-page {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }

    body.theme-night .pagination .incomplete-page {
        background-color: #dc3545;
        border-color: #c82333;
        color: #fff;
    }

    .pagination .incomplete-page.current-page {
        background-color: #dc3545;
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
    .report-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .report-table th,
    .report-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
        vertical-align: middle;
    }

    body.theme-night .report-table th,
    body.theme-night .report-table td {
        border-color: #005a7e;
    }

    .report-table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    body.theme-night .report-table th {
        background-color: #005a7e;
        color: #fff;
    }

    .report-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    body.theme-night .report-table tr:nth-child(even) {
        background-color: #004c6b;
    }

    .report-table tr:hover {
        background-color: #f1f1f1;
    }

    body.theme-night .report-table tr:hover {
        background-color: #006690;
    }

    .icon-success {
        color: #28a745;
        font-size: 1.2em;
    }

    .icon-missing {
        color: #dc3545;
        font-size: 1.2em;
    }

    /* Floating Add Button */
    .floating-add-button {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #17a2b8;
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        font-size: 1.2em;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        .form-group select {
            width: calc(100% - 20px);
        }

        .note-editor.note-frame {
            width: calc(100% - 20px) !important;
        }

        .collapsible-header {
            padding: 10px 15px;
            font-size: 1.2em;
        }

        .collapsible-content {
            padding: 0 15px;
        }

        .collapsible-section.expanded .collapsible-content {
            padding-top: 15px;
            padding-bottom: 15px;
        }


        .comic-table th,
        .comic-table td {
            padding: 6px;
            font-size: 0.9em;
        }

        .comic-table td .actions button {
            padding: 3px 6px;
            font-size: 0.8em;
            margin-left: 2px;
        }

        .pagination a,
        .pagination span {
            padding: 6px 10px;
            margin: 0 2px;
            font-size: 0.9em;
        }

        .floating-add-button {
            padding: 12px 20px;
            font-size: 1em;
            bottom: 10px;
            right: 10px;
        }
    }

    .note-modal-backdrop {
        z-index: 99;
    }
</style>

<div class="admin-container">
    <?php echo $paginationHtml; // Paginierung 1: Ganz oben ?>

    <div id="message-box" class="message-box"></div>

    <section class="form-section collapsible-section">
        <h2 class="collapsible-header">Comic-Eintrag bearbeiten / hinzufügen <i class="fas fa-chevron-right"></i></h2>
        <div class="collapsible-content">
            <form id="comic-edit-form">
                <input type="hidden" id="original-comic-id" name="original_comic_id" value="">
                <div class="form-group">
                    <label for="comic-id">Comic ID (Datum JJJJMMTT):</label>
                    <input type="text" id="comic-id" name="comic_id" pattern="\d{8}"
                        title="Bitte geben Sie eine 8-stellige Zahl (JJJJMMTT) ein." required>
                </div>
                <div class="form-group">
                    <label for="comic-type">Typ:</label>
                    <select id="comic-type" name="comic_type" required>
                        <?php foreach ($comicTypeOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>">
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="comic-name">Name:</label>
                    <input type="text" id="comic-name" name="comic_name" required>
                    <div class="form-group-checkbox">
                        <input type="checkbox" id="comic-name-empty-checkbox">
                        <label for="comic-name-empty-checkbox">Name leer lassen</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="comic-transcript">Transkript (HTML erlaubt):</label>
                    <textarea id="comic-transcript" name="comic_transcript"></textarea>
                </div>
                <div class="form-group">
                    <label for="comic-chapter">Kapitel:</label>
                    <input type="text" id="comic-chapter" name="comic_chapter"
                        placeholder="Geben Sie eine Kapitelnummer ein (z.B. 0, 6, 6.1)">
                </div>

                <!-- NEUER ABSCHNITT FÜR DIE BILDVORSCHAU -->
                <div id="comic-image-preview-container" style="display: none; margin-top: 20px; text-align: center;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Vorschaubild:</label>
                    <img id="comic-image-preview" src="" alt="Vorschau des Comics"
                        style="max-width: 100%; max-height: 900px; height: auto; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <!-- ENDE NEUER ABSCHNITT -->

                <div class="button-group">
                    <button type="submit" id="save-single-button">Speichern</button>
                    <button type="button" id="cancel-edit-button">Abbrechen</button>
                </div>
            </form>
        </div>
    </section>

    <section class="comic-list-section collapsible-section expanded">
        <h2 class="collapsible-header">Bearbeitungsübersicht Comic-Daten <i class="fas fa-chevron-down"></i></h2>
        <div class="collapsible-content">
            <?php echo $paginationHtml; // Paginierung 2: Über der Haupt-Tabelle ?>

            <div class="table-controls">
                <!-- NEU: Legende für die Marker -->
                <div class="marker-legend">
                    <strong>Herkunft Eintrag - Legende:</strong>
                    <span class="source-marker source-json" title="Eintrag existiert in comic_var.json">JSON</span>
                    <span class="source-marker source-image"
                        title="Mindestens eine Bilddatei existiert in comic_hires, comic_lowres oder comic_thumbnails">Bild</span>
                    <span class="source-marker source-php"
                        title="Eine PHP-Datei existiert für diese Seite in /comic/">PHP</span>
                </div>
                <button type="button" id="toggle-transcript-view" class="button"><i class="fas fa-eye"></i> HTML
                    rendern</button>
            </div>

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
                                $rowId = 'comic-row-' . htmlspecialchars($id);
                                $isTypeMissing = empty($data['type']);
                                $isNameMissing = empty($data['name']);
                                $transcriptContent = trim(strip_tags($data['transcript'], '<br>'));
                                $isTranscriptEffectivelyEmpty = (empty($transcriptContent) || $transcriptContent === '<br>' || $transcriptContent === '&nbsp;');
                                $isChapterMissing = ($data['chapter'] === null || $data['chapter'] < 0);
                                $isMissingInfoRow = $isTypeMissing || $isNameMissing || $isTranscriptEffectivelyEmpty || $isChapterMissing;
                                ?>
                                <!-- NEU: Die Funktion findLowresImagePath wird aufgerufen und das Ergebnis als data-Attribut gespeichert -->
                                <?php $lowresImagePath = findLowresImagePath($id, $comicLowresDirPath, $debugMode); ?>
                                <tr id="<?php echo $rowId; ?>" data-comic-id="<?php echo htmlspecialchars($id); ?>"
                                    data-lowres-path="<?php echo htmlspecialchars($lowresImagePath); ?>"
                                    class="<?php echo $isMissingInfoRow ? 'missing-info-row' : ''; ?>">
                                    <td class="comic-id-display">
                                        <?php echo htmlspecialchars($id); ?>
                                        <div class="source-markers">
                                            <?php if (in_array('json', $data['sources'])): ?>
                                                <span class="source-marker source-json"
                                                    title="Eintrag existiert in comic_var.json">JSON</span>
                                            <?php endif; ?>
                                            <?php if (in_array('image', $data['sources'])): ?>
                                                <span class="source-marker source-image"
                                                    title="Mindestens eine Bilddatei existiert in comic_hires, comic_lowres oder comic_thumbnails">Bild</span>
                                            <?php endif; ?>
                                            <?php if (in_array('php', $data['sources'])): ?>
                                                <span class="source-marker source-php"
                                                    title="Eine PHP-Datei existiert für diese Seite in /comic/">PHP</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><span
                                            class="editable-field comic-type-display <?php echo $isTypeMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['type']); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field comic-name-display <?php echo $isNameMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['name']); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field comic-transcript-display transcript-content transcript-collapsed <?php echo $isTranscriptEffectivelyEmpty ? 'missing-info' : ''; ?>"
                                            data-raw-html="<?php echo htmlspecialchars($data['transcript'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-is-expanded="false"><?php echo htmlspecialchars($data['transcript']); ?></span>
                                    </td>
                                    <td><span
                                            class="editable-field comic-chapter-display <?php echo $isChapterMissing ? 'missing-info' : ''; ?>"><?php echo htmlspecialchars($data['chapter'] ?? ''); ?></span>
                                    </td>
                                    <td class="actions">
                                        <button type="button" class="edit-button button edit" title="Bearbeiten"><i
                                                class="fas fa-edit"></i></button>
                                        <button type="button" class="delete-button button delete" title="Löschen"><i
                                                class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-new-comic-button" class="button"><i class="fas fa-plus"></i> Neuen
                Comic-Eintrag hinzufügen (+)</button>

            <?php echo $paginationHtml; // Paginierung 3: Unter der Haupt-Tabelle ?>
        </div>
    </section>

    <section class="report-section collapsible-section">
        <h2 class="collapsible-header">Bericht über fehlende Informationen (Aktuelle Seite) <i
                class="fas fa-chevron-right"></i></h2>
        <div class="collapsible-content">
            <?php if (!empty($paginatedComicData)): ?>
                <?php echo $paginationHtml; // Paginierung 4: Über der Berichts-Tabelle ?>
                <div class="comic-table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Typ</th>
                                <th>Name</th>
                                <th>Transkript</th>
                                <th>Kapitel</th>
                                <th>Lowres</th>
                                <th>Hires</th>
                                <th>Thumbnails</th>
                                <th>Social Media</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginatedComicData as $id => $data):
                                $isTypeMissing = empty($data['type']);
                                $isNameMissing = empty($data['name']);
                                $transcriptContent = trim(strip_tags($data['transcript'], '<br>'));
                                $isTranscriptEffectivelyEmpty = (empty($transcriptContent) || $transcriptContent === '<br>' || $transcriptContent === '&nbsp;');
                                $isChapterMissing = ($data['chapter'] === null || $data['chapter'] < 0);
                                $currentImageExistence = $imageExistenceReport[$id] ?? [];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($id); ?></td>
                                    <td><?php echo $isTypeMissing ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo $isNameMissing ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo $isTranscriptEffectivelyEmpty ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo $isChapterMissing ? '<i class="fas fa-times-circle icon-missing"></i>' : '<i class="fas fa-check-circle icon-success"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['lowres'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['hires'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['thumbnails'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                    <td><?php echo ($currentImageExistence['socialmedia'] ?? false) ? '<i class="fas fa-check-circle icon-success"></i>' : '<i class="fas fa-times-circle icon-missing"></i>'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo $paginationHtml; // Paginierung 5: Unter der Berichts-Tabelle ?>
            <?php else: ?>
                <p>Keine Comic-Daten zum Berichten vorhanden.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php echo $paginationHtml; // Paginierung 6: Ganz unten ?>
</div>

<!-- jQuery (Summernote benötigt jQuery) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Summernote JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Scrollen und Hervorheben beim Laden der Seite
        if (window.location.hash) {
            const targetId = window.location.hash.substring(1);
            const targetRow = document.getElementById(targetId);
            if (targetRow) {
                targetRow.classList.add('highlight-row');
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        const comicEditForm = document.getElementById('comic-edit-form');
        const comicIdInput = document.getElementById('comic-id');
        const originalComicIdInput = document.getElementById('original-comic-id');
        const comicTypeSelect = document.getElementById('comic-type');
        const comicNameInput = document.getElementById('comic-name');
        const comicNameEmptyCheckbox = document.getElementById('comic-name-empty-checkbox');
        const comicTranscriptTextarea = document.getElementById('comic-transcript');
        const comicChapterInput = document.getElementById('comic-chapter');
        const saveSingleButton = document.getElementById('save-single-button');
        const cancelEditButton = document.getElementById('cancel-edit-button');
        const addComicButton = document.getElementById('add-new-comic-button');
        const comicDataTable = document.getElementById('comic-data-table');
        const messageBoxElement = document.getElementById('message-box');
        const formSection = document.querySelector('.form-section');

        const toggleTranscriptViewButton = document.getElementById('toggle-transcript-view');
        let isTranscriptRendered = false;

        let summernoteInitialized = false;

        function initializeSummernote() {
            if (!summernoteInitialized) {
                $('#comic-transcript').summernote({
                    height: 150,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['insert', ['link']],
                        ['view', ['fullscreen', 'codeview', 'help']]
                    ],
                    callbacks: {
                        onPaste: function (e) {
                            var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
                            var pastedData = clipboardData.getData('text/html');

                            if (pastedData) {
                                e.preventDefault();
                                let cleanedData = pastedData.replace(/<!--[\s\S]*?-->/g, '');
                                    cleanedData = cleanedData.replace(/<\/?\w+:[^>]*>/g, '');
                                cleanedData = cleanedData.replace(/\s(class|style|lang|dir|width|height|face|size|start|type|value|id|name|title)=["'][^"']*["']/gi, '');
                                cleanedData = cleanedData.replace(/<b\s*>/gi, '<strong>').replace(/<\/b\s*>/gi, '</strong>');
                                cleanedData = cleanedData.replace(/<i\s*>/gi, '<em>').replace(/<\/i\s*>/gi, '</em>');
                                cleanedData = cleanedData.replace(/<\/?span[^>]*>/gi, '');
                                cleanedData = cleanedData.replace(/<[^\/>][^>]*>\s*<\/[^>]+>/g, '');
                                $('#comic-transcript').summernote('pasteHTML', cleanedData);
                            }
                        }
                    }
                });
                summernoteInitialized = true;
            }
        }

        function resetForm() {
            comicEditForm.reset();
            comicIdInput.value = '';
            originalComicIdInput.value = '';
            comicIdInput.readOnly = false;

            comicNameEmptyCheckbox.checked = false;
            comicNameInput.required = true;
            comicNameInput.disabled = false;

            saveSingleButton.textContent = 'Speichern';
            saveSingleButton.classList.remove('edit');
            saveSingleButton.classList.add('button');
            if (summernoteInitialized) {
                $('#comic-transcript').summernote('code', '');
            } else {
                comicTranscriptTextarea.value = '';
            }
            comicChapterInput.value = '';

            // NEU: Bildvorschau ausblenden und leeren
            const previewContainer = document.getElementById('comic-image-preview-container');
            if (previewContainer) {
                previewContainer.style.display = 'none';
                document.getElementById('comic-image-preview').src = '';
            }
        }

        function showMessage(msg, type) {
            if (messageBoxElement) {
                messageBoxElement.textContent = msg;
                messageBoxElement.className = 'message-box ' + type;
                messageBoxElement.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(() => {
                    messageBoxElement.style.display = 'none';
                }, 5000);
            } else {
                console.warn("Message box element not found, falling back to console: " + msg);
            }
        }

        comicNameEmptyCheckbox.addEventListener('change', function () {
            if (this.checked) {
                comicNameInput.value = '';
                comicNameInput.required = false;
                comicNameInput.disabled = true;
            } else {
                comicNameInput.required = true;
                comicNameInput.disabled = false;
            }
        });

        comicDataTable.addEventListener('click', function (event) {
            const target = event.target;
            const editButton = target.closest('.edit-button');
            const deleteButton = target.closest('.delete-button');
            const transcriptCell = target.closest('.transcript-content');

            if (transcriptCell && !editButton && !deleteButton) {
                const isExpanded = transcriptCell.dataset.isExpanded === 'true';
                transcriptCell.dataset.isExpanded = !isExpanded;
                transcriptCell.classList.toggle('transcript-collapsed', isExpanded);
                transcriptCell.classList.toggle('transcript-expanded', !isExpanded);
            }

            if (editButton) {
                // NEUE LOGIK FÜR DIE BILDVORSCHAU
                const row = editButton.closest('tr');
                const lowresPath = row.dataset.lowresPath;
                const previewContainer = document.getElementById('comic-image-preview-container');
                const previewImage = document.getElementById('comic-image-preview');
                const placeholderUrl = 'https://placehold.co/825x1075/cccccc/333333?text=Comicseite%0Anicht%0Averf%C3%BCgbar';

                // Setze die Bildquelle: entweder der gefundene Pfad oder der Platzhalter
                if (lowresPath) {
                    previewImage.src = lowresPath;
                } else {
                    previewImage.src = placeholderUrl;
                }
                // Zeige den Vorschau-Container an
                previewContainer.style.display = 'block';

                // Bestehender Code zum Füllen des Formulars
                const comicId = row.dataset.comicId;
                const comicType = row.querySelector('.comic-type-display').textContent;
                const comicName = row.querySelector('.comic-name-display').textContent;
                const comicTranscript = row.querySelector('.transcript-content').dataset.rawHtml;
                const comicChapter = row.querySelector('.comic-chapter-display').textContent;

                comicIdInput.value = comicId;
                originalComicIdInput.value = comicId;
                comicIdInput.readOnly = true;
                comicTypeSelect.value = comicType;
                comicNameInput.value = comicName;

                if (comicName === '') {
                    comicNameEmptyCheckbox.checked = true;
                } else {
                    comicNameEmptyCheckbox.checked = false;
                }
                comicNameEmptyCheckbox.dispatchEvent(new Event('change'));

                initializeSummernote();
                $('#comic-transcript').summernote('code', comicTranscript);

                comicChapterInput.value = comicChapter;

                saveSingleButton.textContent = 'Änderungen speichern';
                saveSingleButton.classList.add('edit');
                saveSingleButton.classList.remove('button');

                if (!formSection.classList.contains('expanded')) {
                    formSection.classList.add('expanded');
                }
                formSection.scrollIntoView({ behavior: 'smooth' });
            } else if (deleteButton) {
                const row = deleteButton.closest('tr');
                const comicId = row.dataset.comicId;

                if (confirm(`Sind Sie sicher, dass Sie den Comic-Eintrag mit der ID ${comicId} endgültig löschen möchten?`)) {
                    const dataToSend = {
                        action: 'delete',
                        comic_id: comicId
                    };

                    fetch(window.location.pathname, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dataToSend)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showMessage(data.message, 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showMessage('Fehler beim Löschen: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            showMessage('Ein Netzwerkfehler ist aufgetreten: ' + error.message, 'error');
                        });
                }
            }
        });

        addComicButton.addEventListener('click', function () {
            resetForm();
            saveSingleButton.textContent = 'Hinzufügen';
            saveSingleButton.classList.add('button');
            saveSingleButton.classList.remove('edit');
            comicIdInput.readOnly = false;

            initializeSummernote();

            if (!formSection.classList.contains('expanded')) {
                formSection.classList.add('expanded');
            }
            formSection.scrollIntoView({ behavior: 'smooth' });
        });

        cancelEditButton.addEventListener('click', function () {
            resetForm();
            showMessage('Bearbeitung abgebrochen.', 'info');
        });

        comicEditForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const comicId = comicIdInput.value.trim();
            const originalComicId = originalComicIdInput.value.trim();
            const comicType = comicTypeSelect.value.trim();
            const comicName = comicNameInput.value.trim();
            const comicTranscript = summernoteInitialized ? $('#comic-transcript').summernote('code').trim() : comicTranscriptTextarea.value.trim();
            const comicChapter = comicChapterInput.value.trim();
            const parsedChapter = parseFloat(comicChapter.replace(',', '.'));

            if (!comicId || !comicType || (!comicName && !comicNameEmptyCheckbox.checked) || (comicChapter !== '' && (isNaN(parsedChapter) || parsedChapter < 0))) {
                showMessage('Bitte füllen Sie alle Felder (ID, Typ) aus. Name muss entweder ausgefüllt oder als "leer" markiert sein. Kapitel muss eine Zahl (>= 0) sein oder leer bleiben.', 'error');
                return;
            }
            if (!/^\d{8}$/.test(comicId)) {
                showMessage('Comic ID muss eine 8-stellige Zahl (JJJJMMTT) sein.', 'error');
                return;
            }

            const comicData = {
                comic_id: comicId,
                original_comic_id: originalComicId,
                comic_type: comicType,
                comic_name: comicName,
                comic_transcript: comicTranscript,
                comic_chapter: comicChapter
            };

            const dataToSend = {
                action: 'save',
                page: comicData
            };

            fetch(window.location.pathname, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const targetPage = data.page.toString();
                        const currentPage = new URLSearchParams(window.location.search).get('page') || '1';
                        const targetUrl = `?page=${targetPage}#comic-row-${data.comic_id}`;

                        if (targetPage === currentPage && window.location.pathname + window.location.search === window.location.pathname + `?page=${targetPage}`) {
                            window.location.href = targetUrl;
                            window.location.reload();
                        } else {
                            window.location.href = targetUrl;
                        }
                    } else {
                        showMessage('Fehler beim Speichern: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showMessage('Ein Netzwerkfehler ist aufgetreten: ' + error.message, 'error');
                });
        });

        document.querySelectorAll('.collapsible-header').forEach(header => {
            const section = header.closest('.collapsible-section');
            const icon = header.querySelector('i');

            if (section.classList.contains('expanded')) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }

            header.addEventListener('click', function () {
                const wasExpanded = section.classList.contains('expanded');
                section.classList.toggle('expanded');
                if (section.classList.contains('expanded')) {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                    if (section.classList.contains('form-section') && !wasExpanded) {
                        initializeSummernote();
                    }
                    if (section.classList.contains('form-section')) {
                        section.scrollIntoView({ behavior: 'smooth' });
                    }
                } else {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
            });
        });

        // KORREKTUR: Logik für den Ansicht-Umschalter
        toggleTranscriptViewButton.addEventListener('click', function () {
            isTranscriptRendered = !isTranscriptRendered;
            const allTranscripts = comicDataTable.querySelectorAll('.transcript-content');

            allTranscripts.forEach(cell => {
                const rawHtml = cell.dataset.rawHtml;
                const isExpanded = cell.dataset.isExpanded === 'true'; // Individuellen Zustand auslesen

                if (isTranscriptRendered) {
                    // In den Render-Modus wechseln
                    cell.innerHTML = rawHtml;
                } else {
                    // Zurück in den Code-Modus wechseln
                    cell.textContent = rawHtml;
                }

                // Zustand (ein-/ausgeklappt) wiederherstellen, der für die Zelle gilt
                cell.classList.toggle('transcript-expanded', isExpanded);
                cell.classList.toggle('transcript-collapsed', !isExpanded);
            });

            this.innerHTML = isTranscriptRendered ? '<i class="fas fa-code"></i> Code anzeigen' : '<i class="fas fa-eye"></i> HTML rendern';
            localStorage.setItem('transcriptViewMode', isTranscriptRendered ? 'rendered' : 'source');
        });

        // Gespeicherte Ansicht beim Laden wiederherstellen
        const savedViewMode = localStorage.getItem('transcriptViewMode');
        if (savedViewMode === 'rendered') {
            setTimeout(() => toggleTranscriptViewButton.click(), 0);
        }
    });
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in data_editor_comic.php eingebunden.");
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>