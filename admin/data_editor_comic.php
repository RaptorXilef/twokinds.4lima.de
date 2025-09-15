<?php
/**
 * Administrationsseite zum Bearbeiten der comic_var.json Konfigurationsdatei.
 * V3.9: Fügt einen korrekten, datumsbasierten Cache-Buster zur gecachten
 * Original-URL hinzu und korrigiert die Placeholder-Logik.
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
function loadComicData(string $path, bool $debugMode): array
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
            if (saveComicData($comicVarJsonPath, $comicDataToSave, $debugMode)) {
                $response = ['success' => true, 'message' => 'Comic-Daten erfolgreich gespeichert!'];
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

            if ($comicIdToUpdate && $imageUrlToCache) {
                $currentCache = get_image_cache_local($imageCachePath);
                if (!isset($currentCache[$comicIdToUpdate])) {
                    $currentCache[$comicIdToUpdate] = [];
                }
                $currentCache[$comicIdToUpdate]['url_originalbild'] = $imageUrlToCache;

                if (file_put_contents($imageCachePath, json_encode($currentCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $response['success'] = true;
                    $response['message'] = "Cache für Original-URL von $comicIdToUpdate aktualisiert.";
                } else {
                    $response['message'] = "Fehler beim Speichern der Cache-Datei.";
                    http_response_code(500);
                }
            } else {
                $response['message'] = "Fehlende Daten zum Cachen der URL.";
                http_response_code(400);
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings($settingsFilePath, $debugMode);
$comicEditorSettings = $settings['data_editor_comic'];

$jsonData = loadComicData($comicVarJsonPath, $debugMode);
$imageDirs = [__DIR__ . '/../assets/comic_lowres/', __DIR__ . '/../assets/comic_hires/'];
$imageIds = getComicIdsFromImages($imageDirs, $debugMode);
$phpIds = getComicIdsFromPhpFiles($comicPhpPagesPath, $debugMode);

$allIds = array_unique(array_merge(array_keys($jsonData), $imageIds, $phpIds));
sort($allIds);

$fullComicData = [];
foreach ($allIds as $id) {
    $fullComicData[$id] = $jsonData[$id] ?? [
        'type' => 'Comicseite',
        'name' => '',
        'transcript' => '',
        'chapter' => null,
        'datum' => $id,
        'url_originalbild' => ''
    ];
    $fullComicData[$id] += ['type' => 'Comicseite', 'name' => '', 'transcript' => '', 'chapter' => null, 'datum' => $id, 'url_originalbild' => ''];

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

        <div id="message-box" class="hidden-by-default"></div>

        <div class="table-controls">
            <div class="marker-legend">
                <strong>Quellen:</strong>
                <span class="source-marker source-json" title="Eintrag existiert in comic_var.json">JSON</span>
                <span class="source-marker source-image" title="Mindestens eine Bilddatei existiert lokal">Bild</span>
                <span class="source-marker source-php"
                    title="Eine PHP-Datei existiert für diese Seite in /comic/">PHP</span>
                <span class="source-marker source-url" title="Ein Originalbild ist via URL verknüpft">URL</span>
            </div>
            <div class="marker-legend">
                <strong>Status:</strong>
                <span class="source-marker status-json present"
                    title="JSON-Daten vollständig (Name, Transkript, etc.)">J</span>
                <span class="source-marker status-lowres" title="Low-Res Bild">L</span>
                <span class="source-marker status-hires" title="High-Res Bild">H</span>
                <span class="source-marker status-php present" title="PHP-Datei">P</span>
                <span class="source-marker status-socialmedia" title="Social Media Bild">S</span>
                <span class="source-marker status-thumbnails" title="Thumbnail">T</span>
                <span class="source-marker source-url" title="URL zum Originalbild">U</span>
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

        <div id="modal-image-preview-section">
            <div id="modal-image-controls" class="button-toggle-group">
                <button class="button-toggle active" data-view="de">Deutsch</button>
                <button class="button-toggle" data-view="en">Englisch</button>
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
            </div>
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
        gap: 10px;
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
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        let comicData = <?php echo json_encode($fullComicData, JSON_UNESCAPED_SLASHES); ?>;
        const allComicIds = Object.keys(comicData);
        let cachedImages = <?php echo json_encode($cachedImagesForJs, JSON_UNESCAPED_SLASHES); ?>;

        const tableBody = document.querySelector('#comic-table tbody');
        const saveAllBtn = document.getElementById('save-all-btn');
        const addRowBtn = document.getElementById('add-row-btn');
        const messageBox = document.getElementById('message-box');
        const saveConfirmationBox = document.getElementById('save-confirmation-box');
        const lastRunContainer = document.getElementById('last-run-container');
        const paginationContainer = document.querySelector('.pagination');

        const editModal = document.getElementById('edit-modal');
        const modalCloseBtn = editModal.querySelector('.close-button');
        const modalSaveBtn = document.getElementById('modal-save-btn');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        const modalImageControls = document.getElementById('modal-image-controls');
        const modalPreviewDe = document.getElementById('modal-preview-de');
        const modalPreviewEn = document.getElementById('modal-preview-en');
        let activeEditId = null;

        const ITEMS_PER_PAGE = <?php echo COMIC_PAGES_PER_PAGE; ?>;
        let currentPage = 1;

        $('#modal-transcript').summernote({
            placeholder: "Transkript hier eingeben...", tabsize: 2, height: 200,
            toolbar: [['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link']], ['view', ['codeview']]],
            callbacks: {
                onPaste: function (e) {
                    var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
                    var pastedData = clipboardData.getData('text/html');
                    if (pastedData) {
                        e.preventDefault();
                        let cleanedData = pastedData.replace(/<!--[\s\S]*?-->/g, '');
                            cleanedData = cleanedData.replace(/<(\w+)([^>]*)>/g, (match, tagName, attrs) => {
                                let preservedAttrs = '';
                                if (tagName.toLowerCase() === 'a') {
                                    const hrefMatch = attrs.match(/href="([^"]*)"/i);
                                    if (hrefMatch) preservedAttrs = ` href="${hrefMatch[1]}" target="_blank"`;
                                }
                                return `<${tagName}${preservedAttrs}>`;
                            });
                        cleanedData = cleanedData.replace(/<b>/gi, '<strong>').replace(/<\/b>/gi, '</strong>');
                        cleanedData = cleanedData.replace(/<i>/gi, '<em>').replace(/<\/i>/gi, '</em>');
                        $('#modal-transcript').summernote('pasteHTML', cleanedData);
                    }
                }
            }
        });

        const renderTable = () => {
            tableBody.innerHTML = '';
            const start = (currentPage - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const paginatedIds = allComicIds.slice(start, end);

            paginatedIds.forEach(id => {
                const chapter = comicData[id];
                const row = document.createElement('tr');
                row.dataset.id = id;

                const descPreview = new DOMParser().parseFromString(chapter.transcript || '', 'text/html').body;
                descPreview.querySelectorAll('script, style').forEach(el => el.remove());

                const isNameMissing = !chapter.name || chapter.name.trim() === '';
                const isTranscriptMissing = !chapter.transcript || chapter.transcript.trim() === '';
                const isChapterMissing = chapter.chapter === null || chapter.chapter === '';
                const isUrlMissing = !chapter.url_originalbild || chapter.url_originalbild.trim() === '';

                const hasJson = chapter.sources.includes('json');
                const hasPhp = chapter.sources.includes('php');
                const hasUrl = chapter.sources.includes('url');
                const hasLowres = cachedImages[id]?.lowres;
                const hasHires = cachedImages[id]?.hires;
                const hasThumb = cachedImages[id]?.thumbnails;
                const hasSocial = cachedImages[id]?.socialmedia;
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
                    ${isChapterMissing ? '' : `<strong>Kap. ${chapter.chapter}:</strong><br>`}
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
            const totalPages = Math.ceil(allComicIds.length / ITEMS_PER_PAGE);
            paginationContainer.innerHTML = '';
            if (totalPages <= 1) return;
            if (currentPage > 1) paginationContainer.innerHTML += `<a data-page="${currentPage - 1}">&laquo;</a>`;
            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) paginationContainer.innerHTML += `<span class="current-page">${i}</span>`;
                else paginationContainer.innerHTML += `<a data-page="${i}">${i}</a>`;
            }
            if (currentPage < totalPages) paginationContainer.innerHTML += `<a data-page="${currentPage + 1}">&raquo;</a>`;
        };

        function showMessage(message, type, duration = 5000, container = messageBox) {
            container.textContent = message;
            container.className = `status-message status-${type}`;
            container.style.display = 'block';
            if (duration > 0) {
                setTimeout(() => { container.style.display = 'none'; }, duration);
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

        async function updateImagePreviews(comicId, originalFilename) {
            const deImg = modalPreviewDe.querySelector('img');
            const enImg = modalPreviewEn.querySelector('img');
            const placeholderSrc = (cachedImages['placeholder'] && cachedImages['placeholder'].lowres) ? `../${cachedImages['placeholder'].lowres}` : '../assets/comic_thumbnails/placeholder.jpg';
            deImg.src = (cachedImages[comicId] && cachedImages[comicId].lowres) ? `../${cachedImages[comicId].lowres}` : placeholderSrc;

            if (!originalFilename) {
                enImg.src = placeholderSrc;
                return;
            }

            enImg.src = '../assets/icons/loading.webp';

            if (cachedImages[comicId] && cachedImages[comicId].url_originalbild) {
                enImg.src = cachedImages[comicId].url_originalbild;
                enImg.onerror = () => {
                    delete cachedImages[comicId].url_originalbild;
                    findAndCacheEnglishUrl(comicId, originalFilename, enImg, placeholderSrc);
                };
                return;
            }

            findAndCacheEnglishUrl(comicId, originalFilename, enImg, placeholderSrc);
        }

        function findAndCacheEnglishUrl(comicId, originalFilename, enImgElement, placeholderSrc) {
            const originalImageUrlBase = 'https://cdn.twokinds.keenspot.com/comics/';
            const imageExtensions = ['png', 'jpg', 'gif', 'jpeg', 'webp'];
            let currentExtIndex = 0;

            function tryNextExtension() {
                if (currentExtIndex >= imageExtensions.length) {
                    enImgElement.src = placeholderSrc;
                    enImgElement.onerror = null;
                    return;
                }
                const testUrl = originalImageUrlBase + originalFilename + '.' + imageExtensions[currentExtIndex];
                enImgElement.src = testUrl;
                currentExtIndex++;
            }

            enImgElement.onload = async () => {
                const foundUrl = new URL(enImgElement.src);
                const baseUrlWithoutQuery = `${foundUrl.origin}${foundUrl.pathname}`;

                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const cacheBuster = `?c=${year}${month}${day}`;

                const urlWithBuster = baseUrlWithoutQuery + cacheBuster;

                if (!cachedImages[comicId]) cachedImages[comicId] = {};
                cachedImages[comicId].url_originalbild = urlWithBuster;

                try {
                    await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'update_original_url_cache',
                            comic_id: comicId,
                            image_url: urlWithBuster,
                            csrf_token: csrfToken
                        })
                    });
                } catch (error) {
                    console.error('Fehler beim Cachen der Original-URL:', error);
                }
            };
            enImgElement.onerror = tryNextExtension;
            tryNextExtension();
        }

        function setImageView(view) {
            modalImageControls.querySelectorAll('.button-toggle').forEach(btn => btn.classList.remove('active'));
            modalImageControls.querySelector(`[data-view="${view}"]`).classList.add('active');

            modalPreviewDe.style.display = (view === 'de' || view === 'both') ? 'flex' : 'none';
            modalPreviewEn.style.display = (view === 'en' || view === 'both') ? 'flex' : 'none';
        }

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
                    showMessage(data.message, 'green', 5000, saveConfirmationBox);
                    messageBox.style.display = 'none';
                    await saveSettings();
                    updateTimestamp();
                } else { showMessage(`Fehler: ${data.message}`, 'red', 5000, saveConfirmationBox); }
            } catch (error) { showMessage(`Netzwerkfehler: ${error.message}`, 'red', 5000, saveConfirmationBox); }
        });

        addRowBtn.addEventListener('click', () => {
            activeEditId = null;
            document.getElementById('modal-comic-id').value = '';
            document.getElementById('modal-comic-id').disabled = false;
            document.getElementById('modal-type').value = 'Comicseite';
            document.getElementById('modal-name').value = '';
            $('#modal-transcript').summernote('code', '');
            document.getElementById('modal-chapter').value = '';
            document.getElementById('modal-url').value = '';
            document.getElementById('modal-title-header').textContent = 'Neuen Eintrag erstellen';

            document.getElementById('modal-image-preview-section').style.display = 'none';

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
                document.getElementById('modal-type').value = chapter.type;
                document.getElementById('modal-name').value = chapter.name;
                $('#modal-transcript').summernote('code', chapter.transcript);
                document.getElementById('modal-chapter').value = chapter.chapter;
                document.getElementById('modal-url').value = chapter.url_originalbild;
                document.getElementById('modal-title-header').textContent = `Eintrag bearbeiten (${activeEditId})`;

                document.getElementById('modal-image-preview-section').style.display = 'block';
                updateImagePreviews(activeEditId, chapter.url_originalbild);
                setImageView('de');

                editModal.style.display = 'flex';
            }

            const deleteBtn = e.target.closest('.delete-row-btn');
            if (deleteBtn) {
                if (confirm('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?')) {
                    const row = deleteBtn.closest('tr');
                    const idToDelete = row.dataset.id;
                    delete comicData[idToDelete];
                    const index = allComicIds.indexOf(idToDelete);
                    if (index > -1) {
                        allComicIds.splice(index, 1);
                    }
                    renderTable();
                    showMessage('Eintrag zum Löschen vorgemerkt. Klicken Sie auf "Änderungen speichern".', 'orange', 10000);
                }
            }
        });

        paginationContainer.addEventListener('click', (e) => {
            if (e.target.tagName === 'A' && e.target.dataset.page) {
                e.preventDefault();
                currentPage = parseInt(e.target.dataset.page, 10);
                renderTable();
            }
        });

        modalSaveBtn.addEventListener('click', () => {
            let idToUpdate;
            if (activeEditId) {
                idToUpdate = activeEditId;
            } else {
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
                comicData[idToUpdate] = { sources: ['json'] };
                if (!allComicIds.includes(idToUpdate)) {
                    allComicIds.push(idToUpdate);
                    allComicIds.sort();
                }
            }

            comicData[idToUpdate].type = document.getElementById('modal-type').value;
            comicData[idToUpdate].name = document.getElementById('modal-name').value;
            comicData[idToUpdate].transcript = $('#modal-transcript').summernote('code');
            comicData[idToUpdate].chapter = document.getElementById('modal-chapter').value;
            comicData[idToUpdate].url_originalbild = document.getElementById('modal-url').value;
            comicData[idToUpdate].datum = idToUpdate;

            renderTable();
            editModal.style.display = 'none';
            activeEditId = null;
            showMessage('Änderung zwischengespeichert. Klicken Sie auf "Änderungen speichern", um sie permanent zu machen.', 'orange', 10000);
        });

        modalCancelBtn.addEventListener('click', () => { editModal.style.display = 'none'; activeEditId = null; });
        modalCloseBtn.addEventListener('click', () => modalCancelBtn.click());

        renderTable();
    });
</script>

<?php include $footerPath; ?>