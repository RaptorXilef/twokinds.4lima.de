<?php
/**
 * Administrationsseite zum Bearbeiten der sitemap.json Konfigurationsdatei.
 * 
 * @file      /admin/data_editor_sitemap.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.3.0
 * @since     2.3.0 Korrektur des AJAX-Handlers zur korrekten Verarbeitung von FormData und CSRF-Token.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$sitemapJsonPath = __DIR__ . '/../src/config/sitemap.json';
$comicDirPath = realpath(__DIR__ . '/../comic/') . '/';
$settingsFilePath = __DIR__ . '/../src/config/generator_settings.json';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'generator_thumbnail' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_run_timestamp' => null],
        'generator_socialmedia' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_used_resize_mode' => 'crop', 'last_run_timestamp' => null],
        'build_image_cache' => ['last_run_type' => null, 'last_run_timestamp' => null],
        'generator_comic' => ['last_run_timestamp' => null],
        'upload_image' => ['last_run_timestamp' => null],
        'generator_rss' => ['last_run_timestamp' => null],
        'data_editor_sitemap' => ['last_run_timestamp' => null]
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
    if (!isset($settings['data_editor_sitemap']))
        $settings['data_editor_sitemap'] = $defaults['data_editor_sitemap'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

define('COMIC_PAGES_PER_PAGE', 50);

function loadSitemapData(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0)
        return ['pages' => []];
    $content = file_get_contents($path);
    if ($content === false)
        return ['pages' => []];
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        return ['pages' => []];
    $pages = isset($data['pages']) && is_array($data['pages']) ? $data['pages'] : [];
    $sanitizedPages = [];
    foreach ($pages as $page) {
        $loc = $page['loc'] ?? '';
        $name = $page['name'] ?? '';
        $path_val = $page['path'] ?? './';
        if (empty($loc) && !empty($name)) {
            $loc = rtrim($path_val, '/\\') . '/' . $name;
        }
        if (!empty($loc) && empty($name)) {
            $name = basename($loc);
        }
        if (!empty($loc)) {
            $sanitizedPages[] = ['loc' => $loc, 'name' => $name, 'path' => $path_val, 'priority' => (float) ($page['priority'] ?? 0.5), 'changefreq' => $page['changefreq'] ?? 'weekly'];
        }
    }
    return ['pages' => $sanitizedPages];
}

function saveSitemapData(string $path, array $data, bool $debugMode): bool
{
    if (isset($data['pages']) && is_array($data['pages'])) {
        usort($data['pages'], fn($a, $b) => strcmp($a['loc'] ?? '', $b['loc'] ?? ''));
    }
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false)
        return false;
    return file_put_contents($path, $jsonContent) !== false;
}

function scanComicDirectory(string $dirPath, bool $debugMode): array
{
    if (!is_dir($dirPath))
        return [];
    $comicFiles = [];
    $files = scandir($dirPath);
    if ($files === false)
        return [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'index.php')
            continue;
        if (preg_match('/^\d{8}\.php$/', $file)) {
            $comicFiles[] = $file;
        }
    }
    sort($comicFiles);
    return $comicFiles;
}

// --- AJAX-Handler (Korrigiert) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/src/components/security_check.php';

    ob_end_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Unbekannte Aktion oder fehlende Daten.'];

    switch ($action) {
        case 'save_sitemap':
            $pagesToSaveStr = $_POST['pages'] ?? '[]';
            $allPagesToSave = json_decode($pagesToSaveStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $response['message'] = 'Fehler: Die übermittelten Seitendaten sind kein gültiges JSON.';
                http_response_code(400);
            } elseif (saveSitemapData($sitemapJsonPath, ['pages' => $allPagesToSave], $debugMode)) {
                $response = ['success' => true, 'message' => 'Sitemap-Daten erfolgreich gespeichert!'];
            } else {
                $response['message'] = 'Fehler beim Speichern der Sitemap-Daten.';
                http_response_code(500);
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings($settingsFilePath, $debugMode);
            $currentSettings['data_editor_sitemap']['last_run_timestamp'] = time();
            if (saveGeneratorSettings($settingsFilePath, $currentSettings, $debugMode)) {
                $response['success'] = true;
                $response['message'] = 'Zeitstempel gespeichert.';
            } else {
                $response['message'] = 'Fehler beim Speichern des Zeitstempels.';
                http_response_code(500);
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings($settingsFilePath, $debugMode);
$sitemapSettings = $settings['data_editor_sitemap'];
$sitemapData = loadSitemapData($sitemapJsonPath, $debugMode);
$existingPages = $sitemapData['pages'];
$generalPages = [];
$comicPages = [];
foreach ($existingPages as $page) {
    if (($page['path'] ?? './') === './comic/') {
        $comicPages[$page['loc']] = $page;
    } else {
        $generalPages[] = $page;
    }
}
$foundComicFiles = scanComicDirectory($comicDirPath, $debugMode);
foreach ($foundComicFiles as $filename) {
    $loc = 'comic/' . $filename;
    if (!isset($comicPages[$loc])) {
        $comicPages[$loc] = ['loc' => $loc, 'name' => $filename, 'path' => './comic/', 'priority' => 0.8, 'changefreq' => 'never'];
    }
}
ksort($comicPages);

$pageTitle = 'Adminbereich - Sitemap Editor';
$pageHeader = 'Sitemap Editor';
$robotsContent = 'noindex, nofollow';
include $headerPath;
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($sitemapSettings['last_run_timestamp']): ?>
                    <p class="status-message status-info">Letzte Speicherung am
                        <?php echo date('d.m.Y \u\m H:i:s', $sitemapSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Sitemap Editor</h2>
            <p>Verwalte hier die Einträge für deine Sitemap. Comic-Seiten werden automatisch erkannt und hinzugefügt.
            </p>
        </div>

        <div class="collapsible-section expanded" id="general-sitemap-section">
            <div class="collapsible-header">
                <h3>Allgemeine Seiten</h3>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="collapsible-content">
                <p class="path-hint">Pfade sollten relativ zum Hauptverzeichnis beginnen, z.B.
                    <code>./meine-seite.php</code>.
                </p>
                <div class="sitemap-table-container">
                    <table class="sitemap-table" id="sitemap-table">
                        <thead>
                            <tr>
                                <th>Pfad/Name (Loc)</th>
                                <th>Priorität</th>
                                <th>Frequenz</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- General pages rows will be inserted by JS -->
                        </tbody>
                    </table>
                </div>
                <button class="button add-row-btn"><i class="fas fa-plus-circle"></i> Zeile hinzufügen</button>
            </div>
        </div>

        <div class="collapsible-section expanded" id="comic-sitemap-section">
            <div class="collapsible-header">
                <h3>Comic-Seiten</h3>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="collapsible-content">
                <div class="sitemap-table-container">
                    <table class="sitemap-table" id="comic-table">
                        <thead>
                            <tr>
                                <th>Dateiname</th>
                                <th>Priorität</th>
                                <th>Frequenz</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Comic pages rows will be inserted by JS -->
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <!-- Pagination links will be inserted by JS -->
                </div>
            </div>
        </div>

        <div id="fixed-buttons-container">
            <button id="save-all-btn" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
        </div><br>
        <div id="message-box" class="hidden-by-default"></div>
    </div>
</article>

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

    .collapsible-section {
        margin-bottom: 20px;
    }

    .collapsible-header {
        cursor: pointer;
        padding: 15px;
        background-color: #f2f2f2;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px 8px 0 0;
    }

    body.theme-night .collapsible-header {
        background-color: #005a7e;
        border-bottom-color: #007bb5;
    }

    .collapsible-header i {
        transition: transform 0.3s ease;
    }

    .collapsible-section:not(.expanded) .collapsible-header {
        border-radius: 8px;
        border-bottom: none;
    }

    .collapsible-section:not(.expanded) .collapsible-header i {
        transform: rotate(-90deg);
    }

    .collapsible-content {
        padding: 15px;
        border: 1px solid #eee;
        border-top: none;
    }

    body.theme-night .collapsible-content {
        border-color: #007bb5;
    }

    .collapsible-section.expanded .collapsible-content {
        display: block;
    }

    .collapsible-section:not(.expanded) .collapsible-content {
        display: none;
    }

    .sitemap-table-container {
        overflow-x: auto;
    }

    .sitemap-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sitemap-table th,
    .sitemap-table td {
        padding: 8px;
        border-bottom: 1px solid var(--missing-grid-border-color);
        text-align: left;
    }

    .sitemap-table input,
    .sitemap-table select {
        width: 100%;
        padding: 5px;
        border-radius: 3px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    body.theme-night .sitemap-table {
        color: #f0f0f0;
    }

    body.theme-night .sitemap-table input,
    body.theme-night .sitemap-table select {
        background-color: #03425b;
        border-color: #045d81;
        color: #f0f0f0;
    }

    .path-hint {
        font-size: 0.9em;
        color: #666;
        margin-bottom: 10px;
    }

    body.theme-night .path-hint {
        color: #bbb;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
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

    .pagination a:hover {
        background-color: #e9ecef;
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

    body.theme-night .pagination a:hover {
        background-color: #006690;
    }

    body.theme-night .pagination span.current-page {
        background-color: #2a6177;
        border-color: #2a6177;
    }

    #fixed-buttons-container {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .hidden-by-default {
        display: none;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const fullComicPagesData = <?php echo json_encode(array_values($comicPages), JSON_UNESCAPED_SLASHES); ?>;
        let generalPagesData = <?php echo json_encode($generalPages, JSON_UNESCAPED_SLASHES); ?>;

        const generalTableBody = document.querySelector('#sitemap-table tbody');
        const comicTableBody = document.querySelector('#comic-table tbody');
        const paginationContainer = document.querySelector('.pagination');
        const saveAllBtn = document.getElementById('save-all-btn');
        const addRowBtn = document.querySelector('.add-row-btn');
        const messageBox = document.getElementById('message-box');
        const lastRunContainer = document.getElementById('last-run-container');

        const COMIC_PER_PAGE = <?php echo COMIC_PAGES_PER_PAGE; ?>;
        let comicCurrentPage = 1;

        const renderRow = (page, isComic = false) => {
            const row = document.createElement('tr');
            row.dataset.loc = page.loc;
            const freqOptions = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']
                .map(f => `<option value="${f}" ${page.changefreq === f ? 'selected' : ''}>${f}</option>`).join('');
            row.innerHTML = `
                <td><input type="text" class="loc-input" value="${isComic ? page.name : page.loc}"></td>
                <td><input type="number" class="priority-input" value="${page.priority}" step="0.1" min="0" max="1"></td>
                <td><select class="changefreq-select">${freqOptions}</select></td>
                <td><button class="button delete-row-btn"><i class="fas fa-trash-alt"></i></button></td>
            `;
            if (isComic) row.querySelector('.loc-input').disabled = true;
            return row;
        };

        const renderGeneralTable = () => {
            generalTableBody.innerHTML = '';
            generalPagesData.forEach(page => generalTableBody.appendChild(renderRow(page, false)));
        };

        const renderComicTable = () => {
            comicTableBody.innerHTML = '';
            const start = (comicCurrentPage - 1) * COMIC_PER_PAGE;
            const end = start + COMIC_PER_PAGE;
            const paginatedItems = fullComicPagesData.slice(start, end);
            paginatedItems.forEach(page => comicTableBody.appendChild(renderRow(page, true)));
        };

        const renderPagination = () => {
            const totalPages = Math.ceil(fullComicPagesData.length / COMIC_PER_PAGE);
            paginationContainer.innerHTML = '';
            if (totalPages <= 1) return;

            if (comicCurrentPage > 1) paginationContainer.innerHTML += `<a data-page="${comicCurrentPage - 1}">&laquo;</a>`;
            for (let i = 1; i <= totalPages; i++) {
                if (i === comicCurrentPage) paginationContainer.innerHTML += `<span class="current-page">${i}</span>`;
                else paginationContainer.innerHTML += `<a data-page="${i}">${i}</a>`;
            }
            if (comicCurrentPage < totalPages) paginationContainer.innerHTML += `<a data-page="${comicCurrentPage + 1}">&raquo;</a>`;
        };

        function showMessage(message, type) {
            messageBox.textContent = message;
            messageBox.className = `status-message status-${type}`;
            messageBox.style.display = 'block';
            setTimeout(() => { messageBox.style.display = 'none'; }, 5000);
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
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('csrf_token', csrfToken);
            await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
        }

        saveAllBtn.addEventListener('click', async () => {
            let allPages = [...generalPagesData, ...fullComicPagesData];
            try {
                const formData = new FormData();
                formData.append('action', 'save_sitemap');
                formData.append('pages', JSON.stringify(allPages));
                formData.append('csrf_token', csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                try {
                    const data = JSON.parse(responseText);
                    if (response.ok && data.success) {
                        showMessage('Sitemap erfolgreich gespeichert!', 'green');
                        await saveSettings();
                        updateTimestamp();
                    } else { showMessage(`Fehler: ${data.message || 'Unbekannter Fehler'}`, 'red'); }
                } catch (e) {
                    throw new Error(`Ungültige JSON-Antwort vom Server: ${responseText}`);
                }
            } catch (error) { showMessage(`Netzwerkfehler: ${error.message}`, 'red'); }
        });

        addRowBtn.addEventListener('click', () => {
            const newPage = { loc: './neue-seite.php', name: 'neue-seite.php', path: './', priority: 0.5, changefreq: 'weekly' };
            generalPagesData.push(newPage);
            renderGeneralTable();
        });

        generalTableBody.addEventListener('change', (e) => {
            const row = e.target.closest('tr');
            if (!row) return;
            const loc = row.dataset.loc;
            const pageIndex = generalPagesData.findIndex(p => p.loc === loc);
            if (pageIndex === -1) return;

            const newLoc = row.querySelector('.loc-input').value;
            generalPagesData[pageIndex].loc = newLoc;
            generalPagesData[pageIndex].name = newLoc.includes('/') ? newLoc.substring(newLoc.lastIndexOf('/') + 1) : newLoc;
            generalPagesData[pageIndex].path = newLoc.includes('/') ? newLoc.substring(0, newLoc.lastIndexOf('/') + 1) : './';
            generalPagesData[pageIndex].priority = row.querySelector('.priority-input').value;
            generalPagesData[pageIndex].changefreq = row.querySelector('.changefreq-select').value;
            row.dataset.loc = newLoc;
        });

        comicTableBody.addEventListener('change', (e) => {
            const row = e.target.closest('tr');
            if (!row) return;
            const loc = row.dataset.loc;
            const pageIndex = fullComicPagesData.findIndex(p => p.loc === loc);
            if (pageIndex === -1) return;

            fullComicPagesData[pageIndex].priority = row.querySelector('.priority-input').value;
            fullComicPagesData[pageIndex].changefreq = row.querySelector('.changefreq-select').value;
        });

        document.querySelector('body').addEventListener('click', e => {
            if (e.target.closest('.delete-row-btn')) {
                const row = e.target.closest('tr');
                const loc = row.dataset.loc;
                const tableId = row.closest('table').id;

                if (tableId === 'sitemap-table') {
                    generalPagesData = generalPagesData.filter(p => p.loc !== loc);
                    row.remove();
                } else {
                    showMessage('Automatisch hinzugefügte Comic-Seiten können nicht gelöscht werden.', 'orange');
                }
            }
        });

        paginationContainer.addEventListener('click', (e) => {
            if (e.target.tagName === 'A' && e.target.dataset.page) {
                e.preventDefault();
                comicCurrentPage = parseInt(e.target.dataset.page, 10);
                renderComicTable();
                renderPagination();
            }
        });

        document.querySelectorAll('.collapsible-header').forEach(header => {
            header.addEventListener('click', () => {
                header.parentElement.classList.toggle('expanded');
            });
        });

        renderGeneralTable();
        renderComicTable();
        renderPagination();
    });
</script>

<?php include $footerPath; ?>