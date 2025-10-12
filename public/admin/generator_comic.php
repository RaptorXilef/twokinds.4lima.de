<?php
/**
 * Administrationsseite für den Comic-Seiten-Generator.
 * 
 * @file      ROOT/public/admin/generator_comic.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   3.0.0
 * @since     2.0.0 Vollständig überarbeitet mit modernem UI, Speicherung der letzten Ausführung und detailliertem Protokoll.
 * @since     2.1.0 Anpassung an versionierte comic_var.json (Schema v2).
 * @since     2.2.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     3.0.0 Implementierung einer dynamischen relativen Pfadberechnung und verbesserte Bilderkennung.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin_init.php';

// --- HILFSFUNKTIONEN ---

/**
 * Berechnet den relativen Pfad von einem Start- zu einem Zielpfad.
 * @param string $from Der absolute Startpfad (Verzeichnis).
 * @param string $to Der absolute Zielpfad (Datei).
 * @return string Der berechnete relative Pfad.
 */
function getRelativePath(string $from, string $to): string
{
    $from = str_replace('\\', '/', rtrim($from, '/\\'));
    $to = str_replace('\\', '/', $to);

    $fromParts = explode('/', $from);
    $toParts = explode('/', $to);
    $toFilename = array_pop($toParts);

    while (count($fromParts) && count($toParts) && ($fromParts[0] == $toParts[0])) {
        array_shift($fromParts);
        array_shift($toParts);
    }

    $relativePath = str_repeat('../', count($fromParts));
    $relativePath .= implode('/', $toParts);
    $relativePath .= '/' . $toFilename;

    return $relativePath;
}

function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'generator_comic' => ['last_run_timestamp' => null]
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
    if (!isset($settings['generator_comic']))
        $settings['generator_comic'] = $defaults['generator_comic'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

function getComicData(string $filePath, bool $debugMode): ?array
{
    if (!file_exists($filePath))
        return null;
    $content = file_get_contents($filePath);
    if ($content === false)
        return null;
    $decodedData = json_decode($content, true);
    if (is_array($decodedData)) {
        if (isset($decodedData['schema_version']) && $decodedData['schema_version'] >= 2 && isset($decodedData['comics'])) {
            return $decodedData['comics'];
        }
        return $decodedData;
    }
    return [];
}

function getComicIdsFromImages(array $dirPaths, bool $debugMode): array
{
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($dirPaths as $dirPath) {
        if (is_dir($dirPath)) {
            $files = scandir($dirPath);
            if ($files === false)
                continue;
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || substr($file, 0, 1) === '.' || strpos($file, 'in_translation') !== false)
                    continue;
                $info = pathinfo($file);
                if (isset($info['filename']) && preg_match('/^\d{8}$/', $info['filename']) && isset($info['extension']) && in_array(strtolower($info['extension']), $imageExtensions)) {
                    $imageIds[] = $info['filename'];
                }
            }
        }
    }
    sort($imageIds);
    return array_unique($imageIds);
}

function getMissingComicPageFiles(array $comicIds, string $comicPagesDir, bool $debugMode): array
{
    $missingFiles = [];
    foreach ($comicIds as $id) {
        if (!file_exists($comicPagesDir . $id . '.php')) {
            $missingFiles[] = $id;
        }
    }
    return $missingFiles;
}

function createSingleComicPageFile(string $comicId, string $comicPagesDir, bool $debugMode): bool
{
    if (!is_dir($comicPagesDir)) {
        if (!mkdir($comicPagesDir, 0777, true))
            return false;
    }
    $filePath = $comicPagesDir . $comicId . '.php';
    $relativePath = getRelativePath($comicPagesDir, COMIC_PAGE_RENDERER_PATH);
    $fileContent = "<?php require_once __DIR__ . '/" . $relativePath . "'; ?>";
    return file_put_contents($filePath, $fileContent) !== false;
}

// --- AJAX-Anfrage-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    switch ($action) {
        case 'create_single_comic_page':
            $comicId = $_POST['comic_id'] ?? '';
            if (empty($comicId)) {
                $response['message'] = 'Keine Comic-ID angegeben.';
                http_response_code(400);
            } elseif (createSingleComicPageFile($comicId, PUBLIC_COMIC_PATH, $debugMode)) {
                $response['success'] = true;
                $response['message'] = 'Comic-Seite für ' . $comicId . ' erstellt.';
                $response['comicId'] = $comicId;
            } else {
                $response['message'] = 'Fehler beim Erstellen der Seite für ' . $comicId . '.';
                http_response_code(500);
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings(CONFIG_GENERATOR_SETTINGS_JSON, $debugMode);
            $currentSettings['generator_comic'] = ['last_run_timestamp' => time()];
            if (saveGeneratorSettings(CONFIG_GENERATOR_SETTINGS_JSON, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings(CONFIG_GENERATOR_SETTINGS_JSON, $debugMode);
$comicSettings = $settings['generator_comic'];
$comicIdsFromImages = getComicIdsFromImages([PUBLIC_IMG_COMIC_LOWRES_PATH, PUBLIC_IMG_COMIC_HIRES_PATH], $debugMode);
$comicDataFromJson = getComicData(COMIC_VAR_JSON, $debugMode);
$allComicIds = $comicDataFromJson ? array_unique(array_merge(array_keys($comicDataFromJson), $comicIdsFromImages)) : $comicIdsFromImages;
sort($allComicIds);
$missingComicPages = getMissingComicPageFiles($allComicIds, PUBLIC_COMIC_PATH, $debugMode);

$pageTitle = 'Adminbereich - Comic Generator';
$pageHeader = 'Comic-Seiten-Generator';
$siteDescription = 'Generiert fehlende Comic-PHP-Seiten basierend auf vorhandenen Bildern und JSON-Daten.';
$robotsContent = 'noindex, nofollow';
include TEMPLATE_HEADER;
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <?php if ($comicSettings['last_run_timestamp']): ?>
                <p class="status-message status-info">Letzte Ausführung am
                    <?php echo date('d.m.Y \u\m H:i:s', $comicSettings['last_run_timestamp']); ?> Uhr.
                </p>
            <?php endif; ?>

            <h2>Status & Ausführung</h2>
            <p>Dieses Tool prüft, für welche Comic-Bilder eine entsprechende PHP-Anzeigeseite fehlt und erstellt diese
                bei Bedarf automatisch.</p>

            <div id="fixed-buttons-container">
                <button type="button" id="generate-pages-button" <?php echo empty($missingComicPages) ? 'disabled' : ''; ?>>Fehlende Seiten erstellen</button>
                <button type="button" id="toggle-pause-resume-button" class="hidden-by-default"></button>
            </div>
        </div>

        <div id="generation-results-section" class="hidden-by-default">
            <h2 class="results-header">Ergebnisse der Generierung</h2>
            <div id="created-items-container" class="generated-items-grid"></div>
            <h3 class="status-red hidden-by-default" id="error-header">Protokoll & Fehler:</h3>
            <ul id="generation-log-list"></ul>
        </div>

        <div id="loading-spinner" class="hidden-by-default">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Comic-Seiten...</p>
        </div>

        <div id="initial-status-container">
            <?php if (empty($allComicIds)): ?>
                <p class="status-message status-orange">Keine Comic-Bilder gefunden, auf deren Basis Seiten erstellt werden
                    könnten.</p>
            <?php elseif (empty($missingComicPages)): ?>
                <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Comic-PHP-Dateien sind
                    vorhanden.</p>
            <?php else: ?>
                <p class="status-message status-red">Es fehlen <strong><?php echo count($missingComicPages); ?></strong>
                    Comic-PHP-Dateien:</p>
                <div id="missing-pages-grid" class="missing-items-grid">
                    <?php foreach ($missingComicPages as $id): ?>
                        <span class="missing-item"
                            data-comic-id="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?>.php</span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    :root {
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --missing-item-bg-color: #e9e9e9;
        --generated-item-bg-color: #d4edda;
        --generated-item-text-color: #155724;
        --generated-item-border-color: #c3e6cb;
    }

    body.theme-night {
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --missing-item-bg-color: #025373;
        --generated-item-bg-color: #2a6177;
        --generated-item-text-color: #fff;
        --generated-item-border-color: #48778a;
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

    .status-orange {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
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

    .status-red-button,
    .status-green-button,
    #generate-pages-button {
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
        border: none;
    }

    #generate-pages-button {
        background-color: #007bff;
    }

    #generate-pages-button:hover {
        background-color: #0056b3;
    }

    .status-red-button {
        background-color: #dc3545;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-green-button {
        background-color: #28a745;
    }

    .status-green-button:hover {
        background-color: #218838;
    }

    #generate-pages-button:disabled,
    .status-red-button:disabled,
    .status-green-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        cursor: not-allowed;
    }

    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border-left-color: #09f;
        animation: spin 1s ease infinite;
        margin: 0 auto 10px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .generated-items-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .generated-item {
        text-align: center;
        border: 1px solid var(--generated-item-border-color);
        padding: 8px 12px;
        border-radius: 8px;
        background-color: var(--generated-item-bg-color);
        color: var(--generated-item-text-color);
        font-size: 0.9em;
    }

    #fixed-buttons-container {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: flex-end;
    }

    .missing-items-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--missing-grid-border-color);
        padding: 10px;
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
    }

    .missing-item {
        background-color: var(--missing-item-bg-color);
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.9em;
    }

    #generation-log-list {
        list-style-type: none;
        padding-left: 0;
        margin-top: 10px;
        background-color: var(--missing-grid-bg-color);
        border: 1px solid var(--missing-grid-border-color);
        border-radius: 5px;
        padding: 10px;
        max-height: 300px;
        overflow-y: auto;
    }

    #generation-log-list li {
        padding: 5px;
        border-bottom: 1px dashed var(--missing-grid-border-color);
    }

    #generation-log-list li:last-child {
        border-bottom: none;
    }

    .log-info {
        color: #0c5460;
    }

    .log-success {
        color: #155724;
    }

    .log-warning {
        color: #856404;
    }

    .log-error {
        color: #721c24;
    }

    body.theme-night .log-info {
        color: #69d3e8;
    }

    body.theme-night .log-success {
        color: #28a745;
    }

    body.theme-night .log-warning {
        color: #ffc107;
    }

    body.theme-night .log-error {
        color: #dc3545;
    }

    .hidden-by-default {
        display: none;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const settingsAndActionsContainer = document.getElementById('settings-and-actions-container');
        const initialStatusContainer = document.getElementById('initial-status-container');
        const generateButton = document.getElementById('generate-pages-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingPagesGrid = document.getElementById('missing-pages-grid');
        const createdItemsContainer = document.getElementById('created-items-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const errorHeader = document.getElementById('error-header');
        const logList = document.getElementById('generation-log-list');

        const initialMissingIds = <?php echo json_encode($missingComicPages); ?>;
        let isPaused = false;
        let isGenerationActive = false;

        function addLogMessage(message, type) {
            const li = document.createElement('li');
            li.className = `log-${type}`;
            li.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logList.appendChild(li);
            errorHeader.style.display = 'block';
            li.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }

        async function saveSettings() {
            await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'save_settings', type: 'comic pages', csrf_token: csrfToken })
            });
        }

        async function createSinglePage(comicId) {
            const formData = new URLSearchParams({ action: 'create_single_comic_page', comic_id: comicId, csrf_token: csrfToken });
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                if (!response.ok) throw new Error(`Server-Antwort: ${response.status}`);
                return await response.json();
            } catch (error) {
                return { success: false, message: `Netzwerkfehler: ${error.message}` };
            }
        }

        async function processGenerationQueue() {
            isGenerationActive = true;
            updateButtonState();
            loadingSpinner.style.display = 'block';
            generationResultsSection.style.display = 'block';
            createdItemsContainer.innerHTML = '';
            logList.innerHTML = '';
            errorHeader.style.display = 'none';

            let createdCount = 0;
            let errorCount = 0;

            for (let i = 0; i < initialMissingIds.length; i++) {
                if (isPaused) { await new Promise(resolve => { const interval = setInterval(() => { if (!isPaused) { clearInterval(interval); resolve(); } }, 200); }); }
                const currentId = initialMissingIds[i];
                progressText.textContent = `Verarbeite ${i + 1} von ${initialMissingIds.length} (${currentId}.php)...`;
                addLogMessage(`Versuch für '${currentId}.php'...`, 'info');

                const result = await createSinglePage(currentId);

                if (result.success) {
                    createdCount++;
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'generated-item';
                    itemDiv.textContent = `${result.comicId}.php`;
                    createdItemsContainer.appendChild(itemDiv);
                    const missingItem = missingPagesGrid.querySelector(`span[data-comic-id="${result.comicId}"]`);
                    if (missingItem) missingItem.remove();
                    addLogMessage(`Erfolgreich für '${currentId}.php' erstellt.`, 'success');
                } else {
                    errorCount++;
                    addLogMessage(`Fehlgeschlagen für '${currentId}.php': ${result.message}`, 'error');
                }
                await new Promise(resolve => setTimeout(resolve, 50));
            }

            loadingSpinner.style.display = 'none';
            isGenerationActive = false;
            progressText.textContent = 'Prozess abgeschlossen.';

            if (settingsAndActionsContainer) settingsAndActionsContainer.style.display = 'none';
            if (initialStatusContainer) initialStatusContainer.style.display = 'none';

            addLogMessage(`Prozess beendet. ${createdCount} Seiten erstellt, ${errorCount} Fehler.`, 'info');
            if (createdCount > 0) {
                await saveSettings();
            }
        }

        function updateButtonState() {
            if (initialMissingIds.length === 0) {
                generateButton.disabled = true;
                togglePauseResumeButton.style.display = 'none';
            } else if (isGenerationActive) {
                settingsAndActionsContainer.style.display = 'none';
                togglePauseResumeButton.style.display = 'inline-block';
                if (isPaused) {
                    togglePauseResumeButton.textContent = 'Fortsetzen';
                    togglePauseResumeButton.className = 'status-green-button';
                } else {
                    togglePauseResumeButton.textContent = 'Pause';
                    togglePauseResumeButton.className = 'status-red-button';
                }
            } else {
                settingsAndActionsContainer.style.display = 'block';
                generateButton.style.display = 'inline-block';
                generateButton.disabled = false;
                togglePauseResumeButton.style.display = 'none';
            }
        }

        generateButton.addEventListener('click', processGenerationQueue);
        togglePauseResumeButton.addEventListener('click', () => { isPaused = !isPaused; updateButtonState(); });
        updateButtonState();
    });
</script>

<?php
include TEMPLATE_FOOTER;
?>