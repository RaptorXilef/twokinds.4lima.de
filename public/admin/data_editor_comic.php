<?php

/**
 * Administrationsseite zum Bearbeiten der comic_var.json Konfigurationsdatei.
 *
 * @file      ROOT/public/admin/data_editor_comic.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 3.0.0 - 4.0.0
 * - Core & Datenstruktur:
 *  - Umstellung auf Charakter-ID-System (comic_var.json Schema v2) und neue Gruppenstruktur.
 *  - Zentrale Pfad-Konstanten, dynamischer Path-Helper und CSRF-Fixes (FormData).
 * - Dateiverwaltung:
 *  - Automatische Erstellung/Löschung und Inhalt-Korrektur (require_once) von PHP-Dateien im /comic/-Ordner.
 * - UI & UX:
 *  - Aktionsbuttons und Feedback-Box nun doppelt (oben/unten) für bessere Erreichbarkeit.
 *  - Pagination erweitert (Vor/Zurück) und Theme-Styling (Hell/Dunkel) korrigiert.
 *  - Auto-Scroll und Highlight der Zeile nach Bearbeitung; 'C'-Tag für Zuweisungsstatus.
 *  - Robustes, CSP-konformes Fallback für Charakterbilder und Text-Korrekturen (Zeilenumbruch).
 * - Fixes:
 *  - Diverse Fehlerbehebungen (Löschen, "Neu"-Dialog, JS-Optimierungen, Bildanzeige).
 *
 * @since     5.0.0
 * - style(UI): Modal-Layout überarbeitet; Buttons sind nun am unteren Rand fixiert ("schwebend"), Inhalt scrollbar.
 * - feat(UI): Paginierung-Info und konfigurierbare Textkürzung (TRUNCATE_COMIC_DESCRIPTION) hinzugefügt.
 * - refactor(Config): Nutzung spezifischer Konstanten (ENTRIES_PER_PAGE_COMIC, TRUNCATE_COMIC_DESCRIPTION).
 * - refactor(CSS): Bereinigung verbliebener Inline-Styles.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json' für Timestamps.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> data_editor_comic).
 * - fix(JS): saveSettings nutzt nun FormData statt JSON, damit PHP $_POST korrekt lesen kann.
 * - fix(UI): Anzeige für "Noch keine Speicherung" hinzugefügt.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
// Neuer Pfad im Unterordner 'admin'
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// === VARIABLEN ===
if (!defined('ENTRIES_PER_PAGE_COMIC')) {
    define('ENTRIES_PER_PAGE_COMIC', 50);
}

// Konfiguration für Textkürzung (Standard: False = Alles anzeigen)
if (!defined('TRUNCATE_COMIC_DESCRIPTION')) {
    define('TRUNCATE_COMIC_DESCRIPTION', false);
}
// Übergabe an JS
$truncateTextJs = TRUNCATE_COMIC_DESCRIPTION ? 'true' : 'false';


// --- HILFSFUNKTIONEN ---
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
    $fromArr = explode('/', rtrim($from, '/'));
    $toArr = explode('/', rtrim($to, '/'));
    $toFilename = array_pop($toArr);
    while (count($fromArr) && count($toArr) && ($fromArr[0] == $toArr[0])) {
        array_shift($fromArr);
        array_shift($toArr);
    }
    return str_repeat('../', count($fromArr)) . implode('/', $toArr) . '/' . $toFilename;
}

function loadGeneratorSettings(string $filePath, string $username, bool $debugMode): array
{
    $defaults = [
        'last_run_timestamp' => null
    ];

    if (!file_exists($filePath)) {
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, json_encode(['users' => []], JSON_PRETTY_PRINT));
        return $defaults;
    }

    $content = file_get_contents($filePath);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        if ($debugMode) {
            error_log("[Comic Editor] Config JSON korrupt oder leer. Lade Defaults.");
        }
        return $defaults;
    }

    $userSettings = $data['users'][$username]['data_editor_comic'] ?? [];
    return array_replace_recursive($defaults, $userSettings);
}

function saveGeneratorSettings(string $filePath, string $username, array $settings, bool $debugMode): bool
{
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $data = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data = $decoded;
        }
    }

    if (!isset($data['users'])) {
        $data['users'] = [];
    }
    if (!isset($data['users'][$username])) {
        $data['users'][$username] = [];
    }

    $currentGeneratorSettings = $data['users'][$username]['data_editor_comic'] ?? [];
    $data['users'][$username]['data_editor_comic'] = array_replace_recursive($currentGeneratorSettings, $settings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function loadJsonData(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function loadCharacterDataWithSchema(string $path): array
{
    $charData = loadJsonData($path, false);
    if (!isset($charData['schema_version']) || $charData['schema_version'] < 2) {
        return ['charactersById' => [], 'groupsWithChars' => [], 'schema_version' => 1];
    }
    return ['charactersById' => $charData['characters'] ?? [], 'groupsWithChars' => $charData['groups'] ?? [], 'schema_version' => $charData['schema_version']];
}

function saveComicData(string $path, array $comics, bool $debugMode): bool
{
    ksort($comics);
    return file_put_contents($path, json_encode(['schema_version' => 2, 'comics' => $comics], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function getComicIdsFromImages(array $dirs, bool $debugMode): array
{
    $imageIds = [];
    $exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                $info = pathinfo($file);
                if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), $exts)) {
                    $imageIds[$info['filename']] = true;
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
        foreach (scandir($pagesDir) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && preg_match('/^\d{8}$/', pathinfo($file, PATHINFO_FILENAME))) {
                $phpIds[pathinfo($file, PATHINFO_FILENAME)] = true;
            }
        }
    }
    return array_keys($phpIds);
}

function get_image_cache_local(string $cachePath): array
{
    if (!file_exists($cachePath)) {
        return [];
    }
    $data = json_decode(file_get_contents($cachePath), true);
    return is_array($data) ? $data : [];
}

// --- AJAX Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Unbekannte Aktion.'];
    $comicVarJsonPath = Path::getDataPath('comic_var.json');
    $comicImageCacheJsonPath = Path::getCachePath('comic_image_cache.json');
    // Nutze globale Variable $configPath
    $generatorSettingsJsonPath = $configPath;

    switch ($action) {
        case 'save_comic_data':
            $data = json_decode($_POST['comics'] ?? '[]', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response['message'] = 'Ungültiges JSON.';
                http_response_code(400);
                break;
            }
            $decodedCurrent = loadJsonData($comicVarJsonPath, $debugMode);
            $currentData = (isset($decodedCurrent['schema_version']) && $decodedCurrent['schema_version'] >= 2) ? ($decodedCurrent['comics'] ?? []) : $decodedCurrent;

            $newIds = array_keys(array_diff_key($data, $currentData));
            $delIds = array_keys(array_diff_key($currentData, $data));
            $created = 0;
            $deleted = 0;

            foreach ($newIds as $id) {
                $f = DIRECTORY_PUBLIC_COMIC . DIRECTORY_SEPARATOR . $id . '.php';
                if (!file_exists($f)) {
                    $rel = getRelativePath(DIRECTORY_PUBLIC_COMIC, DIRECTORY_PRIVATE_RENDERER . DIRECTORY_SEPARATOR . 'renderer_comic_page.php');
                    if (file_put_contents($f, "<?php require_once __DIR__ . '/" . $rel . "'; ?>") !== false) {
                        $created++;
                    }
                }
            }
            foreach ($delIds as $id) {
                $f = DIRECTORY_PUBLIC_COMIC . DIRECTORY_SEPARATOR . $id . '.php';
                if (file_exists($f) && unlink($f)) {
                    $deleted++;
                }
            }

            if (saveComicData($comicVarJsonPath, $data, $debugMode)) {
                $response = [
                    'success' => true,
                    'message' => "Comic-Daten Gespeichert! $created PHP-Datei(en) erstellt, $deleted PHP-Datei(en) gelöscht."
                ];
            } else {
                $response['message'] = 'Fehler beim Speichern.';
                http_response_code(500);
            }
            break;
        case 'save_settings':
            $s = loadGeneratorSettings($generatorSettingsJsonPath, $currentUser, $debugMode);
            $s['last_run_timestamp'] = time();
            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentUser, $s, $debugMode)) {
                $response['success'] = true;
            } else {
                $response['message'] = 'Fehler beim Speichern der Einstellungen.';
            }
            break;
        case 'update_original_url_cache':
            $id = $_POST['comic_id'] ?? null;
            $url = $_POST['image_url'] ?? null;
            $key = $_POST['cache_key'] ?? 'url_originalbild';
            if ($id && $url && in_array($key, ['url_originalbild', 'url_originalsketch'])) {
                $cache = get_image_cache_local($comicImageCacheJsonPath);
                if (!isset($cache[$id])) {
                    $cache[$id] = [];
                }
                $cache[$id][$key] = $url;
                if (file_put_contents($comicImageCacheJsonPath, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    $response = ['success' => true, 'message' => 'Cache aktualisiert.'];
                } else {
                    $response['message'] = 'Fehler beim Cache-Speichern.';
                    http_response_code(500);
                }
            } else {
                $response['message'] = 'Ungültige Daten.';
                http_response_code(400);
            }
            break;
    }
    echo json_encode($response);
    exit;
}

// === VIEW DATEN LADEN ===
$comicEditorSettings = loadGeneratorSettings($configPath, $currentUser, $debugMode);
$decodedData = loadJsonData(Path::getDataPath('comic_var.json'), $debugMode);
$jsonData = (isset($decodedData['schema_version']) && $decodedData['schema_version'] >= 2) ? ($decodedData['comics'] ?? []) : $decodedData;
$charaktereData = loadCharacterDataWithSchema(Path::getDataPath('charaktere.json'));
$imageDirs = [DIRECTORY_PUBLIC_IMG_COMIC_LOWRES, DIRECTORY_PUBLIC_IMG_COMIC_HIRES];
$imageIds = getComicIdsFromImages($imageDirs, $debugMode);
$phpIds = getComicIdsFromPhpFiles(DIRECTORY_PUBLIC_COMIC, $debugMode);
$allIds = array_unique(array_merge(array_keys($jsonData), $imageIds, $phpIds));
rsort($allIds);

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
    $fullComicData[$id] = array_merge($defaults, $jsonData[$id] ?? []);
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
    if (!empty($fullComicData[$id]['url_originalbild'])) {
        $sources[] = 'url';
    }
    $fullComicData[$id]['sources'] = $sources;
}
$cachedImagesForJs = get_image_cache_local(Path::getCachePath('comic_image_cache.json'));
$placeholderUrl = Url::getImgLayoutThumbnailsUrl('placeholder.jpg');
$loadingIconUrl = Url::getImgAdminUiUrl('loading.webp');
$charProfileUrlBase = Url::getImgCharactersProfilesUrl('');

$pageTitle = 'Adminbereich - Comic Daten Editor';
$pageHeader = 'Comic Daten Editor';
$robotsContent = 'noindex, nofollow';
$additionalScripts = <<<HTML
    <script nonce="{$nonce}" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script nonce="{$nonce}" src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
HTML;

require_once Path::getPartialTemplatePath('header.php');
?>

    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if (!empty($comicEditorSettings['last_run_timestamp'])) : ?>
                    <p class="status-message status-info">Letzte Speicherung am
                        <?php echo date('d.m.Y \u\m H:i:s', $comicEditorSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Speicherung erfasst.</p>
                <?php endif; ?>
            </div>
            <h2>Comic Daten Editor</h2>
            <p>Verwalte hier die Metadaten für jede Comic-Seite. Fehlende Einträge für existierende Bilder werden
                automatisch hinzugefügt.</p>
        </div>

        <div class="filter-form">
            <fieldset>
                <legend>Filter</legend>
                <div class="filter-controls center-filter">
                    <div class="search-wrapper">
                        <input type="text" id="search-input" placeholder="Nach ID oder Name suchen...">
                        <!-- FIX: Klasse .hidden-by-default statt inline style -->
                        <button id="clear-search-btn" type="button" title="Suche leeren" class="hidden-by-default">&times;</button>
                    </div>
                </div>
            </fieldset>
        </div>

        <!-- FIX: Inline Styles entfernt (wird durch SCSS geregelt) -->
        <div class="pagination"></div>

        <!-- FIX: Paginierung Info mit neuer Klasse -->
        <div class="marker-legend legend-pagination-info">
            <small>Zeigt <?php echo ENTRIES_PER_PAGE_COMIC; ?> Einträge pro Seite.</small>
        </div>

        <div class="table-controls actions-bar">
            <div class="top-actions">
                <button id="add-row-btn-top" class="button"><i class="fas fa-plus-circle"></i> Neuer Eintrag</button>
                <button id="save-all-btn-top" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
            </div>

            <div class="marker-legend-group">
                <div class="marker-legend">
                    <strong>Quellen:</strong>
                    <span class="source-marker source-json" title="Eintrag existiert in comic_var.json">JSON</span>
                    <span class="source-marker source-image" title="Mindestens eine Bilddatei existiert lokal">BILD</span>
                    <span class="source-marker source-php" title="Eine PHP-Datei existiert">PHP</span>
                    <span class="source-marker source-url" title="Ein Originalbild ist via URL verknüpft">URL</span>
                </div>
                <div class="marker-legend">
                    <strong>Status:</strong>
                    <span class="status-icon status-json present" title="JSON vollständig">J</span>
                    <span class="status-icon status-lowres present" title="Low-Res Bild">L</span>
                    <span class="status-icon status-hires present" title="High-Res Bild">H</span>
                    <span class="status-icon status-php present" title="PHP-Datei">P</span>
                    <span class="status-icon status-socialmedia present" title="Social Media">S</span>
                    <span class="status-icon status-thumbnails present" title="Thumbnail">T</span>
                    <span class="status-icon status-url present" title="URL">U</span>
                    <span class="status-icon status-charaktere present" title="Charaktere">C</span>
                </div>
            </div>
        </div>

        <div id="message-box-top" class="hidden-by-default"></div>

        <div class="sitemap-table-container">
            <table class="admin-table comic-editor-table" id="comic-table">
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

<div id="edit-modal" class="modal hidden-by-default">
    <div class="modal-content modal-advanced-layout">
        <div class="modal-header-wrapper">
            <h2 id="modal-title-header">Eintrag bearbeiten</h2>
            <span class="close-button modal-close">&times;</span>
        </div>
        <div class="modal-scroll-content">
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

            <div id="modal-image-preview-section" class="image-preview-section">
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
            </div>
        </div>
        <div class="modal-footer-actions">
            <div class="modal-buttons">
                <button id="modal-save-btn" class="button button-green">Übernehmen</button>
                <button id="modal-cancel-btn" class="button delete">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        // Daten
        let comicData = <?php echo json_encode($fullComicData, JSON_UNESCAPED_SLASHES); ?>;
        let allComicIds = Object.keys(comicData);
        let filteredComicIds = [...allComicIds];
        let cachedImages = <?php echo json_encode($cachedImagesForJs, JSON_UNESCAPED_SLASHES); ?>;
        const charaktereInfo = <?php echo json_encode($charaktereData, JSON_UNESCAPED_SLASHES); ?>;

        // Konstanten
        const TRUNCATE_TEXT = <?php echo $truncateTextJs; ?>;
        const allCharactersData = charaktereInfo.charactersById;
        const characterGroups = charaktereInfo.groupsWithChars;
        const baseUrl = '<?php echo DIRECTORY_PUBLIC_URL; ?>';
        const placeholderUrl = '<?php echo $placeholderUrl; ?>';
        const loadingIconUrl = '<?php echo $loadingIconUrl; ?>';
        const charProfileUrlBase = '<?php echo $charProfileUrlBase; ?>';


        const tableBody = document.querySelector('#comic-table tbody');

        const saveAllBtn = document.getElementById('save-all-btn'); // Unten
        const addRowBtn = document.getElementById('add-row-btn'); // Unten
        const saveAllBtnTop = document.getElementById('save-all-btn-top'); // Oben
        const addRowBtnTop = document.getElementById('add-row-btn-top'); // Oben

        const messageBox = document.getElementById('message-box'); // Unten
        const messageBoxTop = document.getElementById('message-box-top'); // Oben

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

        const ITEMS_PER_PAGE = <?php echo ENTRIES_PER_PAGE_COMIC; ?>;
        let currentPage = 1;

        if (charaktereInfo.schema_version < 2) {
            showMessage("Fehler: Veraltetes `charaktere.json`-Format. Bitte migrieren.", 'red', 0);
            return;
        }

        $('#modal-transcript').summernote({
            placeholder: "Transkript hier eingeben...",
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ],
        });

        const renderTable = () => {
            filteredComicIds.sort().reverse(); // Sortiert absteigend (neueste zuerst)
            tableBody.innerHTML = '';
            const start = (currentPage - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;
            const paginatedIds = filteredComicIds.slice(start, end);

            paginatedIds.forEach(id => {
                const chapter = comicData[id];
                if (!chapter) return;
                const row = document.createElement('tr');
                row.dataset.id = id;

                // --- TRANSCRIPT RENDERING LOGIC ---
                // Original Logik: Volltext
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = chapter.transcript || '';
                // Sicherheit: Scripts/Styles entfernen
                tempDiv.querySelectorAll('script, style').forEach(el => el.remove());

                let displayContent = '';

                if (TRUNCATE_TEXT) {
                    // Falls Kürzung aktiv: Nur Text extrahieren und abschneiden
                    let text = tempDiv.textContent || tempDiv.innerText || '';
                    if (text.length > 100) {
                        text = text.substring(0, 100) + '...';
                    }
                    displayContent = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
                } else {
                    // Falls Volltext: Links sicher machen (target blank)
                    tempDiv.querySelectorAll('a').forEach(a => {
                        a.setAttribute('target', '_blank');
                        a.setAttribute('rel', 'noopener noreferrer');
                    });
                    displayContent = tempDiv.innerHTML;
                }

                if (!displayContent || displayContent.trim() === '') {
                    displayContent = '<em>Kein Transkript</em>';
                }
                // ----------------------------------

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
                const thumbnailPath = (cachedImages[id] && cachedImages[id].thumbnails) ? `${baseUrl}/${cachedImages[id].thumbnails}` : (cachedImages['placeholder'] && cachedImages['placeholder'].lowres ? `${baseUrl}/${cachedImages['placeholder'].lowres}` : placeholderUrl);

                row.innerHTML = `
                <td>
                    ${id}
                    <div class="source-markers"><br>
                        ${(chapter.sources || []).map(s => `<span class="source-marker source-${s.toLowerCase()}" title="Quelle: ${s}">${s.toUpperCase()}</span>`).join('')}
                    </div>
                </td>
                <td>
                    <img src="${thumbnailPath}" class="comic-thumbnail" alt="Vorschau" onerror="this.src='${placeholderUrl}'; this.onerror=null;">
                </td>
                <td class="${isNameMissing ? 'missing-info' : ''}">
                    <span class="comic-type-display">${chapter.type || ''}</span><br>
                    ${(isChapterMissing || chapter.chapter === null) ? '' : `<strong>Kap. ${chapter.chapter}:</strong><br>`}
                    ${chapter.name || '<em>Kein Name</em>'}
                </td>
                <td class="${isTranscriptMissing ? 'missing-info' : ''}">
                    <div class="description-preview">${displayContent}</div>
                </td>
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
                <td class="actions-cell">
                    <button class="button edit-row-btn"><i class="fas fa-edit"></i></button>
                    <button class="button delete-row-btn"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
                tableBody.appendChild(row);
            });
            renderPagination();
        };

        function showMessage(message, type, duration = 5000) {
            const boxes = [messageBox, messageBoxTop];
            boxes.forEach(box => {
                if (!box) return;
                box.textContent = message;
                box.className = `status-message status-${type}`;
                box.style.display = 'block';
            });
            if (duration > 0) {
                setTimeout(() => {
                    boxes.forEach(box => {
                        if (box) box.style.display = 'none';
                    });
                }, duration);
            }
        }

        function updateTimestamp() {
            const now = new Date();
            const date = now.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            const time = now.toLocaleTimeString('de-DE');
            const newStatusText = `Letzte Speicherung am ${date} um ${time} Uhr.`;
            let pElement = lastRunContainer.querySelector('.status-message');
            if (!pElement) {
                pElement = document.createElement('p');
                pElement.className = 'status-message status-info';
                lastRunContainer.innerHTML = ''; // Container leeren
                lastRunContainer.appendChild(pElement);
            }
            pElement.innerHTML = newStatusText;
        }

        async function saveSettings() {
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('csrf_token', csrfToken);

            await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
        }

        const renderPagination = () => {
            const totalPages = Math.ceil(filteredComicIds.length / ITEMS_PER_PAGE);
            paginationContainers.forEach(container => {
                container.innerHTML = '';
                if (totalPages <= 1) return;
                let htmlParts = [];
                if (currentPage > 1) {
                    htmlParts.push(`<a data-page="1">&laquo;</a>`);
                    htmlParts.push(`<a data-page="${currentPage - 1}">&lsaquo;</a>`);
                }
                let startPage = Math.max(1, currentPage - 4);
                let endPage = Math.min(totalPages, currentPage + 4);
                if (startPage > 1) {
                    htmlParts.push(`<a data-page="1">1</a>`);
                    if (startPage > 2) htmlParts.push(`<span>...</span>`);
                }
                for (let i = startPage; i <= endPage; i++) {
                    if (i === currentPage) htmlParts.push(`<span class="current-page">${i}</span>`);
                    else htmlParts.push(`<a data-page="${i}">${i}</a>`);
                }
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) htmlParts.push(`<span>...</span>`);
                    htmlParts.push(`<a data-page="${totalPages}">${totalPages}</a>`);
                }
                if (currentPage < totalPages) {
                    htmlParts.push(`<a data-page="${currentPage + 1}">&rsaquo;</a>`);
                    htmlParts.push(`<a data-page="${totalPages}">&raquo;</a>`);
                }
                container.innerHTML = htmlParts.join('');
            });
        };

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase().trim();
            // FIX: Inline style durch Klassen-Toggle ersetzen
            if (searchTerm) {
                clearSearchBtn.classList.remove('hidden-by-default');
                clearSearchBtn.style.display = 'inline-block'; // Temporärer override für inline-block, da hidden-by-default display:none hat
            } else {
                clearSearchBtn.style.display = 'none';
            }

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
                comicData[idToUpdate] = {
                    ...comicData['new_entry']
                };
                delete comicData['new_entry'];
                if (!allComicIds.includes(idToUpdate)) {
                    allComicIds.push(idToUpdate);
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    if (!searchTerm || idToUpdate.includes(searchTerm)) filteredComicIds.push(idToUpdate);
                }
            }
            comicData[idToUpdate].type = document.getElementById('modal-type').value;
            comicData[idToUpdate].name = document.getElementById('modal-name').value;
            comicData[idToUpdate].transcript = $('#modal-transcript').summernote('code');
            comicData[idToUpdate].chapter = document.getElementById('modal-chapter').value;
            comicData[idToUpdate].url_originalbild = document.getElementById('modal-url').value;
            comicData[idToUpdate].url_originalsketch = document.getElementById('modal-url-sketch').value;
            comicData[idToUpdate].datum = idToUpdate;
            if (isNewEntry) comicData[idToUpdate].sources = ['json'];
            renderTable();
            editModal.style.display = 'none';
            const updatedRow = tableBody.querySelector(`tr[data-id="${idToUpdate}"]`);
            if (updatedRow) {
                updatedRow.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                updatedRow.classList.add('row-highlight');
                setTimeout(() => {
                    updatedRow.classList.remove('row-highlight');
                }, 8000);
            }
            activeEditId = null;
            showMessage('Änderung zwischengespeichert. Klicken Sie auf "Änderungen speichern", um sie permanent zu machen.', 'orange', 10000);
        });

        const cancelAction = () => {
            if (activeEditId === 'new_entry') delete comicData['new_entry'];
            editModal.style.display = 'none';
            activeEditId = null;
        };
        modalCancelBtn.addEventListener('click', cancelAction);
        modalCloseBtn.addEventListener('click', cancelAction);

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
            const placeholderSrc = (cachedImages['placeholder'] && cachedImages['placeholder'].lowres) ? `${baseUrl}/${cachedImages['placeholder'].lowres}` : placeholderUrl;

            if (activeEditId === 'new_entry') {
                deImg.src = placeholderSrc;
                enImg.src = placeholderSrc;
                sketchImg.src = placeholderSrc;
                return;
            }

            deImg.src = (cachedImages[activeEditId] && cachedImages[activeEditId].lowres) ? `${baseUrl}/${cachedImages[activeEditId].lowres}` : placeholderSrc;

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
            imgElement.src = loadingIconUrl;
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
                    const formData = new FormData();
                    formData.append('action', 'update_original_url_cache');
                    formData.append('comic_id', comicId);
                    formData.append('image_url', urlWithBuster);
                    formData.append('cache_key', cacheKey);
                    formData.append('csrf_token', csrfToken);
                    await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
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

        function renderCharakterSelection(comicId) {
            modalCharaktereSection.innerHTML = '';
            const selectedCharIds = comicData[comicId]?.charaktere || [];
            const placeholderUrlUnknown = 'https://placehold.co/60x60/cccccc/333333?text=?';
            const placeholderUrlMissing = 'https://placehold.co/60x60/dc3545/ffffff?text=Fehlt';

            Object.keys(characterGroups).forEach(groupName => {
                const kategorieDiv = document.createElement('div');
                kategorieDiv.className = 'charakter-kategorie';
                kategorieDiv.innerHTML = `<h3>${groupName}</h3>`;

                const grid = document.createElement('div');
                grid.className = 'charaktere-grid';

                characterGroups[groupName].forEach(charId => {
                    const charInfo = allCharactersData[charId];
                    if (!charInfo) return;

                    const item = document.createElement('div');
                    item.className = 'charakter-item';
                    item.dataset.charakterId = charId;

                    const img = document.createElement('img');
                    const imageUrl = charInfo.pic_url;

                    if (imageUrl) {
                        img.src = `${charProfileUrlBase}/${imageUrl}`; // KORRIGIERT
                        img.addEventListener('error', function() {
                            this.onerror = null;
                            this.src = placeholderUrlMissing;
                        }, {
                            once: true
                        });
                    } else {
                        img.src = placeholderUrlUnknown;
                    }

                    img.alt = charInfo.name;
                    if (selectedCharIds.includes(charId)) {
                        item.classList.add('active');
                    }

                    const nameSpan = document.createElement('span');
                    nameSpan.textContent = charInfo.name;

                    item.appendChild(img);
                    item.appendChild(nameSpan);
                    grid.appendChild(item);
                });

                kategorieDiv.appendChild(grid);
                modalCharaktereSection.appendChild(kategorieDiv);
            });
        }


        modalCharaktereSection.addEventListener('click', (e) => {
            const item = e.target.closest('.charakter-item');
            if (!item) return;

            const charId = item.dataset.charakterId;
            const isActiveNow = !item.classList.contains('active');

            // Synchronize all items with the same character ID
            const allItemsForId = modalCharaktereSection.querySelectorAll(`.charakter-item[data-charakter-id="${charId}"]`);
            allItemsForId.forEach(el => el.classList.toggle('active', isActiveNow));

            if (!comicData[activeEditId].charaktere) {
                comicData[activeEditId].charaktere = [];
            }

            const charakterArray = comicData[activeEditId].charaktere;
            const index = charakterArray.indexOf(charId);

            if (isActiveNow) {
                if (index === -1) {
                    charakterArray.push(charId);
                }
            } else {
                if (index > -1) {
                    // Remove all occurrences just in case of data duplication
                    comicData[activeEditId].charaktere = charakterArray.filter(id => id !== charId);
                }
            }
        });


        modalImageControls.addEventListener('click', (e) => {
            if (e.target.matches('.button-toggle')) {
                setImageView(e.target.dataset.view);
            }
        });

        // Logik für "Speichern" in eine eigene Funktion ausgelagert
        const handleSaveAll = async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'save_comic_data');
                formData.append('comics', JSON.stringify(comicData));
                formData.append('csrf_token', csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                try {
                    const data = JSON.parse(responseText);
                    if (response.ok && data.success) {
                        showMessage(data.message, 'green');
                        await saveSettings();
                        updateTimestamp();
                    } else {
                        showMessage(`Fehler: ${data.message || 'Unbekannter Fehler'}`, 'red');
                    }
                } catch (e) {
                    throw new Error(`Ungültige JSON-Antwort vom Server: ${responseText}`);
                }
            } catch (error) {
                showMessage(`Netzwerkfehler: ${error.message}`, 'red');
            }
        };

        // Logik für "Neuer Eintrag" in eine eigene Funktion ausgelagert
        const handleAddRow = () => {
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

            // Modal Anzeige auf Flex setzen für korrekte Zentrierung und Layout
            editModal.style.display = 'flex';
        };

        // Listener für BEIDE "Speichern"-Buttons
        saveAllBtn.addEventListener('click', handleSaveAll);
        saveAllBtnTop.addEventListener('click', handleSaveAll);

        // Listener für BEIDE "Neuer Eintrag"-Buttons
        addRowBtn.addEventListener('click', handleAddRow);
        addRowBtnTop.addEventListener('click', handleAddRow);

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

                // Modal Anzeige auf Flex setzen
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

        renderTable();
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
