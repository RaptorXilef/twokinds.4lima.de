<?php
/**
 * Administrationsseite zum Bearbeiten der comic_var.json Konfigurationsdatei.
 *
 * @file      /admin/data_editor_comic.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   5.5.5
 * @since     5.4.0 Implementiert robustes, CSP-konformes Fallback für Charakterbilder im Modal.
 * Zeigt '?' bei fehlendem Pfad und 'Fehlt' bei Ladefehler, korrigiert 'undefined' Fehler.
 * @since     5.5.0 Hinzufügen eines 'C'-Status-Tags zur Anzeige, ob Charaktere zugewiesen sind.
 * @since     5.5.1 Fügt die fehlenden Schaltflächen für "Vorherige" (‹) und "Nächste" (›) -Seite hinzu. 
 * Die Schaltfläche für die aktuell ausgewählte Seite wird jetzt in beiden Themes (Hell und Dunkel) korrekt hervorgehoben.
 * @since     5.5.2 Nach Bearbeitung scrollt die Ansicht zur bearbeiteten Zeile, die zur Hervorhebung kurz aufleuchtet.
 * @since     5.5.3 Erstellt/Löscht automatisch die zugehörigen PHP-Dateien im /comic/-Ordner beim Speichern von Änderungen.
 * @since     5.5.4 Korrigiert den Inhalt neu erstellter PHP-Dateien auf die korrekte einzelne require_once-Anweisung.
 * @since     5.5.5 Behebt Fehler beim Löschen von Einträgen, Erstellen von PHP-Dateien und der Anzeige im "Neu"-Dialog.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;
define('COMIC_PAGES_PER_PAGE', 50);

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$comicPhpPagesPath = __DIR__ . '/../comic/';
$settingsFilePath = __DIR__ . '/../src/config/generator_settings.json';
$imageCachePath = __DIR__ . '/../src/config/comic_image_cache.json';
$charaktereJsonPath = __DIR__ . '/../src/config/charaktere.json';


// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'data_editor_comic' => ['last_run_timestamp' => null]
    ];
    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        file_put_contents($filePath, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }
    $content = file_get_contents($filePath);
    $settings = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        return $defaults;
    if (!isset($settings['data_editor_comic']))
        $settings['data_editor_comic'] = $defaults['data_editor_comic'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// --- Comic-Daten Funktionen ---
function loadJsonData(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0)
        return [];
    $content = file_get_contents($path);
    if ($content === false)
        return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveComicData(string $path, array $data, bool $debugMode): bool
{
    ksort($data);
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false)
        return false;
    return file_put_contents($path, $jsonContent) !== false;
}

function getComicIdsFromImages(array $dirs, bool $debugMode): array
{
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if (isset($fileInfo['filename']) && preg_match('/^\d{8}$/', $fileInfo['filename']) && isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                    $imageIds[$fileInfo['filename']] = true;
                }
            }
        }
    }
    return array_keys($imageIds);
}

function getComicIdsFromPhpFiles(string $pagesDir, bool $debugMode): array
{
    $phpIds = [];
    if (is_dir($pagesDir)) {
        $files = scandir($pagesDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && preg_match('/^\d{8}$/', pathinfo($file, PATHINFO_FILENAME))) {
                $phpIds[pathinfo($file, PATHINFO_FILENAME)] = true;
            }
        }
    }
    return array_keys($phpIds);
}

function get_image_cache_local(string $cachePath): array
{
    if (!file_exists($cachePath))
        return [];
    $content = file_get_contents($cachePath);
    if ($content === false)
        return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}


// --- AJAX-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_end_clean();
    header('Content-Type: application/json');

    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($input, $requestData);
    }

    $token = $requestData['csrf_token'] ?? null;
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF-Token-Validierung fehlgeschlagen. Bitte laden Sie die Seite neu.']);
        exit;
    }

    $action = $requestData['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'save_comic_data':
            $comicDataToSave = $requestData['comics'] ?? [];
            $currentData = loadJsonData($comicVarJsonPath, $debugMode);

            $newIds = array_keys(array_diff_key($comicDataToSave, $currentData));
            $deletedIds = array_keys(array_diff_key($currentData, $comicDataToSave));

            $createdCount = 0;
            foreach ($newIds as $id) {
                $filePath = $comicPhpPagesPath . $id . '.php';
                if (!file_exists($filePath)) {
                    $phpContent = "<?php require_once __DIR__ . '/../src/components/comic_page_renderer.php'; ?>";
                    if (file_put_contents($filePath, $phpContent) !== false) {
                        $createdCount++;
                    }
                }
            }

            $deletedCount = 0;
            foreach ($deletedIds as $id) {
                $filePath = $comicPhpPagesPath . $id . '.php';
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $deletedCount++;
                    }
                }
            }

            if (saveComicData($comicVarJsonPath, $comicDataToSave, $debugMode)) {
                $message = "Comic-Daten erfolgreich gespeichert!";
                if ($createdCount > 0) {
                    $message .= " $createdCount PHP-Datei(en) erstellt.";
                }
                if ($deletedCount > 0) {
                    $message .= " $deletedCount PHP-Datei(en) gelöscht.";
                }
                $response = ['success' => true, 'message' => $message];
            } else {
                $response['message'] = 'Fehler beim Speichern der Comic-Daten.';
                http_response_code(500);
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings($settingsFilePath, $debugMode);
            $currentSettings['data_editor_comic']['last_run_timestamp'] = time();
            if (saveGeneratorSettings($settingsFilePath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
        case 'update_original_url_cache':
            $comicIdToUpdate = $requestData['comic_id'] ?? null;
            $imageUrlToCache = $requestData['image_url'] ?? null;
            $cacheKey = $requestData['cache_key'] ?? 'url_originalbild';

            if ($comicIdToUpdate && $imageUrlToCache && in_array($cacheKey, ['url_originalbild', 'url_originalsketch'])) {
                $currentCache = get_image_cache_local($imageCachePath);
                if (!isset($currentCache[$comicIdToUpdate])) {
                    $currentCache[$comicIdToUpdate] = [];
                }
                $currentCache[$comicIdToUpdate][$cacheKey] = $imageUrlToCache;

                if (file_put_contents($imageCachePath, json_encode($currentCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $response['success'] = true;
                    $response['message'] = "Cache für '$cacheKey' von $comicIdToUpdate aktualisiert.";
                } else {
                    $response['message'] = "Fehler beim Speichern der Cache-Datei.";
                    http_response_code(500);
                }
            } else {
                $response['message'] = "Fehlende oder ungültige Daten zum Cachen der URL.";
                http_response_code(400);
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings($settingsFilePath, $debugMode);
$comicEditorSettings = $settings['data_editor_comic'];

$jsonData = loadJsonData($comicVarJsonPath, $debugMode);
$charaktereData = loadJsonData($charaktereJsonPath, $debugMode);
$imageDirs = [__DIR__ . '/../assets/comic_lowres/', __DIR__ . '/../assets/comic_hires/'];
$imageIds = getComicIdsFromImages($imageDirs, $debugMode);
$phpIds = getComicIdsFromPhpFiles($comicPhpPagesPath, $debugMode);

$allIds = array_unique(array_merge(array_keys($jsonData), $imageIds, $phpIds));
sort($allIds);

$fullComicData = [];
foreach ($allIds as $id) {
    $defaults = [
        'type' => 'Comicseite',
        'name' => '',
        'transcript' => '',
        'chapter' => null,
        'datum' => $id,
        'url_originalbild' => '',
        'url_originalsketch' => '',
        'charaktere' => []
    ];
    $existingData = $jsonData[$id] ?? [];
    $fullComicData[$id] = array_merge($defaults, $existingData);

    $sources = [];
    if (isset($jsonData[$id]))
        $sources[] = 'json';
    if (in_array($id, $imageIds))
        $sources[] = 'image';
    if (in_array($id, $phpIds))
        $sources[] = 'php';
    if (!empty($fullComicData[$id]['url_originalbild']))
        $sources[] = 'url';
    $fullComicData[$id]['sources'] = $sources;
}

$cachedImagesForJs = get_image_cache_local($imageCachePath);

$pageTitle = 'Adminbereich - Comic Daten Editor';
$pageHeader = 'Comic Daten Editor';
$robotsContent = 'noindex, nofollow';
$additionalScripts = <<<HTML
    <script nonce="{$nonce}" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script nonce="{$nonce}" src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
HTML;

include $headerPath;
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($comicEditorSettings['last_run_timestamp']): ?>
                    <p class="status-message status-info">Letzte Speicherung am
                        <?php echo date('d.m.Y \u\m H:i:s', $comicEditorSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Comic Daten Editor</h2>
            <p>Verwalte hier die Metadaten für jede Comic-Seite. Fehlende Einträge für existierende Bilder werden
                automatisch hinzugefügt.</p>
        </div>

        <div class="pagination"></div>

        <div class="table-controls">
            <div class="search-container">
                <input type="text" id="search-input" placeholder="Nach ID oder Name suchen...">
                <button id="clear-search-btn" class="button" style="display: none;">&times;</button>
            </div>
            <div class="marker-legend-group">
                <div class="marker-legend">
                    <strong>Quellen:</strong>
                    <span class="source-marker source-json" title="Eintrag existiert in comic_var.json">JSON</span>
                    <span class="source-marker source-image"
                        title="Mindestens eine Bilddatei existiert lokal">Bild</span>
                    <span class="source-marker source-php"
                        title="Eine PHP-Datei existiert für diese Seite in /comic/">PHP</span>
                    <span class="source-marker source-url" title="Ein Originalbild ist via URL verknüpft">URL</span>
                </div>
                <div class="marker-legend">
                    <strong>Status:</strong>
                    <span class="source-marker status-json present"
                        title="JSON-Daten vollständig (Name, Transkript, etc.)">J</span>
                    <span class="source-marker status-lowres present" title="Low-Res Bild">L</span>
                    <span class="source-marker status-hires present" title="High-Res Bild">H</span>
                    <span class="source-marker status-php present" title="PHP-Datei">P</span>
                    <span class="source-marker status-socialmedia present" title="Social Media Bild">S</span>
                    <span class="source-marker status-thumbnails present" title="Thumbnail">T</span>
                    <span class="source-marker source-url present" title="URL zum Originalbild">U</span>
                    <span class="source-marker status-charaktere present" title="Charaktere zugewiesen">C</span>
                </div>
            </div>
        </div>

        <div class="sitemap-table-container">
            <table class="sitemap-table" id="comic-table">
                <thead>
                    <tr>
                        <th>ID & Quellen</th>
                        <th>Vorschau</th>
                        <th>Name & Kapitel</th>
                        <th>Transkript</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="pagination"></div>

        <div id="save-confirmation-box" class="hidden-by-default"></div>
        <div id="fixed-buttons-container">
            <button id="add-row-btn" class="button"><i class="fas fa-plus-circle"></i> Neuer Eintrag</button>
            <button id="save-all-btn" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
        </div>
        <br>
        <div id="message-box" class="hidden-by-default"></div>
    </div>
</article>

<div id="edit-modal" class="modal hidden-by-default">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2 id="modal-title-header">Eintrag bearbeiten</h2>
        <div class="form-group"><label>Comic ID:</label><input type="text" id="modal-comic-id"></div>
        <div class="form-group"><label for="modal-type">Typ:</label><select id="modal-type">
                <option>Comicseite</option>
                <option>Lückenfüller</option>
            </select></div>
        <div class="form-group"><label for="modal-name">Name:</label><input type="text" id="modal-name"></div>
        <div class="form-group"><label for="modal-transcript">Transkript:</label><textarea
                id="modal-transcript"></textarea></div>
        <div class="form-group"><label for="modal-chapter">Kapitel:</label><input type="text" id="modal-chapter"></div>
        <div class="form-group"><label for="modal-url">Originalbild Dateiname:</label><input type="text" id="modal-url">
        </div>
        <div class="form-group"><label for="modal-url-sketch">Originalskizze Dateiname:</label><input type="text"
                id="modal-url-sketch"></div>

        <div id="modal-image-preview-section">
            <div id="modal-image-controls" class="button-toggle-group">
                <button class="button-toggle active" data-view="de">Deutsch</button>
                <button class="button-toggle" data-view="en">Englisch</button>
                <button class="button-toggle" data-view="sketch">Skizze</button>
                <button class="button-toggle" data-view="both">Beide</button>
            </div>
            <div id="modal-image-previews">
                <div class="image-preview-box" id="modal-preview-de">
                    <p>Deutsche Version (Low-Res)</p>
                    <img src="" alt="Deutsche Vorschau">
                </div>
                <div class="image-preview-box" id="modal-preview-en">
                    <p>Englisches Original</p>
                    <img src="" alt="Englische Vorschau">
                </div>
                <div class="image-preview-box" id="modal-preview-sketch">
                    <p>Original Skizze</p>
                    <img src="" alt="Original Skizze Vorschau">
                </div>
            </div>
        </div>

        <div id="modal-charaktere-section">
            <!-- Charakterauswahl wird hier per JS eingefügt -->
        </div>

        <div class="modal-buttons">
            <button id="modal-save-btn" class="button">Übernehmen</button>
            <button id="modal-cancel-btn" class="button delete">Abbrechen</button>
        </div>
    </div>
</div>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    :root {
        --missing-grid-border-color: #e0e0e0;
    }

    body.theme-night {
        --missing-grid-border-color: #045d81;
    }

    .status-message {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
    }

    .status-green {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-red {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .status-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .status-orange {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .sitemap-table-container {
        overflow-x: auto;
    }

    .sitemap-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .sitemap-table th,
    .sitemap-table td {
        padding: 8px;
        border-bottom: 1px solid var(--missing-grid-border-color);
        text-align: left;
        vertical-align: middle;
        word-wrap: break-word;
    }

    .sitemap-table th:nth-child(1),
    .sitemap-table td:nth-child(1) {
        width: 10%;
    }

    /* ID */
    .sitemap-table th:nth-child(2),
    .sitemap-table td:nth-child(2) {
        width: 120px;
    }

    /* Vorschau */
    .sitemap-table th:nth-child(3),
    .sitemap-table td:nth-child(3) {
        width: 25%;
    }

    /* Name */
    .sitemap-table th:nth-child(4),
    .sitemap-table td:nth-child(4) {
        width: 35%;
    }

    /* Transkript */
    .sitemap-table th:nth-child(5),
    .sitemap-table td:nth-child(5) {
        width: 15%;
    }

    /* Status */
    .sitemap-table th:nth-child(6),
    .sitemap-table td:nth-child(6) {
        width: 100px;
    }

    /* Aktionen */

    body.theme-night .sitemap-table {
        color: #f0f0f0;
    }

    .description-preview {
        max-height: 5em;
        overflow: auto;
        border: 1px solid #eee;
        padding: 5px;
        border-radius: 3px;
    }

    body.theme-night .description-preview {
        border-color: #045d81;
    }

    .missing-info {
        border: 2px solid #dc3545 !important;
        background-color: #f8d7da33 !important;
    }

    .comic-thumbnail {
        max-width: 100px;
        height: auto;
        border-radius: 4px;
        display: block;
        margin-top: 5px;
    }

    .comic-type-display {
        font-size: 0.8em;
        font-style: italic;
        display: block;
    }

    #fixed-buttons-container {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
        gap: 10px;
    }

    .hidden-by-default {
        display: none;
    }

    .source-markers,
    .status-icons {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 5px;
    }

    .source-marker,
    .status-icon {
        font-size: 0.7em;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: bold;
        color: white;
        line-height: 1.5;
    }

    .source-json,
    .status-json {
        background-color: #6c757d;
    }

    .source-image {
        background-color: #007bff;
    }

    .status-lowres {
        background-color: #007bff;
    }

    .status-hires {
        background-color: #0056b3;
    }

    .source-php,
    .status-php {
        background-color: #28a745;
    }

    .source-url,
    .status-url {
        background-color: #9f58d1;
    }

    .status-socialmedia {
        background-color: #fd7e14;
    }

    .status-thumbnails {
        background-color: #ffc107;
        color: #333;
    }

    .status-charaktere {
        background-color: #6f42c1;
    }

    .status-icon.present {
        background-color: #28a745;
    }

    .status-icon.missing {
        background-color: #dc3545;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 20px 0;
        gap: 5px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #007bff;
        cursor: pointer;
    }

    .pagination span.current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        cursor: default;
    }

    body.theme-night .pagination a,
    body.theme-night .pagination span {
        border-color: #005a7e;
        color: #7bbdff;
        background-color: #00425c;
    }

    body.theme-night .pagination span.current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 101;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border-radius: 8px;
        width: 100%;
        max-width: 1045px;
        position: relative;
    }

    @keyframes highlight-row {
        from {
            background-color: #d4edda;
        }

        to {
            background-color: transparent;
        }
    }

    .row-highlight {
        animation: highlight-row 8s ease-out;
    }

    body.theme-night .row-highlight {
        animation: highlight-row-dark 8s ease-out;
    }

    @keyframes highlight-row-dark {
        from {
            background-color: #1a4d2e;
        }

        to {
            background-color: transparent;
        }
    }

    body.theme-night .modal-content {
        background-color: #00425c;
        border: 1px solid #007bb5;
    }

    .close-button {
        color: #aaa;
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .modal-buttons {
        margin-top: 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    body.theme-night .form-group input,
    body.theme-night .form-group textarea,
    body.theme-night .form-group select {
        background-color: #03425b;
        border-color: #045d81;
        color: #f0f0f0;
    }

    body.theme-night .note-editor.note-frame {
        background-color: #00425c;
    }

    body.theme-night .note-toolbar {
        background-color: #005a7e;
        border-bottom: 1px solid #007bb5;
    }

    body.theme-night .note-btn {
        background-color: #006690;
        color: #fff;
        border-color: #007bb5;
    }

    body.theme-night .note-editable {
        background-color: #00425c;
        color: #f0f0f0;
    }

    body.theme-night .note-statusbar {
        border-top: 1px solid #007bb5;
    }

    .note-tooltip {
        width: auto !important;
        height: auto !important;
        min-height: 0 !important;
        left: auto !important;
        right: auto !important;
        line-height: 1.2 !important;
        padding: 5px;
        white-space: nowrap;
    }

    .banner,
    body.theme-night #banner,
    #banner-lights-off {
        z-index: 99 !important;
    }

    .note-modal-backdrop {
        z-index: 100;
    }

    .table-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 20px;
        /* Erhöhter Abstand */
    }

    .search-container {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    #search-input {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }

    body.theme-night #search-input {
        background-color: #03425b;
        border-color: #045d81;
        color: #f0f0f0;
    }

    #clear-search-btn {
        padding: 5px 10px;
    }

    .marker-legend-group {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    #modal-image-preview-section {
        margin-top: 20px;
        text-align: center;
    }

    .button-toggle-group {
        display: inline-flex;
        justify-content: center;
        gap: 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 15px;
    }

    .button-toggle {
        padding: 8px 15px;
        border: none;
        background-color: #f0f0f0;
        cursor: pointer;
        transition: background-color 0.3s;
        color: #333;
        margin: 0;
        border-radius: 0;
    }

    .button-toggle:not(:last-child) {
        border-right: 1px solid #ccc;
    }

    .button-toggle:hover {
        background-color: #e0e0e0;
    }

    .button-toggle.active {
        background-color: #007bff;
        color: white;
    }

    body.theme-night .button-toggle-group {
        border-color: #007bb5;
    }

    body.theme-night .button-toggle {
        background-color: #005a7e;
        color: #fff;
        border-right-color: #007bb5;
    }

    body.theme-night .button-toggle:hover {
        background-color: #006690;
    }

    body.theme-night .button-toggle.active {
        background-color: #007bff;
    }

    #modal-image-previews {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .image-preview-box {
        display: none;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .image-preview-box img {
        max-width: 500px;
        height: auto;
        max-height: 100%;
        border-radius: 4px;
        border: 1px solid #ccc;
    }

    body.theme-night .image-preview-box img {
        border-color: #045d81;
    }

    #modal-charaktere-section {
        margin-top: 20px;
        border-top: 1px solid var(--missing-grid-border-color);
        padding-top: 15px;
    }

    .charakter-kategorie {
        margin-bottom: 15px;
    }

    .charakter-kategorie h3 {
        margin-bottom: 10px;
        font-size: 1.1em;
        border-bottom: 1px solid var(--missing-grid-border-color);
        padding-bottom: 5px;
    }

    .charaktere-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .charakter-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
    }

    .charakter-item img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid transparent;
        transition: border-color 0.3s, filter 0.3s;
    }

    .charakter-item:not(.active) img {
        filter: grayscale(100%);
    }

    .charakter-item.active img {
        border-color: #007bff;
        filter: grayscale(0%);
    }

    .charakter-item span {
        margin-top: 5px;
        font-size: 0.8em;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        let comicData = <?php echo json_encode($fullComicData, JSON_UNESCAPED_SLASHES); ?>;
        let allComicIds = Object.keys(comicData);
        let filteredComicIds = [...allComicIds];
        let cachedImages = <?php echo json_encode($cachedImagesForJs, JSON_UNESCAPED_SLASHES); ?>;
        const charaktereData = <?php echo json_encode($charaktereData, JSON_UNESCAPED_SLASHES); ?>;

        const tableBody = document.querySelector('#comic-table tbody');
        const saveAllBtn = document.getElementById('save-all-btn');
        const addRowBtn = document.getElementById('add-row-btn');
        const messageBox = document.getElementById('message-box');
        const lastRunContainer = document.getElementById('last-run-container');
        const paginationContainers = document.querySelectorAll('.pagination');
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search-btn');

        const editModal = document.getElementById('edit-modal');
        const modalCloseBtn = editModal.querySelector('.close-button');
        const modalSaveBtn = document.getElementById('modal-save-btn');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        const modalImageControls = document.getElementById('modal-image-controls');
        const modalPreviewDe = document.getElementById('modal-preview-de');
        const modalPreviewEn = document.getElementById('modal-preview-en');
        const modalPreviewSketch = document.getElementById('modal-preview-sketch');
        const modalCharaktereSection = document.getElementById('modal-charaktere-section');
        let activeEditId = null;
        let debounceTimer;

        const ITEMS_PER_PAGE = <?php echo COMIC_PAGES_PER_PAGE; ?>;
        let currentPage = 1;

        $('#modal-transcript').summernote({
            placeholder: "Transkript hier eingeben...", tabsize: 2, height: 200,
            toolbar: [['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link']], ['view', ['codeview']]],
        });

        const renderTable = () => {
            filteredComicIds.sort();
            tableBody.innerHTML = '';
            const start = (currentPage - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const paginatedIds = filteredComicIds.slice(start, end);

            paginatedIds.forEach(id => {
                const chapter = comicData[id];
                if (!chapter) return;
                const row = document.createElement('tr');
                row.dataset.id = id;

                const descPreview = new DOMParser().parseFromString(chapter.transcript || '', 'text/html').body;
                descPreview.querySelectorAll('script, style').forEach(el => el.remove());

                const isNameMissing = !chapter.name || chapter.name.trim() === '';
                const isTranscriptMissing = !chapter.transcript || chapter.transcript.trim() === '';
                const isChapterMissing = chapter.chapter === null || String(chapter.chapter).trim() === '';
                const isUrlMissing = !chapter.url_originalbild || chapter.url_originalbild.trim() === '';

                const hasJson = chapter.sources.includes('json');
                const hasPhp = chapter.sources.includes('php');
                const hasUrl = chapter.sources.includes('url');
                const hasLowres = cachedImages[id]?.lowres;
                const hasHires = cachedImages[id]?.hires;
                const hasThumb = cachedImages[id]?.thumbnails;
                const hasSocial = cachedImages[id]?.socialmedia;
                const hasCharacters = chapter.charaktere && chapter.charaktere.length > 0;
                const thumbnailPath = (cachedImages[id] && cachedImages[id].thumbnails) ? `../${cachedImages[id].thumbnails}` : (cachedImages['placeholder'] && cachedImages['placeholder'].lowres ? `../${cachedImages['placeholder'].lowres}` : '../assets/comic_thumbnails/placeholder.jpg');

                row.innerHTML = `
                <td>
                    ${id}
                    <div class="source-markers">
                        ${(chapter.sources || []).map(s => `<span class="source-marker source-${s.toLowerCase()}" title="Quelle: ${s}">${s.toUpperCase()}</span>`).join('')}
                    </div>
                </td>
                <td>
                    <span class="comic-type-display">${chapter.type || ''}</span>
                    <img src="${thumbnailPath}" class="comic-thumbnail" alt="Vorschau" onerror="this.src='../assets/comic_thumbnails/placeholder.jpg'; this.onerror=null;">
                </td>
                <td class="${isNameMissing ? 'missing-info' : ''}">
                    ${(isChapterMissing || chapter.chapter === null) ? '' : `<strong>Kap. ${chapter.chapter}:</strong><br>`}
                    ${chapter.name || '<em>Kein Name</em>'}
                </td>
                <td class="${isTranscriptMissing ? 'missing-info' : ''}"><div class="description-preview">${descPreview.innerHTML || '<em>Kein Transkript</em>'}</div></td>
                <td>
                    <div class="status-icons">
                        <span class="status-icon ${hasJson && !isNameMissing && !isTranscriptMissing && !isChapterMissing && !isUrlMissing ? 'present' : 'missing'}" title="JSON-Daten vollständig">J</span>
                        <span class="status-icon ${hasLowres ? 'present' : 'missing'}" title="Low-Res Bild">L</span>
                        <span class="status-icon ${hasHires ? 'present' : 'missing'}" title="High-Res Bild">H</span>
                        <span class="status-icon ${hasPhp ? 'present' : 'missing'}" title="PHP-Datei">P</span>
                        <span class="status-icon ${hasSocial ? 'present' : 'missing'}" title="Social Media Bild">S</span>
                        <span class="status-icon ${hasThumb ? 'present' : 'missing'}" title="Thumbnail">T</span>
                        <span class="status-icon ${hasUrl ? 'present' : 'missing'}" title="URL zum Originalbild">U</span>
                        <span class="status-icon ${hasCharacters ? 'present' : 'missing'}" title="Charaktere zugewiesen">C</span>
                    </div>
                </td>
                <td class="action-buttons">
                    <button class="button edit-row-btn"><i class="fas fa-edit"></i></button>
                    <button class="button delete-row-btn"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
                tableBody.appendChild(row);
            });
            renderPagination();
        };

        const renderPagination = () => {
            const totalPages = Math.ceil(filteredComicIds.length / ITEMS_PER_PAGE);

            paginationContainers.forEach(container => {
                container.innerHTML = '';
                if (totalPages <= 1) return;

                let htmlParts = [];

                // "First" and "Previous" buttons
                if (currentPage > 1) {
                    htmlParts.push(`<a data-page="1">&laquo;</a>`); // First
                    htmlParts.push(`<a data-page="${currentPage - 1}">&lsaquo;</a>`); // Previous
                }

                // Page numbers logic
                let startPage = Math.max(1, currentPage - 4);
                let endPage = Math.min(totalPages, currentPage + 4);

                if (startPage > 1) {
                    htmlParts.push(`<a data-page="1">1</a>`);
                    if (startPage > 2) {
                        htmlParts.push(`<span>...</span>`);
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    if (i === currentPage) {
                        htmlParts.push(`<span class="current-page">${i}</span>`);
                    } else {
                        htmlParts.push(`<a data-page="${i}">${i}</a>`);
                    }
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        htmlParts.push(`<span>...</span>`);
                    }
                    htmlParts.push(`<a data-page="${totalPages}">${totalPages}</a>`);
                }

                // "Next" and "Last" buttons
                if (currentPage < totalPages) {
                    htmlParts.push(`<a data-page="${currentPage + 1}">&rsaquo;</a>`); // Next
                    htmlParts.push(`<a data-page="${totalPages}">&raquo;</a>`); // Last
                }

                container.innerHTML = htmlParts.join('');
            });
        };

        function showMessage(message, type, duration = 5000) {
            messageBox.textContent = message;
            messageBox.className = `status-message status-${type}`;
            messageBox.style.display = 'block';
            if (duration > 0) {
                setTimeout(() => { messageBox.style.display = 'none'; }, duration);
            }
        }

        function updateTimestamp() {
            const now = new Date();
            const date = now.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = now.toLocaleTimeString('de-DE');
            const newStatusText = `Letzte Speicherung am ${date} um ${time} Uhr.`;
            let pElement = lastRunContainer.querySelector('.status-message');
            if (!pElement) {
                pElement = document.createElement('p');
                pElement.className = 'status-message status-info';
                lastRunContainer.prepend(pElement);
            }
            pElement.innerHTML = newStatusText;
        }

        async function saveSettings() {
            await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_settings', csrf_token: csrfToken })
            });
        }

        function updateImagePreviewsWithDebounce() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updateImagePreviews, 500);
        }

        async function updateImagePreviews() {
            const originalFilename = document.getElementById('modal-url').value;
            const sketchFilename = document.getElementById('modal-url-sketch').value;
            const deImg = modalPreviewDe.querySelector('img');
            const enImg = modalPreviewEn.querySelector('img');
            const sketchImg = modalPreviewSketch.querySelector('img');
            const placeholderSrc = (cachedImages['placeholder'] && cachedImages['placeholder'].lowres) ? `../${cachedImages['placeholder'].lowres}` : '../assets/comic_thumbnails/placeholder.jpg';

            if (activeEditId === 'new_entry') {
                deImg.src = placeholderSrc;
                enImg.src = placeholderSrc;
                sketchImg.src = placeholderSrc;
                return;
            }

            deImg.src = (cachedImages[activeEditId] && cachedImages[activeEditId].lowres) ? `../${cachedImages[activeEditId].lowres}` : placeholderSrc;

            findAndCacheUrl(activeEditId, originalFilename, 'https://cdn.twokinds.keenspot.com/comics/', 'url_originalbild', enImg, placeholderSrc);
            findAndCacheUrl(activeEditId, sketchFilename, 'https://twokindscomic.com/images/', 'url_originalsketch', sketchImg, placeholderSrc);
        }

        function findAndCacheUrl(comicId, filename, baseUrl, cacheKey, imgElement, placeholderSrc) {
            if (!filename) {
                imgElement.src = placeholderSrc;
                return;
            }

            let finalFilename = filename;
            if (cacheKey === 'url_originalsketch') {
                finalFilename += '_sketch';
            }

            imgElement.src = '../assets/icons/loading.webp';

            if (cachedImages[comicId] && cachedImages[comicId][cacheKey]) {
                const cachedUrl = new URL(cachedImages[comicId][cacheKey]);
                const baseCachedUrl = `${cachedUrl.origin}${cachedUrl.pathname}`;
                const expectedBaseUrl = baseUrl + finalFilename;

                if (baseCachedUrl.includes(expectedBaseUrl)) {
                    imgElement.src = cachedImages[comicId][cacheKey];
                    imgElement.onerror = () => {
                        delete cachedImages[comicId][cacheKey];
                        findAndCacheUrl(comicId, filename, baseUrl, cacheKey, imgElement, placeholderSrc);
                    };
                    return;
                }
            }

            const imageExtensions = ['png', 'jpg', 'gif', 'jpeg', 'webp'];
            let currentExtIndex = 0;

            function tryNextExtension() {
                if (currentExtIndex >= imageExtensions.length) {
                    imgElement.src = placeholderSrc;
                    imgElement.onerror = null;
                    return;
                }
                const testUrl = baseUrl + finalFilename + '.' + imageExtensions[currentExtIndex];
                imgElement.src = testUrl;
                currentExtIndex++;
            }

            imgElement.onload = async () => {
                const foundUrl = new URL(imgElement.src);
                const baseUrlWithoutQuery = `${foundUrl.origin}${foundUrl.pathname}`;

                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const cacheBuster = `?c=${year}${month}${day}`;

                const urlWithBuster = baseUrlWithoutQuery + cacheBuster;

                if (!cachedImages[comicId]) cachedImages[comicId] = {};
                cachedImages[comicId][cacheKey] = urlWithBuster;

                try {
                    await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'update_original_url_cache',
                            comic_id: comicId,
                            image_url: urlWithBuster,
                            cache_key: cacheKey,
                            csrf_token: csrfToken
                        })
                    });
                } catch (error) {
                    console.error(`Fehler beim Cachen der URL für ${cacheKey}:`, error);
                }
            };

            imgElement.onerror = tryNextExtension;
            tryNextExtension();
        }

        function setImageView(view) {
            modalImageControls.querySelectorAll('.button-toggle').forEach(btn => btn.classList.remove('active'));
            modalImageControls.querySelector(`[data-view="${view}"]`).classList.add('active');

            modalPreviewDe.style.display = (view === 'de' || view === 'both') ? 'flex' : 'none';
            modalPreviewEn.style.display = (view === 'en' || view === 'both') ? 'flex' : 'none';
            modalPreviewSketch.style.display = view === 'sketch' ? 'flex' : 'none';
        }

        // KORREKTUR: Robuste Funktion für Charakter-Bilder
        function renderCharakterSelection(comicId) {
            modalCharaktereSection.innerHTML = '';
            const selectedCharaktere = comicData[comicId]?.charaktere || [];

            const categories = {
                'Hauptcharaktere': 'charaktere_main',
                'Nebencharaktere': 'charaktere_secondary',
                'Andere Charaktere': 'charaktere_other_main',
                'Andere seltene Charaktere': 'charaktere_other'
            };

            const placeholderUrlUnknown = 'https://placehold.co/60x60/cccccc/333333?text=Bild\\nnicht\\ndefiniert';
            const placeholderUrlMissing = 'https://placehold.co/60x60/dc3545/ffffff?text=Bild\\nFehlt';

            for (const [title, key] of Object.entries(categories)) {
                if (charaktereData[key]) {
                    const kategorieDiv = document.createElement('div');
                    kategorieDiv.className = 'charakter-kategorie';
                    kategorieDiv.innerHTML = `<h3>${title}</h3>`;

                    const grid = document.createElement('div');
                    grid.className = 'charaktere-grid';

                    for (const [name, urls] of Object.entries(charaktereData[key])) {
                        const item = document.createElement('div');
                        item.className = 'charakter-item';
                        item.dataset.charakterName = name;

                        const img = document.createElement('img');
                        const imageUrl = urls ? urls.charaktere_pic_url : null;

                        if (imageUrl) {
                            img.src = `../${imageUrl}`;
                            img.addEventListener('error', function () {
                                this.onerror = null;
                                this.src = placeholderUrlMissing;
                            }, { once: true });
                        } else {
                            img.src = placeholderUrlUnknown;
                        }

                        img.alt = name;
                        if (selectedCharaktere.includes(name)) {
                            item.classList.add('active');
                        }

                        const nameSpan = document.createElement('span');
                        nameSpan.textContent = name;

                        item.appendChild(img);
                        item.appendChild(nameSpan);
                        grid.appendChild(item);
                    }
                    kategorieDiv.appendChild(grid);
                    modalCharaktereSection.appendChild(kategorieDiv);
                }
            }
        }


        modalCharaktereSection.addEventListener('click', (e) => {
            const item = e.target.closest('.charakter-item');
            if (!item) return;

            const charakterName = item.dataset.charakterName;
            item.classList.toggle('active');
            const isActive = item.classList.contains('active');

            if (!comicData[activeEditId].charaktere) {
                comicData[activeEditId].charaktere = [];
            }

            const charakterArray = comicData[activeEditId].charaktere;
            const index = charakterArray.indexOf(charakterName);

            if (isActive) {
                if (index === -1) {
                    charakterArray.push(charakterName);
                }
            } else {
                if (index > -1) {
                    charakterArray.splice(index, 1);
                }
            }
        });


        modalImageControls.addEventListener('click', (e) => {
            if (e.target.matches('.button-toggle')) {
                setImageView(e.target.dataset.view);
            }
        });

        saveAllBtn.addEventListener('click', async () => {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save_comic_data', comics: comicData, csrf_token: csrfToken })
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    showMessage(data.message, 'green');
                    await saveSettings();
                    updateTimestamp();
                } else { showMessage(`Fehler: ${data.message}`, 'red'); }
            } catch (error) { showMessage(`Netzwerkfehler: ${error.message}`, 'red'); }
        });

        addRowBtn.addEventListener('click', () => {
            activeEditId = 'new_entry';
            comicData['new_entry'] = {
                type: 'Comicseite',
                name: '',
                transcript: '',
                chapter: null,
                datum: '',
                url_originalbild: '',
                url_originalsketch: '',
                charaktere: [],
                sources: []
            };

            document.getElementById('modal-comic-id').value = '';
            document.getElementById('modal-comic-id').disabled = false;
            document.getElementById('modal-type').value = 'Comicseite';
            document.getElementById('modal-name').value = '';
            $('#modal-transcript').summernote('code', '');
            document.getElementById('modal-chapter').value = '';
            document.getElementById('modal-url').value = '';
            document.getElementById('modal-url-sketch').value = '';
            document.getElementById('modal-title-header').textContent = 'Neuen Eintrag erstellen';

            document.getElementById('modal-image-preview-section').style.display = 'block';
            updateImagePreviews();
            renderCharakterSelection('new_entry');
            setImageView('de');

            editModal.style.display = 'flex';
        });

        tableBody.addEventListener('click', e => {
            const editBtn = e.target.closest('.edit-row-btn');
            if (editBtn) {
                const row = editBtn.closest('tr');
                activeEditId = row.dataset.id;
                const chapter = comicData[activeEditId];
                document.getElementById('modal-comic-id').value = activeEditId;
                document.getElementById('modal-comic-id').disabled = true;
                document.getElementById('modal-type').value = chapter.type || 'Comicseite';
                document.getElementById('modal-name').value = chapter.name || '';
                $('#modal-transcript').summernote('code', chapter.transcript || '');
                document.getElementById('modal-chapter').value = chapter.chapter || '';
                document.getElementById('modal-url').value = chapter.url_originalbild || '';
                document.getElementById('modal-url-sketch').value = chapter.url_originalsketch || '';
                document.getElementById('modal-title-header').textContent = `Eintrag bearbeiten (${activeEditId})`;

                document.getElementById('modal-image-preview-section').style.display = 'block';
                updateImagePreviews();
                renderCharakterSelection(activeEditId);
                setImageView('de');

                editModal.style.display = 'flex';
            }

            const deleteBtn = e.target.closest('.delete-row-btn');
            if (deleteBtn) {
                if (confirm('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?')) {
                    const row = deleteBtn.closest('tr');
                    const idToDelete = row.dataset.id;
                    delete comicData[idToDelete];
                    allComicIds = allComicIds.filter(id => id !== idToDelete);
                    filteredComicIds = filteredComicIds.filter(id => id !== idToDelete);
                    renderTable();
                    showMessage('Eintrag zum Löschen vorgemerkt. Klicken Sie auf "Änderungen speichern".', 'orange', 10000);
                }
            }
        });

        paginationContainers.forEach(container => {
            container.addEventListener('click', (e) => {
                if (e.target.tagName === 'A' && e.target.dataset.page) {
                    e.preventDefault();
                    currentPage = parseInt(e.target.dataset.page, 10);
                    renderTable();
                }
            });
        });

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase().trim();
            clearSearchBtn.style.display = searchTerm ? 'inline-block' : 'none';

            if (!searchTerm) {
                filteredComicIds = [...allComicIds];
            } else {
                filteredComicIds = allComicIds.filter(id => {
                    const comic = comicData[id];
                    const name = comic.name ? comic.name.toLowerCase() : '';
                    return id.includes(searchTerm) || name.includes(searchTerm);
                });
            }
            currentPage = 1;
            renderTable();
        });

        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        });

        document.getElementById('modal-url').addEventListener('input', updateImagePreviewsWithDebounce);
        document.getElementById('modal-url-sketch').addEventListener('input', updateImagePreviewsWithDebounce);

        modalSaveBtn.addEventListener('click', () => {
            let idToUpdate;
            let isNewEntry = false;

            if (activeEditId && activeEditId !== 'new_entry') {
                idToUpdate = activeEditId;
            } else {
                isNewEntry = true;
                const newId = document.getElementById('modal-comic-id').value;
                if (!/^\d{8}$/.test(newId)) {
                    alert('Bitte geben Sie eine gültige ID im Format JJJJMMTT ein.');
                    return;
                }
                if (comicData[newId]) {
                    alert('Diese ID existiert bereits.');
                    return;
                }
                idToUpdate = newId;

                comicData[idToUpdate] = { ...comicData['new_entry'] };
                delete comicData['new_entry'];

                if (!allComicIds.includes(idToUpdate)) {
                    allComicIds.push(idToUpdate);
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    if (!searchTerm || idToUpdate.includes(searchTerm)) {
                        filteredComicIds.push(idToUpdate);
                    }
                }
            }

            comicData[idToUpdate].type = document.getElementById('modal-type').value;
            comicData[idToUpdate].name = document.getElementById('modal-name').value;
            comicData[idToUpdate].transcript = $('#modal-transcript').summernote('code');
            comicData[idToUpdate].chapter = document.getElementById('modal-chapter').value;
            comicData[idToUpdate].url_originalbild = document.getElementById('modal-url').value;
            comicData[idToUpdate].url_originalsketch = document.getElementById('modal-url-sketch').value;
            comicData[idToUpdate].datum = idToUpdate;

            if (isNewEntry) {
                comicData[idToUpdate].sources = ['json'];
            }

            renderTable();
            editModal.style.display = 'none';

            const updatedRow = tableBody.querySelector(`tr[data-id="${idToUpdate}"]`);
            if (updatedRow) {
                updatedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                updatedRow.classList.add('row-highlight');
                setTimeout(() => {
                    updatedRow.classList.remove('row-highlight');
                }, 8000);
            }

            activeEditId = null;
            showMessage('Änderung zwischengespeichert. Klicken Sie auf "Änderungen speichern", um sie permanent zu machen.', 'orange', 10000);
        });

        const cancelAction = () => {
            if (activeEditId === 'new_entry') {
                delete comicData['new_entry'];
            }
            editModal.style.display = 'none';
            activeEditId = null;
        };
        modalCancelBtn.addEventListener('click', cancelAction);
        modalCloseBtn.addEventListener('click', cancelAction);

        renderTable();
    });
</script>

<?php include $footerPath; ?>