<?php

/**
 * Administrationsseite zum asynchronen Hochladen von Comic-Bildern.
 *
 * @file      ROOT/public/admin/upload_image.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
* @since 5.0.0
 *    ARCHITEKTUR & CORE
 *    - Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *     - Vollständige Code-Bereinigung und Nutzung der neuesten Konstanten-Struktur.
 *
 *    FUNKTIONALITÄT
 *    - Konsistentes Speichern und Anzeigen der letzten Ausführung.
 *
 * @since 5.0.0
 * - refactor(UI): Inline-CSS entfernt und durch SCSS-Klassen (.drag-drop-zone, .image-comparison) ersetzt.
 * - refactor(Code): HTML-Struktur an Admin-Standard angepasst (#settings-and-actions-container).
 * - fix(JS): Modernisiertes JavaScript (async/await, fetch) und verbessertes Error-Handling.
 * - style(UX): Visuelles Feedback bei Drag & Drop und Upload-Fortschritt verbessert.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- VARIABLEN & KONFIGURATION ---
$tempDir = sys_get_temp_dir();

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = ['upload_image' => ['last_run_timestamp' => null]];
    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }
    $content = file_get_contents($filePath);
    $settings = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $defaults;
    }
    if (!isset($settings['upload_image'])) {
        $settings['upload_image'] = $defaults['upload_image'];
    }
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// Sicherstellen, dass die Upload-Verzeichnisse existieren
if (!is_dir(DIRECTORY_PUBLIC_IMG_COMIC_HIRES)) {
    mkdir(DIRECTORY_PUBLIC_IMG_COMIC_HIRES, 0777, true);
}
if (!is_dir(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES)) {
    mkdir(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES, 0777, true);
}

function shortenFilename($filename)
{
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    preg_match('/(\d{8})/', $filename, $matches);
    return (!empty($matches) ? $matches[0] : pathinfo($filename, PATHINFO_FILENAME)) . '.' . $extension;
}

function findExistingFileInDir($shortName, $dir)
{
    $baseName = pathinfo($shortName, PATHINFO_FILENAME);
    foreach (glob($dir . DIRECTORY_SEPARATOR . $baseName . '.*') as $file) {
        return $file;
    }
    return null;
}

// --- AJAX HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'Fehler beim Upload. Code: ' . $file['error']]);
            exit;
        }
        $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            echo json_encode(['status' => 'error', 'message' => 'Ungültiger Dateityp.']);
            exit;
        }

        $shortName = shortenFilename($file['name']);
        $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . uniqid() . '_' . $shortName;

        if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            list($width, $height) = getimagesize($tempFilePath);
            $targetDir = ($width >= 1800 && $height >= 1000) ? DIRECTORY_PUBLIC_IMG_COMIC_HIRES : DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;
            $existingFile = findExistingFileInDir($shortName, $targetDir);

            if ($existingFile !== null) {
                $_SESSION['pending_upload'][$shortName] = ['temp_file' => $tempFilePath, 'existing_file' => $existingFile, 'target_dir' => $targetDir];

                // Erzeuge eine korrekte, absolute URL für das existierende Bild
                $relativeExistingPath = str_replace(DIRECTORY_PUBLIC, '', $existingFile);
                $existingFileUrl = DIRECTORY_PUBLIC_URL . str_replace(DIRECTORY_SEPARATOR, '/', $relativeExistingPath);

                echo json_encode(['status' => 'confirmation_needed', 'short_name' => $shortName, 'existing_image_url' => $existingFileUrl, 'new_image_data_uri' => 'data:image/' . $imageFileType . ';base64,' . base64_encode(file_get_contents($tempFilePath))]);
            } else {
                $targetPath = $targetDir . DIRECTORY_SEPARATOR . $shortName;
                if (rename($tempFilePath, $targetPath)) {
                    echo json_encode(['status' => 'success', 'message' => "Datei '{$shortName}' erfolgreich hochgeladen."]);
                } else {
                    if (file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }
                    echo json_encode(['status' => 'error', 'message' => "Fehler beim Speichern von '{$shortName}'."]);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => "Fehler beim Verschieben von '{$file['name']}'."]);
        }
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $response = ['success' => false, 'message' => ''];
        $settingsFile = Path::getConfigPath('config_generator_settings.json');

        switch ($action) {
            case 'confirm_overwrite':
                $shortName = $_POST['short_name'] ?? null;
                $decision = $_POST['decision'] ?? null;
                if (!$shortName || !isset($_SESSION['pending_upload'][$shortName])) {
                    echo json_encode(['status' => 'error', 'message' => 'Ungültige Anfrage.']);
                    exit;
                }
                $uploadData = $_SESSION['pending_upload'][$shortName];
                unset($_SESSION['pending_upload'][$shortName]);
                if ($decision === 'yes') {
                    if (file_exists($uploadData['existing_file'])) {
                        unlink($uploadData['existing_file']);
                    }
                    if (rename($uploadData['temp_file'], $uploadData['target_dir'] . DIRECTORY_SEPARATOR . $shortName)) {
                        echo json_encode(['status' => 'success', 'message' => "Datei '{$shortName}' wurde überschrieben."]);
                    } else {
                        if (file_exists($uploadData['temp_file'])) {
                            unlink($uploadData['temp_file']);
                        }
                        echo json_encode(['status' => 'error', 'message' => "Fehler beim Überschreiben von '{$shortName}'."]);
                    }
                } else {
                    if (file_exists($uploadData['temp_file'])) {
                        unlink($uploadData['temp_file']);
                    }
                    echo json_encode(['status' => 'info', 'message' => "Upload von '{$shortName}' wurde abgebrochen."]);
                }
                exit;

            case 'save_settings':
                $currentSettings = loadGeneratorSettings($settingsFile, $debugMode);
                $currentSettings['upload_image']['last_run_timestamp'] = time();
                if (saveGeneratorSettings($settingsFile, $currentSettings, $debugMode)) {
                    $response['success'] = true;
                }
                break;
        }
        echo json_encode($response);
        exit;
    }
}

$settings = loadGeneratorSettings(Path::getConfigPath('config_generator_settings.json'), $debugMode);
$uploadSettings = $settings['upload_image'];
$pageTitle = 'Adminbereich - Bild-Upload';
$pageHeader = 'Bild-Upload';
$siteDescription = 'Seite zum hochladen der Comicseiten auf den Server (ohne FTP).';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="content-section">
        <!-- UI HEADER & ACTIONS -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($uploadSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">Letzter Upload am
                        <?php echo date('d.m.Y \u\m H:i:s', $uploadSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Bild-Upload</h2>
            <p>Ziehe Bilder per Drag & Drop in den Kasten oder wähle sie über den Button aus. Das Skript erkennt automatisch, ob es sich um eine High-Res- oder Low-Res-Version handelt.</p>
        </div>

        <div id="statusMessages"></div>

        <div id="uploadContainer">
            <form id="uploadForm">
                <!-- Dropzone nutzt jetzt SCSS Klasse -->
                <div id="dropZone" class="drag-drop-zone">
                    <p class="instructions">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Dateien hierher ziehen, oder klicken
                    </p>
                    <input type="file" id="fileInput" multiple class="hidden-by-default">
                    <label for="fileInput" class="button">Bilder auswählen</label>
                </div>

                <!-- Dateiliste -->
                <div id="fileList" class="upload-file-list"></div>

                <div style="text-align: right;">
                    <button type="submit" id="uploadButton" class="button button-green upload-button" disabled>
                        <i class="fas fa-upload"></i> Upload starten
                    </button>
                </div>
            </form>
        </div>

        <!-- Notification Box für Cache Update -->
        <div id="cache-update-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Upload abgeschlossen</h4>
            <p>
                Die Bilder wurden erfolgreich hochgeladen. Bitte wähle den nächsten Schritt:
            </p>

            <div class="next-steps-actions">
                <!-- Option 1: Thumbnails -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_thumbnail.php'; ?>"
                   class="button button-orange" target="_blank">
                   <i class="fas fa-images"></i> 1. Thumbnails generieren
                </a>

                <!-- Option 2: Cache -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting.php?autostart=lowres,hires'; ?>"
                   class="button button-blue">
                   <i class="fas fa-sync"></i> 2. Cache aktualisieren
                </a>
            </div>
        </div>

        <!-- Confirm Modal (Advanced Layout) -->
        <div id="confirmationModal" class="modal hidden-by-default">
            <div class="modal-content modal-advanced-layout">
                <div class="modal-header-wrapper">
                    <h2 id="confirmationHeader">Bestätigung erforderlich</h2>
                    <span class="close-button">&times;</span>
                </div>

                <div class="modal-scroll-content">
                    <p id="confirmationMessage"></p>
                    <div class="image-comparison">
                        <div class="image-box">
                            <h3>Bestehendes Bild</h3>
                            <img id="existingImage" src="" alt="Bestehendes Bild" loading="lazy">
                        </div>
                        <div class="image-box">
                            <h3>Neues Bild</h3>
                            <img id="newImage" src="" alt="Neues Bild" loading="lazy">
                        </div>
                    </div>
                </div>

                <div class="modal-footer-actions">
                    <div class="modal-buttons">
                        <button id="confirmOverwrite" class="button button-green">Ja, überschreiben</button>
                        <button id="cancelOverwrite" class="button delete">Nein, abbrechen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';

        // UI Refs
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const statusMessages = document.getElementById('statusMessages');

        const confirmationModal = document.getElementById('confirmationModal');
        const closeModalBtn = confirmationModal.querySelector('.close-button');
        const confirmOverwriteButton = document.getElementById('confirmOverwrite');
        const cancelOverwriteButton = document.getElementById('cancelOverwrite');
        const existingImage = document.getElementById('existingImage');
        const newImage = document.getElementById('newImage');
        const confirmationMessage = document.getElementById('confirmationMessage');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');
        const lastRunContainer = document.getElementById('last-run-container');

        let filesToUpload = [];

        // --- Drag & Drop Logic ---
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                handleFiles(e.dataTransfer.files);
            }
        });

        // Klick auf Dropzone (außer auf den Button) öffnet FileDialog
        dropZone.addEventListener('click', (e) => {
            if (e.target !== fileInput && !e.target.closest('label')) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', () => handleFiles(fileInput.files));

        function handleFiles(files) {
            // Files zu Array konvertieren für einfachere Handhabung
            const newFiles = Array.from(files);

            // Duplikate vermeiden (optional, hier einfach anhängen)
            filesToUpload = [...filesToUpload, ...newFiles];
            updateFileList();
        }

        function updateFileList() {
            if (filesToUpload.length > 0) {
                fileList.innerHTML = `<strong>Ausgewählte Dateien (${filesToUpload.length}):</strong><br> ` +
                filesToUpload.map(f => `<span>${escapeHtml(f.name)}</span>`).join(', ');
                fileList.style.display = 'block';
                uploadButton.disabled = false;
            } else {
                fileList.innerHTML = '';
                fileList.style.display = 'none';
                uploadButton.disabled = true;
            }
        }

        // --- Upload Logic ---
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (filesToUpload.length === 0) return;

            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Lade hoch...';
            cacheUpdateNotification.style.display = 'none';
            statusMessages.innerHTML = ''; // Alte Nachrichten löschen

            let uploadSuccessCount = 0;
            let fileCounter = 0;

            // Sequentieller Upload, um Serverlast und Modal-Konflikte zu vermeiden
            for (const file of filesToUpload) {
                try {
                    const result = await uploadFile(file);
                    if (result && result.status === 'success') {
                        uploadSuccessCount++;
                        // Kleine Erfolgsmeldung für jede Datei
                        addStatusMessage(result.message, 'success', 2000);
                    } else if (result && result.status === 'info') {
                        // Übersprungen
                        addStatusMessage(result.message, 'info', 3000);
                    }
                    fileCounter++;
                } catch (err) {
                    console.error("Upload Fehler:", err);
                    addStatusMessage(`Fehler bei ${file.name}: ${err.message}`, 'error');
                }
            }

            if (fileCounter > 0) {
                addStatusMessage(`Upload-Prozess abgeschlossen. ${uploadSuccessCount} von ${filesToUpload.length} Dateien erfolgreich.`, "info");
            }

            // Reset
            filesToUpload = [];
            updateFileList();
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload starten';
            fileInput.value = ''; // Reset Input

            if (uploadSuccessCount > 0) {
                await saveSettings();
                updateTimestamp();
                cacheUpdateNotification.classList.remove('hidden-by-default');
                cacheUpdateNotification.style.display = 'block';
            }
        });

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('', { // Post an sich selbst
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`HTTP Fehler: ${response.status}`);

            const result = await response.json();

            if (result.status === 'confirmation_needed') {
                return await handleConfirmation(result);
            } else {
                if (result.status === 'error') {
                    addStatusMessage(result.message, 'error');
                }
                return result;
            }
        }

        // --- Confirmation Modal ---
        async function handleConfirmation(data) {
            return new Promise(resolve => {
                confirmationModal.style.display = 'flex';
                confirmationMessage.innerHTML = `Ein Bild mit dem Namen <strong>${escapeHtml(data.short_name)}</strong> existiert bereits. Soll es überschrieben werden?`;

                // Bilder setzen
                existingImage.src = data.existing_image_url;
                newImage.src = data.new_image_data_uri;

                const cleanup = () => {
                    confirmationModal.style.display = 'none';
                    // Event Listener entfernen (clean way would be named functions, but simple overwrite works here because promises are sequential)
                    confirmOverwriteButton.onclick = null;
                    cancelOverwriteButton.onclick = null;
                    closeModalBtn.onclick = null;
                };

                const handleDecision = async (decision) => {
                    cleanup();

                    const formData = new FormData();
                    formData.append('action', 'confirm_overwrite');
                    formData.append('short_name', data.short_name);
                    formData.append('decision', decision);
                    formData.append('csrf_token', csrfToken);

                    try {
                        const response = await fetch('', { method: 'POST', body: formData });
                        const result = await response.json();

                        if (result.status === 'error') {
                            addStatusMessage(result.message, 'error');
                        } else {
                            addStatusMessage(result.message, result.status === 'success' ? 'success' : 'info');
                        }
                        resolve(result);
                    } catch (e) {
                        resolve({status: 'error', message: e.message});
                    }
                };

                confirmOverwriteButton.onclick = () => handleDecision('yes');
                cancelOverwriteButton.onclick = () => handleDecision('no');
                closeModalBtn.onclick = () => handleDecision('no');

                // Klick außerhalb schließt Modal (optional)
                // window.onclick logic interfere with other modals potentially, keeping it scoped strictly here
            });
        }

        // --- Helper ---
        async function saveSettings() {
            try {
                const fd = new FormData();
                fd.append('action', 'save_settings');
                fd.append('csrf_token', csrfToken);
                await fetch('', { method: 'POST', body: fd });
            } catch(e) { console.error(e); }
        }

        function updateTimestamp() {
            const now = new Date();
            const formattedDate = now.toLocaleDateString('de-DE') + ' ' + now.toLocaleTimeString('de-DE');
            let pElement = lastRunContainer.querySelector('.status-message');
            if (!pElement) {
                pElement = document.createElement('p');
                pElement.className = 'status-message status-info';
                lastRunContainer.appendChild(pElement);
            }
            pElement.innerHTML = `Letzter Upload am ${formattedDate} Uhr.`;
        }

        function addStatusMessage(message, type, autoHideDuration = 0) {
            const messageDiv = document.createElement('div');
            // Mapping CSS classes
            let cssClass = 'status-info';
            if (type === 'success' || type === 'green') cssClass = 'status-green';
            if (type === 'error' || type === 'red') cssClass = 'status-red';
            if (type === 'orange') cssClass = 'status-orange';

            messageDiv.className = `status-message ${cssClass}`;
            messageDiv.innerHTML = `<p>${message}</p>`; // p wrapper for style consistency

            // Oben einfügen
            statusMessages.prepend(messageDiv);

            if (autoHideDuration > 0) {
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => messageDiv.remove(), 500);
                }, autoHideDuration);
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        updateFileList();
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
