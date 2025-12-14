<?php

/**
 * Administrationsseite zum Bearbeiten der sitemap.json Konfigurationsdatei.
 *
 * @file      ROOT/public/admin/data_editor_sitemap.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
* @since 2.0.0 - 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Vollständige Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Code-Modernisierung sowie Korrektur des AJAX-Handlers (FormData, CSRF-Token).
 *
 *    UI & FUNKTIONALITÄT
 *    - Implementierung einer fortschrittlichen Paginierung.
 *
 * @since     5.0.0
 * - refactor(UI): Design an Admin-Standard angepasst (7-1 SCSS), Inline-Styles entfernt, Info-Header hinzugefügt.
 * - fix(Sort): Comic-Seiten werden nun absteigend sortiert (Neueste zuerst).
 * - refactor(Config): Nutzung der spezifischen Konstante ENTRIES_PER_PAGE_SITEMAP.
 * - refactor(CSS): Inline-Styles durch SCSS-Klassen ersetzt.
 * - refactor(CSS): Bereinigung verbliebener Inline-Styles im JavaScript.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> data_editor_sitemap).
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
if (!defined('ENTRIES_PER_PAGE_SITEMAP')) {
    define('ENTRIES_PER_PAGE_SITEMAP', 50);
}

// Pfad zur zentralen Admin-Konfiguration
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// --- HILFSFUNKTIONEN ---
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
            error_log("[Sitemap Editor] Config JSON korrupt oder leer. Lade Defaults.");
        }
        return $defaults;
    }

    $userSettings = $data['users'][$username]['data_editor_sitemap'] ?? [];
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

    $currentGeneratorSettings = $data['users'][$username]['data_editor_sitemap'] ?? [];
    $data['users'][$username]['data_editor_sitemap'] = array_replace_recursive($currentGeneratorSettings, $settings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function loadSitemapData(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        return ['pages' => []];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        return ['pages' => []];
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['pages' => []];
    }
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
    // WICHTIG: UTF-8 Support wie bei anderen Editoren
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($jsonContent === false) {
        return false;
    }
    return file_put_contents($path, $jsonContent) !== false;
}

function scanComicDirectory(string $dirPath, bool $debugMode): array
{
    if (!is_dir($dirPath)) {
        return [];
    }
    $comicFiles = [];
    $files = scandir($dirPath);
    if ($files === false) {
        return [];
    }
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'index.php') {
            continue;
        }
        if (preg_match('/^\d{8}\.php$/', $file)) {
            $comicFiles[] = $file;
        }
    }
    rsort($comicFiles); // Sortiert absteigend, neueste zuerst
    return $comicFiles;
}

// --- AJAX-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token(); // Zentralisierte CSRF-Prüfung
    ob_end_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Unbekannte Aktion oder fehlende Daten.'];

    $sitemapJsonPath = Path::getDataPath('sitemap.json');
    $generatorSettingsJsonPath = $configPath;


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
            $currentSettings = loadGeneratorSettings($generatorSettingsJsonPath, $currentUser, $debugMode);
            $currentSettings['last_run_timestamp'] = time();

            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentUser, $currentSettings, $debugMode)) {
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

// === DATEN LADEN ===
$sitemapSettings = loadGeneratorSettings($configPath, $currentUser, $debugMode);
$sitemapData = loadSitemapData(Path::getDataPath('sitemap.json'), $debugMode);
$existingPages = $sitemapData['pages'];
$generalPages = [];
$comicPages = [];

$comicPathPrefix = str_replace(DIRECTORY_PUBLIC_URL, '.', DIRECTORY_PUBLIC_COMIC_URL) . '/';


foreach ($existingPages as $page) {
    if (($page['path'] ?? './') === $comicPathPrefix) {
        $comicPages[$page['loc']] = $page;
    } else {
        $generalPages[] = $page;
    }
}
$foundComicFiles = scanComicDirectory(DIRECTORY_PUBLIC_COMIC, $debugMode);
foreach ($foundComicFiles as $filename) {
    // FIX: Konsistente Pfad-Erstellung ohne trim(), damit Keys zu sitemap.json passen (./comic/...)
    $loc = $comicPathPrefix . $filename;
    if (!isset($comicPages[$loc])) {
        $comicPages[$loc] = ['loc' => $loc, 'name' => $filename, 'path' => $comicPathPrefix, 'priority' => 0.8, 'changefreq' => 'monthly'];
    }
}

// FIX: Explizite Sortierung nach Dateiname (Name) absteigend (Z bis A, Neueste zuerst)
uasort($comicPages, function ($a, $b) {
    return strnatcmp($b['name'], $a['name']);
});

$pageTitle = 'Adminbereich - Sitemap Editor';
$pageHeader = 'Sitemap Editor';
$robotsContent = 'noindex, nofollow';

// Konstante an JS übergeben (Paginierung für Comics)
$itemsPerPage = (int)ENTRIES_PER_PAGE_SITEMAP;

$additionalScripts = '<script nonce="' . htmlspecialchars($nonce) . '" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>';

require_once Path::getPartialTemplatePath('header.php');
?>

    <div class="content-section">
        <!-- INFO HEADER -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if (!empty($sitemapSettings['last_run_timestamp'])) : ?>
                    <p class="status-message status-info">Letzte Speicherung am
                        <?php echo date('d.m.Y \u\m H:i:s', $sitemapSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Speicherung erfasst.</p>
                <?php endif; ?>
            </div>
            <h2>Sitemap Editor</h2>
            <p>Verwalte hier die Einträge für deine Sitemap. Comic-Seiten werden automatisch erkannt und hinzugefügt.</p>
        </div>

        <div id="message-box" class="hidden-by-default"></div>

        <!-- 1. ALLGEMEINE SEITEN -->
        <div class="collapsible-section expanded" id="general-sitemap-section">
            <div class="collapsible-header">
                <h3>Allgemeine Seiten</h3>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="collapsible-content">
                <p class="path-hint">Pfade sollten relativ zum Hauptverzeichnis beginnen, z.B. <code>./meine-seite.php</code>.</p>

                <div class="sitemap-table-container">
                    <table class="admin-table sitemap-editor-table" id="sitemap-table">
                        <thead>
                            <tr>
                                <th>Pfad/Name (Loc)</th>
                                <th>Priorität</th>
                                <th>Frequenz</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS filled -->
                        </tbody>
                    </table>
                </div>

                <div class="add-row-container">
                    <button class="button add-row-btn"><i class="fas fa-plus-circle"></i> Zeile hinzufügen</button>
                </div>
            </div>
        </div>

        <!-- 2. COMIC SEITEN -->
        <div class="collapsible-section expanded" id="comic-sitemap-section">
            <div class="collapsible-header">
                <h3>Comic-Seiten (Automatisch)</h3>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="collapsible-content">
                <div class="pagination-info-container">
                    <small>Zeigt <?php echo $itemsPerPage; ?> Einträge pro Seite.</small>
                </div>

                <div class="sitemap-table-container">
                    <table class="admin-table sitemap-editor-table" id="comic-table">
                        <thead>
                            <tr>
                                <th>Dateiname</th>
                                <th>Priorität</th>
                                <th>Frequenz</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS filled -->
                        </tbody>
                    </table>
                </div>
                <div class="pagination"></div>
            </div>
        </div>

        <!-- FOOTER ACTIONS -->
        <div id="fixed-buttons-container">
            <button id="save-all-btn" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
        </div>
    </div>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        // Daten aus PHP
        const fullComicPagesData = <?php echo json_encode(array_values($comicPages), JSON_UNESCAPED_SLASHES); ?>;
        let generalPagesData = <?php echo json_encode($generalPages, JSON_UNESCAPED_SLASHES); ?>;

        // UI Elemente
        const generalTableBody = document.querySelector('#sitemap-table tbody');
        const comicTableBody = document.querySelector('#comic-table tbody');
        const paginationContainer = document.querySelector('.pagination');
        const saveAllBtn = document.getElementById('save-all-btn');
        const addRowBtn = document.querySelector('.add-row-btn');
        const messageBox = document.getElementById('message-box');
        const lastRunContainer = document.getElementById('last-run-container');

        const COMIC_PER_PAGE = <?php echo $itemsPerPage; ?>;
        let comicCurrentPage = 1;

        // --- ZEILEN RENDERER ---
        const renderRow = (page, isComic = false) => {
            const row = document.createElement('tr');
            row.dataset.loc = page.loc;

            const freqOptions = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never']
                .map(f => `<option value="${f}" ${page.changefreq === f ? 'selected' : ''}>${f}</option>`).join('');

            // Icon-Button für Löschen (nur bei General erlaubt, aber Comic-Buttons müssen wg. Layout da sein)
            // FIX: Inline CSS entfernt, 'disabled' Attribut reicht dank SCSS
            const deleteBtn = isComic ?
                `<button class="button delete-row-btn" disabled><i class="fas fa-lock"></i></button>` :
                `<button class="button delete-row-btn" title="Eintrag entfernen"><i class="fas fa-trash-alt"></i></button>`;

            row.innerHTML = `
                <td>
                    <input type="text" class="loc-input" value="${isComic ? page.name : page.loc}" ${isComic ? 'disabled' : ''}>
                </td>
                <td>
                    <input type="number" class="priority-input" value="${page.priority}" step="0.1" min="0" max="1">
                </td>
                <td>
                    <select class="changefreq-select">${freqOptions}</select>
                </td>
                <!-- FIX: Inline Style durch Klasse ersetzt -->
                <td class="text-right">
                    ${deleteBtn}
                </td>
            `;
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

            let htmlParts = [];
            if (comicCurrentPage > 1) {
                htmlParts.push(`<a data-page="1">&laquo;</a>`);
                htmlParts.push(`<a data-page="${comicCurrentPage - 1}">&lsaquo;</a>`);
            }

            let startPage = Math.max(1, comicCurrentPage - 4);
            let endPage = Math.min(totalPages, comicCurrentPage + 4);

            if (startPage > 1) {
                htmlParts.push(`<a data-page="1">1</a>`);
                if (startPage > 2) htmlParts.push(`<span>...</span>`);
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === comicCurrentPage) {
                    htmlParts.push(`<span class="current-page">${i}</span>`);
                } else {
                    htmlParts.push(`<a data-page="${i}">${i}</a>`);
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) htmlParts.push(`<span>...</span>`);
                htmlParts.push(`<a data-page="${totalPages}">${totalPages}</a>`);
            }

            if (comicCurrentPage < totalPages) {
                htmlParts.push(`<a data-page="${comicCurrentPage + 1}">&rsaquo;</a>`);
                htmlParts.push(`<a data-page="${totalPages}">&raquo;</a>`);
            }
            paginationContainer.innerHTML = htmlParts.join('');
        };

        function showMessage(message, type) {
            messageBox.textContent = message;
            messageBox.className = `status-message status-${type}`;
            messageBox.style.display = 'block';
            // Auto-Hide nach 5 Sekunden
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
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

        // --- EVENT HANDLER ---

        saveAllBtn.addEventListener('click', async () => {
            // Aktuelle Daten aus den Tabellen sammeln
            const updatedGeneralPages = [];
            generalTableBody.querySelectorAll('tr').forEach(row => {
                const loc = row.querySelector('.loc-input').value;
                if (loc) { // Nur speichern wenn Loc nicht leer ist
                    updatedGeneralPages.push({
                        loc: loc,
                        name: loc.includes('/') ? loc.substring(loc.lastIndexOf('/') + 1) : loc,
                        path: loc.includes('/') ? loc.substring(0, loc.lastIndexOf('/') + 1) : './',
                        priority: row.querySelector('.priority-input').value,
                        changefreq: row.querySelector('.changefreq-select').value
                    });
                }
            });
            generalPagesData = updatedGeneralPages;

            // Comic Seiten: Wir nehmen die globalen Daten (da nur Priority/Freq geändert werden kann, und das haben wir via 'change' event syncronisiert)
            const allPages = [...generalPagesData, ...fullComicPagesData];

            try {
                const formData = new FormData();
                formData.append('action', 'save_sitemap');
                formData.append('pages', JSON.stringify(allPages));
                formData.append('csrf_token', csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showMessage('Sitemap erfolgreich gespeichert!', 'green');
                    await saveSettings();
                    updateTimestamp();
                } else {
                    showMessage(`Fehler: ${data.message || 'Unbekannter Fehler'}`, 'red');
                }
            } catch (error) {
                showMessage(`Netzwerkfehler: ${error.message}`, 'red');
            }
        });

        addRowBtn.addEventListener('click', () => {
            const newPage = {
                loc: './neue-seite.php',
                name: 'neue-seite.php',
                path: './',
                priority: 0.5,
                changefreq: 'weekly'
            };
            generalPagesData.push(newPage);
            renderGeneralTable();
        });

        // Sync Changes: General Table
        generalTableBody.addEventListener('change', (e) => {
            const row = e.target.closest('tr');
            if (!row) return;
            const originalLoc = row.dataset.loc; // Wir suchen nach der originalen Location, da sich diese ändern kann
            // Bei neu hinzugefügten Zeilen kann dataset.loc abweichen oder leer sein, hier simple Logik via Index wäre sicherer, aber Loc ist Key.
            // Für echte Robustheit müsste man IDs nutzen, aber für Sitemap Editor reicht das Mapping.

            // Re-Sync über Array-Index (da Reihenfolge gleich bleibt beim Rendern)
            const rowIndex = Array.from(generalTableBody.children).indexOf(row);
            if (rowIndex === -1 || !generalPagesData[rowIndex]) return;

            const newLoc = row.querySelector('.loc-input').value;
            generalPagesData[rowIndex].loc = newLoc;
            generalPagesData[rowIndex].priority = row.querySelector('.priority-input').value;
            generalPagesData[rowIndex].changefreq = row.querySelector('.changefreq-select').value;
            // Name/Path Update falls nötig
            generalPagesData[rowIndex].name = newLoc.includes('/') ? newLoc.substring(newLoc.lastIndexOf('/') + 1) : newLoc;
            generalPagesData[rowIndex].path = newLoc.includes('/') ? newLoc.substring(0, newLoc.lastIndexOf('/') + 1) : './';

            row.dataset.loc = newLoc;
        });

        // Sync Changes: Comic Table (Pagination beachten!)
        comicTableBody.addEventListener('change', (e) => {
            const row = e.target.closest('tr');
            if (!row) return;
            // Hier nutzen wir den Index im aktuellen Paginierungs-Slice
            const rowIndexInPage = Array.from(comicTableBody.children).indexOf(row);
            const globalIndex = (comicCurrentPage - 1) * COMIC_PER_PAGE + rowIndexInPage;

            if (fullComicPagesData[globalIndex]) {
                fullComicPagesData[globalIndex].priority = row.querySelector('.priority-input').value;
                fullComicPagesData[globalIndex].changefreq = row.querySelector('.changefreq-select').value;
            }
        });

        document.querySelector('body').addEventListener('click', e => {
            const btn = e.target.closest('.delete-row-btn');
            if (btn) {
                const row = btn.closest('tr');
                const tableId = row.closest('table').id;

                if (tableId === 'sitemap-table') {
                    if (confirm('Möchtest du diesen Eintrag wirklich entfernen?')) {
                        const rowIndex = Array.from(generalTableBody.children).indexOf(row);
                        if (rowIndex > -1) {
                            generalPagesData.splice(rowIndex, 1);
                            row.remove();
                        }
                    }
                } else {
                    // Comic Buttons sind disabled, aber sicher ist sicher
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

        // Init
        renderGeneralTable();
        renderComicTable();
        renderPagination();
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
