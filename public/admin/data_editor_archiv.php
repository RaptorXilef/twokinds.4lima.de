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
 * @version   3.5.0
 * @since     3.2.0 Korrektur des AJAX-Handlers zur korrekten Verarbeitung von FormData und CSRF-Token. Die ursprüngliche UI und PHP-Logik bleiben vollständig erhalten.
 * @since     3.3.0 Umstellung auf zentrale Pfad-Konstanten.
 * @since     3.4.0 Direkte Verwendung von Konstanten anstelle von temporären Variablen.
 * @since     3.5.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin_init.php';

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
        'data_editor_sitemap' => ['last_run_timestamp' => null],
        'data_editor_archiv' => ['last_run_timestamp' => null]
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
    if (!isset($settings['data_editor_archiv']))
        $settings['data_editor_archiv'] = $defaults['data_editor_archiv'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// --- Archiv-Daten Funktionen ---
function getChapterSortValue(array $chapter): array
{
    $rawChapterId = $chapter['chapterId'] ?? '';
    if ($rawChapterId === '')
        return [2, PHP_INT_MAX];
    $numericCheckId = str_replace(',', '.', $rawChapterId);
    if (is_numeric($numericCheckId))
        return [0, (float) $numericCheckId];
    return [1, $rawChapterId];
}

function loadArchiveChapters(string $path, bool $debugMode): array
{
    if (!file_exists($path) || filesize($path) === 0)
        return [];
    $content = file_get_contents($path);
    if ($content === false)
        return [];
    $data = json_decode($content, true);
    if (!is_array($data))
        return [];
    usort($data, function ($a, $b) {
        $sortA = getChapterSortValue($a);
        $sortB = getChapterSortValue($b);
        if ($sortA[0] !== $sortB[0])
            return $sortA[0] <=> $sortB[0];
        if ($sortA[0] === 1)
            return strnatcmp($sortA[1], $sortB[1]);
        return $sortA[1] <=> $sortB[1];
    });
    return $data;
}

function saveArchiveChapters(string $path, array $data, bool $debugMode): bool
{
    usort($data, function ($a, $b) {
        $sortA = getChapterSortValue($a);
        $sortB = getChapterSortValue($b);
        if ($sortA[0] !== $sortB[0])
            return $sortA[0] <=> $sortB[0];
        if ($sortA[0] === 1)
            return strnatcmp($sortA[1], $sortB[1]);
        return $sortA[1] <=> $sortB[1];
    });
    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonContent === false)
        return false;
    return file_put_contents($path, $jsonContent) !== false;
}

// --- AJAX-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Die Sicherheitsprüfung wird zuerst ausgeführt.
    verify_csrf_token();

    ob_end_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Unbekannte Aktion oder fehlende Daten.'];

    switch ($action) {
        case 'save_archive':
            $chaptersToSaveStr = $_POST['chapters'] ?? '[]';
            $chaptersToSave = json_decode($chaptersToSaveStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $response['message'] = 'Fehler: Die übermittelten Kapiteldaten sind kein gültiges JSON.';
                http_response_code(400);
            } elseif (saveArchiveChapters(ARCHIVE_CHAPTERS_JSON, $chaptersToSave, $debugMode)) {
                $response = ['success' => true, 'message' => 'Archiv-Daten erfolgreich in der JSON-Datei gespeichert!'];
            } else {
                $response['message'] = 'Fehler beim Speichern der Archiv-Daten.';
                http_response_code(500);
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings(CONFIG_GENERATOR_SETTINGS_JSON, $debugMode);
            $currentSettings['data_editor_archiv']['last_run_timestamp'] = time();
            if (saveGeneratorSettings(CONFIG_GENERATOR_SETTINGS_JSON, $currentSettings, $debugMode)) {
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


$settings = loadGeneratorSettings(CONFIG_GENERATOR_SETTINGS_JSON, $debugMode);
$archiveSettings = $settings['data_editor_archiv'];
$chapters = loadArchiveChapters(ARCHIVE_CHAPTERS_JSON, $debugMode);

$pageTitle = 'Adminbereich - Archiv Editor';
$pageHeader = 'Archiv Editor';
$robotsContent = 'noindex, nofollow';

$additionalScripts = <<<HTML
    <script nonce="{$nonce}" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script nonce="{$nonce}" src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
HTML;

include TEMPLATE_HEADER;
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($archiveSettings['last_run_timestamp']): ?>
                    <p class="status-message status-info">Letzte Speicherung am
                        <?php echo date('d.m.Y \u\m H:i:s', $archiveSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Archiv Editor</h2>
            <p>Verwalte hier die Kapitel für die Archiv-Seite. Einträge können hinzugefügt, bearbeitet und gelöscht
                werden.</p>
        </div>

        <div id="message-box" class="hidden-by-default"></div>

        <div class="sitemap-table-container">
            <table class="sitemap-table" id="archive-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titel</th>
                        <th>Beschreibung (Vorschau)</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows are rendered by JavaScript -->
                </tbody>
            </table>
        </div>

        <div id="save-confirmation-box" class="hidden-by-default"></div>
        <div id="fixed-buttons-container">
            <button id="add-row-btn" class="button"><i class="fas fa-plus-circle"></i> Neues Kapitel</button>
            <button id="save-all-btn" class="button"><i class="fas fa-save"></i> Änderungen speichern</button>
        </div>
    </div>
</article>

<!-- Edit Modal -->
<div id="edit-modal" class="modal hidden-by-default">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Kapitel bearbeiten</h2>
        <div class="form-group"><label for="modal-chapter-id">Kapitel ID:</label><input type="text"
                id="modal-chapter-id"></div>
        <div class="form-group"><label for="modal-title">Titel:</label><input type="text" id="modal-title"></div>
        <div class="form-group"><label for="modal-description">Beschreibung:</label><textarea
                id="modal-description"></textarea></div>
        <div class="modal-buttons">
            <button id="modal-save-btn" class="button">Speichern</button>
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
    }

    .sitemap-table th,
    .sitemap-table td {
        padding: 8px;
        border-bottom: 1px solid var(--missing-grid-border-color);
        text-align: left;
        vertical-align: middle;
    }

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

    #fixed-buttons-container {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
        gap: 10px;
    }

    .hidden-by-default {
        display: none;
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
        max-width: 1035px;
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
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
        box-sizing: border-box;
    }

    body.theme-night .form-group input,
    body.theme-night .form-group textarea {
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
        z-index: 99;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        let chaptersData = <?php echo json_encode($chapters, JSON_UNESCAPED_SLASHES); ?>;

        const tableBody = document.querySelector('#archive-table tbody');
        const saveAllBtn = document.getElementById('save-all-btn');
        const addRowBtn = document.getElementById('add-row-btn');
        const messageBox = document.getElementById('message-box');
        const saveConfirmationBox = document.getElementById('save-confirmation-box');
        const lastRunContainer = document.getElementById('last-run-container');

        const editModal = document.getElementById('edit-modal');
        const modalCloseBtn = editModal.querySelector('.close-button');
        const modalSaveBtn = document.getElementById('modal-save-btn');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        let activeEditIndex = null;

        $('#modal-description').summernote({
            placeholder: "Beschreibung hier eingeben...", tabsize: 2, height: 250,
            toolbar: [['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['insert', ['link']], ['view', ['codeview']]],
            callbacks: {
                onPaste: function (e) {
                    var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
                    var pastedData = clipboardData.getData('text/html');

                    if (pastedData) {
                        e.preventDefault();

                        const bodyMatch = /<body[^>]*>([\s\S]*)<\/body>/i.exec(pastedData);
                        let cleanedData = bodyMatch && bodyMatch[1] ? bodyMatch[1] : pastedData;

                        cleanedData = cleanedData.replace(/<!--[\s\S]*?-->/g, '');
                            cleanedData = cleanedData.replace(/<\/?\w+:[^>]*>/g, '');
                        cleanedData = cleanedData.replace(/<(meta|link|style)[\s\S]*?>/gi, '');

                        cleanedData = cleanedData.replace(/<(\w+)([^>]*)>/g, function (match, tagName, attrs) {
                            let preservedAttrs = '';
                            if (tagName.toLowerCase() === 'a') {
                                const hrefMatch = attrs.match(/href="([^"]*)"/i);
                                if (hrefMatch) {
                                    preservedAttrs = ` href="${hrefMatch[1]}" target="_blank"`;
                                }
                            }
                            return `<${tagName}${preservedAttrs}>`;
                        });

                        cleanedData = cleanedData.replace(/<b>/gi, '<strong>').replace(/<\/b>/gi, '</strong>');
                        cleanedData = cleanedData.replace(/<i>/gi, '<em>').replace(/<\/i>/gi, '</em>');
                        cleanedData = cleanedData.replace(/<\/?span>/gi, '');
                        cleanedData = cleanedData.replace(/<p>\s*&nbsp;\s*<\/p>/gi, '');
                        cleanedData = cleanedData.replace(/<p>\s*<\/p>/gi, '');

                        $('#modal-description').summernote('pasteHTML', cleanedData);
                    }
                }
            }
        });

        const renderRow = (chapter, index) => {
            const row = document.createElement('tr');
            row.dataset.index = index;
            const descPreview = new DOMParser().parseFromString(chapter.description || '', 'text/html').body;
            descPreview.querySelectorAll('script, style').forEach(el => el.remove());

            row.innerHTML = `
            <td>${chapter.chapterId || ''}</td>
            <td>${chapter.title || ''}</td>
            <td><div class="description-preview">${descPreview.innerHTML}</div></td>
            <td class="action-buttons">
                <button class="button edit-row-btn"><i class="fas fa-edit"></i></button>
                <button class="button delete-row-btn"><i class="fas fa-trash-alt"></i></button>
            </td>
        `;
            return row;
        };

        const renderTable = () => {
            tableBody.innerHTML = '';
            chaptersData.forEach((chapter, index) => tableBody.appendChild(renderRow(chapter, index)));
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
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('csrf_token', csrfToken);
            await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
        }

        saveAllBtn.addEventListener('click', async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'save_archive');
                formData.append('chapters', JSON.stringify(chaptersData));
                formData.append('csrf_token', csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                try {
                    const data = JSON.parse(responseText);
                    if (response.ok && data.success) {
                        showMessage(data.message, 'green', 5000, saveConfirmationBox);
                        messageBox.style.display = 'none';
                        await saveSettings();
                        updateTimestamp();
                    } else {
                        showMessage(`Fehler: ${data.message || 'Unbekannter Fehler'}`, 'red', 5000, saveConfirmationBox);
                    }
                } catch (e) {
                    throw new Error(`Ungültige JSON-Antwort vom Server: ${responseText}`);
                }
            } catch (error) {
                showMessage(`Netzwerkfehler: ${error.message}`, 'red', 10000, saveConfirmationBox);
            }
        });

        addRowBtn.addEventListener('click', () => {
            activeEditIndex = chaptersData.length;
            document.getElementById('modal-chapter-id').value = '';
            document.getElementById('modal-title').value = 'Neues Kapitel';
            $('#modal-description').summernote('code', '');
            editModal.style.display = 'flex';
        });

        tableBody.addEventListener('click', e => {
            const editBtn = e.target.closest('.edit-row-btn');
            if (editBtn) {
                const row = editBtn.closest('tr');
                activeEditIndex = parseInt(row.dataset.index, 10);
                const chapter = chaptersData[activeEditIndex];
                document.getElementById('modal-chapter-id').value = chapter.chapterId;
                document.getElementById('modal-title').value = chapter.title;
                $('#modal-description').summernote('code', chapter.description);
                editModal.style.display = 'flex';
            }

            const deleteBtn = e.target.closest('.delete-row-btn');
            if (deleteBtn) {
                if (confirm('Sind Sie sicher, dass Sie dieses Kapitel löschen möchten?')) {
                    const row = deleteBtn.closest('tr');
                    const index = parseInt(row.dataset.index, 10);
                    chaptersData.splice(index, 1);
                    renderTable();
                    showMessage('Kapitel zum Löschen vorgemerkt. Klicke auf "Änderungen speichern", um die Aktion abzuschließen.', 'orange', 10000);
                }
            }
        });

        modalSaveBtn.addEventListener('click', () => {
            const updatedChapter = {
                chapterId: document.getElementById('modal-chapter-id').value,
                title: document.getElementById('modal-title').value,
                description: $('#modal-description').summernote('code')
            };
            if (activeEditIndex !== null) {
                if (activeEditIndex >= chaptersData.length) {
                    chaptersData.push(updatedChapter);
                } else {
                    chaptersData[activeEditIndex] = updatedChapter;
                }
            }
            renderTable();
            editModal.style.display = 'none';
            activeEditIndex = null;
            showMessage('Änderung zwischengespeichert. Klicke auf "Änderungen speichern", um sie permanent zu machen.', 'orange', 10000);
        });

        modalCancelBtn.addEventListener('click', () => {
            editModal.style.display = 'none';
            activeEditIndex = null;
        });
        modalCloseBtn.addEventListener('click', () => modalCancelBtn.click());

        renderTable();
    });
</script>

<?php include TEMPLATE_FOOTER; ?>