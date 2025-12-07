<?php

/**
 * Administrationsseite zum Bearbeiten der archive_chapters.json.
 *
 * @file      ROOT/public/admin/data_editor_archiv.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 2.0.0 Korrektur des AJAX-Handlers zur korrekten Verarbeitung von FormData und CSRF-Token. Die ursprüngliche UI und PHP-Logik bleiben vollständig erhalten.
 *  - Umstellung auf zentrale Pfad-Konstanten.
 *  - Direkte Verwendung von Konstanten anstelle von temporären Variablen.
 *  - Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 *  - Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 *
 * @since 5.0.0
 * - refactor(UI): Inline-CSS entfernt und auf SCSS 7-1 Architektur umgestellt.
 * - feat(Filter): Live-Suche nach ID, Titel und Beschreibung hinzugefügt.
 * - feat(Pagination): Client-seitige Paginierung implementiert.
 * - style(Layout): Layout an data_editor_comic.php angeglichen (Sticky Footer im Modal, Icon-Buttons).
 * - fix(JS): Robustere JSON-Übergabe an JavaScript (verhindert "null is not iterable" Fehler).
 * - fix(Data): UTF-8 Handling beim Laden/Speichern verbessert.
 * - fix(JS): Spread-Syntax entfernt und JSON-Initialisierung gehärtet (Fix für "Unexpected token .").
 * - fix(JS/PHP): Robustere JSON-Datenübergabe (UTF-8) und JS-Syntax-Fixes (.slice statt Spread).
 * - fix(UI): Zeilenumbruch vor Klammern im Titel und konfigurierbare Textkürzung.
 * - fix(UI): HTML-Rendering in Tabellenvorschau aktiviert (Links anklickbar), wenn Kürzung inaktiv.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
// Falls noch nicht definiert, setze Standardwert (False = Volle Beschreibung anzeigen & HTML erlauben)
if (!defined('TRUNCATE_ARCHIVE_DESCRIPTION')) {
    define('TRUNCATE_ARCHIVE_DESCRIPTION', false);
}

// === BACKEND LOGIK ===

function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = ['data_editor_archiv' => ['last_run_timestamp' => null]];
    if (!file_exists($filePath)) {
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }
    $content = file_get_contents($filePath);
    $settings = json_decode($content, true);
    return (json_last_error() !== JSON_ERROR_NONE || !isset($settings['data_editor_archiv'])) ? $defaults : $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    return file_put_contents($filePath, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

function getChapterSortValue(array $chapter): array
{
    $rawChapterId = $chapter['chapterId'] ?? '';
    if ($rawChapterId === '') {
        return [2, PHP_INT_MAX];
    }
    $numericCheckId = str_replace(',', '.', $rawChapterId);
    if (is_numeric($numericCheckId)) {
        return [0, (float) $numericCheckId];
    }
    return [1, $rawChapterId];
}

function loadArchiveChapters(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);

    if (!is_array($data)) {
        if ($debugMode) {
            error_log("[Archiv Editor] JSON Decode Fehler: " . json_last_error_msg());
        }
        return [];
    }

    // Sortierung
    usort($data, function ($a, $b) {
        $sortA = getChapterSortValue($a);
        $sortB = getChapterSortValue($b);
        if ($sortA[0] !== $sortB[0]) {
            return $sortA[0] <=> $sortB[0];
        }
        if ($sortA[0] === 1) {
            return strnatcmp($sortA[1], $sortB[1]);
        }
        return $sortA[1] <=> $sortB[1];
    });
    return $data;
}

function saveArchiveChapters(string $path, array $data, bool $debugMode): bool
{
    usort($data, function ($a, $b) {
        $sortA = getChapterSortValue($a);
        $sortB = getChapterSortValue($b);
        if ($sortA[0] !== $sortB[0]) {
            return $sortA[0] <=> $sortB[0];
        }
        if ($sortA[0] === 1) {
            return strnatcmp($sortA[1], $sortB[1]);
        }
        return $sortA[1] <=> $sortB[1];
    });

    // WICHTIG: JSON_UNESCAPED_UNICODE ist essentiell für deutsche Texte!
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $jsonContent) !== false;
}

// --- AJAX-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Unbekannte Aktion.'];

    $archiveChaptersJsonPath = Path::getDataPath('archive_chapters.json');
    $generatorSettingsJsonPath = Path::getConfigPath('config_generator_settings.json');

    switch ($action) {
        case 'save_archive':
            $chaptersToSaveStr = $_POST['chapters'] ?? '[]';
            $chaptersToSave = json_decode($chaptersToSaveStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $response['message'] = 'Ungültiges JSON-Format empfangen.';
                http_response_code(400);
            } elseif (saveArchiveChapters($archiveChaptersJsonPath, $chaptersToSave, $debugMode)) {
                $response = ['success' => true, 'message' => 'Archiv-Daten erfolgreich gespeichert!'];
            } else {
                $response['message'] = 'Schreibfehler beim Speichern der Datei.';
                http_response_code(500);
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings($generatorSettingsJsonPath, $debugMode);
            $currentSettings['data_editor_archiv']['last_run_timestamp'] = time();
            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            } else {
                $response['message'] = 'Fehler beim Speichern der Einstellungen.';
            }
            break;
    }
    echo json_encode($response);
    exit;
}

// === DATEN LADEN ===
$settings = loadGeneratorSettings(Path::getConfigPath('config_generator_settings.json'), $debugMode);
$archiveSettings = $settings['data_editor_archiv'];
$chapters = loadArchiveChapters(Path::getDataPath('archive_chapters.json'), $debugMode);

// WICHTIG: Sicheres Encoding für JS-Übergabe
$chaptersJson = json_encode($chapters, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

if ($chaptersJson === false) {
    if ($debugMode) {
        error_log("[Archiv Editor] JSON Encode Fehler: " . json_last_error_msg());
    }
    $chaptersJson = '[]';
}

// === UI VARIABLEN ===
$pageTitle = 'Adminbereich - Archiv Editor';
$pageHeader = 'Archiv Editor';
$robotsContent = 'noindex, nofollow';

$itemsPerPage = defined('ENTRIES_PER_PAGE_ARCHIVE') ? ENTRIES_PER_PAGE_ARCHIVE : 50;
// Übergabe der Konstante an JS (Bool -> String 'true'/'false')
$truncateDesc = TRUNCATE_ARCHIVE_DESCRIPTION ? 'true' : 'false';

$additionalScripts = <<<HTML
    <script nonce="{$nonce}" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet" nonce="{$nonce}">
    <script nonce="{$nonce}" src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
HTML;

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if (!empty($archiveSettings['last_run_timestamp'])) : ?>
                    <p class="status-message status-info">Letzte Speicherung am
                        <?php echo date('d.m.Y \u\m H:i:s', $archiveSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Archiv Editor</h2>
            <p>Verwalte hier die Kapitelübersicht. Neue Kapitel werden standardmäßig am Ende der Liste angefügt, aber beim Speichern automatisch nach ID sortiert.</p>
        </div>

        <!-- FILTER FORM -->
        <div class="filter-form">
            <fieldset>
                <legend>Filter</legend>
                <div class="filter-controls center-filter">
                    <div class="search-wrapper">
                        <input type="text" id="search-input" placeholder="Suchen nach ID, Titel oder Inhalt...">
                        <button id="clear-search-btn" type="button" title="Suche leeren" style="display: none;">&times;</button>
                    </div>
                </div>
            </fieldset>
        </div>

        <!-- OBERE ACTION BAR -->
        <div class="table-controls actions-bar">
            <div class="top-actions">
                <button id="add-row-btn-top" class="button"><i class="fas fa-plus-circle"></i> Neues Kapitel</button>
                <button id="save-all-btn-top" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
            </div>
            <div class="marker-legend-group">
                <div class="marker-legend">
                    <small>Zeigt <?php echo $itemsPerPage; ?> Einträge pro Seite.</small>
                </div>
            </div>
        </div>

        <div id="message-box-top" class="hidden-by-default"></div>

        <!-- TABELLE -->
        <div class="sitemap-table-container">
            <table class="admin-table archive-editor-table" id="archive-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titel</th>
                        <th>Beschreibung (Vorschau)</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Wird per JS gefüllt -->
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <div class="pagination"></div>

        <div id="save-confirmation-box" class="hidden-by-default"></div>

        <!-- UNTERE BUTTONS -->
        <div id="fixed-buttons-container">
            <button id="add-row-btn" class="button"><i class="fas fa-plus-circle"></i> Neues Kapitel</button>
            <button id="save-all-btn" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
        </div>

        <div id="message-box" class="hidden-by-default"></div>
    </div>
</article>

<!-- EDIT MODAL (Advanced Layout) -->
<div id="edit-modal" class="modal hidden-by-default">
    <div class="modal-content modal-advanced-layout">
        <div class="modal-header-wrapper">
            <h2 id="modal-title-header">Kapitel bearbeiten</h2>
            <button class="modal-close close-button" aria-label="Schließen">&times;</button>
        </div>

        <div class="modal-scroll-content">
            <div class="form-group">
                <label for="modal-chapter-id">Kapitel ID (Sortierschlüssel):</label>
                <input type="text" id="modal-chapter-id" placeholder="z.B. 1, 1.5, 20">
            </div>
            <div class="form-group">
                <label for="modal-title">Titel:</label>
                <input type="text" id="modal-title" placeholder="Kapitel Name">
            </div>
            <div class="form-group">
                <label for="modal-description">Beschreibung:</label>
                <textarea id="modal-description"></textarea>
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
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';

    // Konstante aus PHP
    const TRUNCATE_DESC = <?php echo $truncateDesc; ?>;

    // FIX: JSON-Initialisierung gehärtet und sicher gemacht
    let rawData;
    try {
        rawData = <?php echo $chaptersJson ?: '[]'; ?>;
    } catch (e) {
        console.error("JSON Parse Fehler beim Initialisieren:", e);
        rawData = [];
    }

    let chaptersData = Array.isArray(rawData) ? rawData : [];

    // FIX: .slice() statt Spread Operator [...x]
    let filteredChapters = chaptersData.slice();

    // UI Referenzen
    const tableBody = document.querySelector('#archive-table tbody');
    const paginationContainer = document.querySelector('.pagination');
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');

    const saveButtons = [document.getElementById('save-all-btn'), document.getElementById('save-all-btn-top')];
    const addButtons = [document.getElementById('add-row-btn'), document.getElementById('add-row-btn-top')];
    const messageBoxes = [document.getElementById('message-box'), document.getElementById('message-box-top')];
    const lastRunContainer = document.getElementById('last-run-container');

    // Modal Referenzen
    const editModal = document.getElementById('edit-modal');
    const modalCloseBtns = [editModal.querySelector('.close-button'), document.getElementById('modal-cancel-btn')];
    const modalSaveBtn = document.getElementById('modal-save-btn');

    // Nutze jQuery für Summernote-Zugriff
    const descTextarea = $('#modal-description');

    const modalInputs = {
        id: document.getElementById('modal-chapter-id'),
        title: document.getElementById('modal-title')
    };

    // State
    const ITEMS_PER_PAGE = <?php echo $itemsPerPage; ?>;
    let currentPage = 1;
    let activeEditIndex = null;

    // --- SUMMERNOTE INIT ---
    if (typeof $ !== 'undefined' && typeof $.fn.summernote !== 'undefined') {
        descTextarea.summernote({
            placeholder: "Beschreibung hier eingeben...",
            tabsize: 2,
            height: 250,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ],
            callbacks: {
                onPaste: function (e) {
                    var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
                    var pastedData = clipboardData.getData('text/html');
                    if (pastedData) {
                        e.preventDefault();
                        const bodyMatch = /<body[^>]*>([\s\S]*)<\/body>/i.exec(pastedData);
                        let cleanedData = bodyMatch && bodyMatch[1] ? bodyMatch[1] : pastedData;
                        cleanedData = cleanedData.replace(/<!--[\s\S]*?-->/g, '')
                                                 .replace(/<\/?\w+:[^>]*>/g, '')
                                                 .replace(/<(meta|link|style)[\s\S]*?>/gi, '');
                        descTextarea.summernote('pasteHTML', cleanedData);
                    }
                }
            }
        });
    } else {
        console.error("Summernote konnte nicht geladen werden.");
    }

    // --- TABELLEN RENDER LOGIK ---
    const renderTable = () => {
        tableBody.innerHTML = '';

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end = start + ITEMS_PER_PAGE;
        const pageItems = filteredChapters.slice(start, end);

        if (pageItems.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 20px;">Keine Kapitel gefunden.</td></tr>';
            paginationContainer.innerHTML = '';
            return;
        }

        pageItems.forEach(chapter => {
            const realIndex = chaptersData.indexOf(chapter);
            const row = document.createElement('tr');
            row.dataset.index = realIndex;

            // --- HTML PREVIEW LOGIK ---
            // Wir erstellen ein temporäres Element, um das HTML zu parsen und zu bereinigen
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = chapter.description || '';

            // Sicherheit: Entferne Skripte, Styles, iFrames (alles was Layout/Sicherheit gefährdet)
            tempDiv.querySelectorAll('script, style, iframe, object, embed, meta').forEach(el => el.remove());

            let displayContent = '';

            if (TRUNCATE_DESC) {
                // FALL: KÜRZUNG AKTIV
                // Wir nutzen nur reinen Text, da abgeschnittenes HTML kaputt gehen kann.
                let text = tempDiv.textContent || tempDiv.innerText || '';
                if (text.length > 100) {
                    text = text.substring(0, 100) + '...';
                }
                // Text sicher für HTML ausgeben
                displayContent = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            } else {
                // FALL: VOLLE ANSICHT (HTML ERLAUBT)
                // Wir stellen sicher, dass alle Links in neuem Tab öffnen, damit man nicht aus dem Admin fliegt
                tempDiv.querySelectorAll('a').forEach(a => {
                    a.setAttribute('target', '_blank');
                    a.setAttribute('rel', 'noopener noreferrer');
                });
                displayContent = tempDiv.innerHTML;
            }

            if (!displayContent || displayContent.trim() === '') {
                displayContent = '<em>Keine Beschreibung</em>';
            }

            // FEAT 1: Titel formatieren (Umbruch vor Klammer)
            let displayTitle = chapter.title || '';
            displayTitle = displayTitle.replace('(', '<br>(');

            row.innerHTML = `
                <td>${chapter.chapterId || ''}</td>
                <td>${displayTitle}</td>
                <td><div class="description-preview">${displayContent}</div></td>
                <td class="actions-cell">
                    <button class="button edit-row-btn" title="Bearbeiten"><i class="fas fa-edit"></i></button>
                    <button class="button delete-row-btn" title="Löschen"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
            tableBody.appendChild(row);
        });

        renderPagination();
    };

    const renderPagination = () => {
        const totalPages = Math.ceil(filteredChapters.length / ITEMS_PER_PAGE);
        paginationContainer.innerHTML = '';

        if (totalPages <= 1) return;

        let html = '';
        if (currentPage > 1) {
            html += `<a href="#" data-page="${currentPage - 1}">&laquo;</a>`;
        }

        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) html += `<a href="#" data-page="1">1</a><span>...</span>`;

        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) html += `<span class="current-page">${i}</span>`;
            else html += `<a href="#" data-page="${i}">${i}</a>`;
        }

        if (endPage < totalPages) html += `<span>...</span><a href="#" data-page="${totalPages}">${totalPages}</a>`;

        if (currentPage < totalPages) {
            html += `<a href="#" data-page="${currentPage + 1}">&raquo;</a>`;
        }

        paginationContainer.innerHTML = html;
    };

    // --- EVENT LISTENER: FILTER ---
    searchInput.addEventListener('input', () => {
        const term = searchInput.value.toLowerCase().trim();
        clearSearchBtn.style.display = term ? 'inline-block' : 'none';

        if (!term) {
            filteredChapters = chaptersData.slice(); // Safe copy
        } else {
            filteredChapters = chaptersData.filter(ch => {
                const id = (ch.chapterId || '').toLowerCase();
                const title = (ch.title || '').toLowerCase();
                const desc = (ch.description || '').toLowerCase();
                return id.includes(term) || title.includes(term) || desc.includes(term);
            });
        }
        currentPage = 1;
        renderTable();
    });

    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    });

    // --- EVENT LISTENER: PAGINATION ---
    paginationContainer.addEventListener('click', (e) => {
        if (e.target.tagName === 'A' && e.target.dataset.page) {
            e.preventDefault();
            currentPage = parseInt(e.target.dataset.page, 10);
            renderTable();
        }
    });

    // --- EVENT LISTENER: MODAL ---
    function openModal(index = null) {
        activeEditIndex = index;
        if (index !== null && chaptersData[index]) {
            const ch = chaptersData[index];
            modalInputs.id.value = ch.chapterId || '';
            modalInputs.title.value = ch.title || '';
            descTextarea.summernote('code', ch.description || '');
            document.getElementById('modal-title-header').textContent = `Kapitel bearbeiten (${ch.chapterId})`;
        } else {
            // Neu
            modalInputs.id.value = '';
            modalInputs.title.value = '';
            descTextarea.summernote('code', '');
            document.getElementById('modal-title-header').textContent = 'Neues Kapitel erstellen';
        }
        editModal.style.display = 'flex';
    }

    function closeModal() {
        editModal.style.display = 'none';
        activeEditIndex = null;
    }

    modalCloseBtns.forEach(btn => btn.addEventListener('click', closeModal));

    modalSaveBtn.addEventListener('click', () => {
        const newChapter = {
            chapterId: modalInputs.id.value.trim(),
            title: modalInputs.title.value.trim(),
            description: descTextarea.summernote('code')
        };

        if (!newChapter.chapterId) {
            alert('Bitte eine Kapitel-ID eingeben.');
            return;
        }

        if (activeEditIndex !== null) {
            chaptersData[activeEditIndex] = newChapter;
        } else {
            chaptersData.push(newChapter);
        }

        searchInput.dispatchEvent(new Event('input'));
        closeModal();
        showMessage('Änderung übernommen. Bitte "Speichern" klicken.', 'orange');
    });

    addButtons.forEach(btn => btn.addEventListener('click', () => openModal(null)));

    // --- EVENT LISTENER: TABELLE (Edit/Delete) ---
    tableBody.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;

        const row = btn.closest('tr');
        const index = parseInt(row.dataset.index, 10);

        if (btn.classList.contains('edit-row-btn')) {
            openModal(index);
        } else if (btn.classList.contains('delete-row-btn')) {
            if (confirm('Möchtest du dieses Kapitel wirklich löschen?')) {
                chaptersData.splice(index, 1);
                searchInput.dispatchEvent(new Event('input'));
                showMessage('Kapitel gelöscht. Bitte "Speichern" klicken.', 'orange');
            }
        }
    });

    // --- SPEICHERN & NACHRICHTEN ---
    function showMessage(msg, type, duration = 5000) {
        messageBoxes.forEach(box => {
            if(!box) return;
            box.textContent = msg;
            box.className = `status-message status-${type}`;
            box.style.display = 'block';
            if (duration) setTimeout(() => { box.style.display = 'none'; }, duration);
        });
    }

    async function handleSave() {
        try {
            const formData = new FormData();
            formData.append('action', 'save_archive');
            formData.append('chapters', JSON.stringify(chaptersData));
            formData.append('csrf_token', csrfToken);

            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const text = await res.text();

            try {
                const data = JSON.parse(text);
                if (res.ok && data.success) {
                    showMessage(data.message, 'green');
                    updateTimestamp();
                    saveSettings();
                } else {
                    showMessage(data.message || 'Fehler beim Speichern', 'red');
                }
            } catch (jsonErr) {
                console.error("Server Antwort:", text);
                showMessage('Ungültige Server-Antwort: ' + text.substring(0, 100), 'red');
            }
        } catch (err) {
            showMessage('Netzwerkfehler: ' + err.message, 'red');
        }
    }

    async function saveSettings() {
        const fd = new FormData();
        fd.append('action', 'save_settings');
        fd.append('csrf_token', csrfToken);
        await fetch(window.location.href, { method: 'POST', body: fd });
    }

    function updateTimestamp() {
        const now = new Date();
        const str = `Letzte Speicherung am ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr.`;
        if (lastRunContainer) {
            let p = lastRunContainer.querySelector('p');
            if(!p) {
                p = document.createElement('p');
                p.className = 'status-message status-info';
                lastRunContainer.appendChild(p);
            }
            p.textContent = str;
        }
    }

    saveButtons.forEach(btn => btn.addEventListener('click', handleSave));

    // Initial render
    renderTable();
});
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
