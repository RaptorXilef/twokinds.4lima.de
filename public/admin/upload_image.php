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
 * @since 2.0.0 - 4.0.0
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
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> upload_image).
 * - fix(UI): Fallback-Anzeige für fehlenden Zeitstempel.
 * - feat(Settings): Konfigurierbare Schwellenwerte für High-Res/Low-Res Erkennung.
 * - feat(Logic): Optionale manuelle Zuweisung (High/Low) statt Automatik implementiert.
 * - feat(UI): Neues Modal zur manuellen Auswahl der Auflösungskategorie.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- VARIABLEN & KONFIGURATION ---
$tempDir = sys_get_temp_dir();
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, string $username): array
{
    $defaults = [
        'last_run_timestamp' => null,
        'auto_detect_hires' => true,
        'hires_threshold_width' => 1800,
        'hires_threshold_height' => 1000
    ];

    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode(['users' => []], JSON_PRETTY_PRINT));
        return $defaults;
    }

    $content = file_get_contents($filePath);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return $defaults;
    }

    // Spezifische Einstellungen für den User laden
    $userSettings = $data['users'][$username]['upload_image'] ?? [];

    // Merge mit Defaults
    return array_replace_recursive($defaults, $userSettings);
}

function saveGeneratorSettings(string $filePath, string $username, array $newSettings): bool
{
    $data = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        }
    }

    if (!isset($data['users'])) {
        $data['users'] = [];
    }
    if (!isset($data['users'][$username])) {
        $data['users'][$username] = [];
    }

    $currentData = $data['users'][$username]['upload_image'] ?? [];
    $data['users'][$username]['upload_image'] = array_replace_recursive($currentData, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// Sicherstellen, dass die Upload-Verzeichnisse existieren
if (!is_dir(DIRECTORY_PUBLIC_IMG_COMIC_HIRES)) {
    mkdir(DIRECTORY_PUBLIC_IMG_COMIC_HIRES, 0777, true);
}
if (!is_dir(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES)) {
    mkdir(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES, 0777, true);
}

function shortenFilename(string $filename): string
{
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    preg_match('/(\d{8})/', $filename, $matches);
    return (!empty($matches) ? $matches[0] : pathinfo($filename, PATHINFO_FILENAME)) . '.' . $extension;
}

function findExistingFileInDir(string $shortName, string $dir): ?string
{
    $baseName = pathinfo($shortName, PATHINFO_FILENAME);
    $pattern = $dir . DIRECTORY_SEPARATOR . $baseName . '.*';
    $files = glob($pattern);
    return $files ? $files[0] : null;
}

// Hilfsfunktion zur Verarbeitung des Upload-Ziels
function processFileTarget(string $tempFilePath, string $shortName, string $targetDir, string $imageFileType): array
{
    $existingFile = findExistingFileInDir($shortName, $targetDir);

    if ($existingFile !== null) {
        // Status speichern für Overwrite-Entscheidung
        $_SESSION['pending_upload'][$shortName] = [
            'temp_file' => $tempFilePath,
            'existing_file' => $existingFile,
            'target_dir' => $targetDir
        ];

        // URL für Vorschau generieren
        $relativeExistingPath = str_replace(DIRECTORY_PUBLIC, '', $existingFile);
        $existingFileUrl = DIRECTORY_PUBLIC_URL . str_replace(DIRECTORY_SEPARATOR, '/', $relativeExistingPath);

        // Daten URI für das neue Bild
        $imageData = file_get_contents($tempFilePath);
        $base64 = base64_encode($imageData);
        $newDataUri = 'data:image/' . $imageFileType . ';base64,' . $base64;

        return [
            'status' => 'confirmation_needed',
            'short_name' => $shortName,
            'existing_image_url' => $existingFileUrl,
            'new_image_data_uri' => $newDataUri
        ];
    } else {
        // Direkt verschieben
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $shortName;
        if (rename($tempFilePath, $targetPath)) {
            return ['status' => 'success', 'message' => "Datei '{$shortName}' erfolgreich hochgeladen."];
        } else {
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            return ['status' => 'error', 'message' => "Fehler beim Speichern von '{$shortName}'."];
        }
    }
}

// --- AJAX HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    // 1. UPLOAD INITIIEREN
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
            // Einstellungen laden für Entscheidung
            $settings = loadGeneratorSettings($configPath, $currentUser);

            if ($settings['auto_detect_hires']) {
                // AUTOMATIK
                list($width, $height) = getimagesize($tempFilePath);
                $minW = (int)$settings['hires_threshold_width'];
                $minH = (int)$settings['hires_threshold_height'];

                $targetDir = ($width >= $minW && $height >= $minH) ? DIRECTORY_PUBLIC_IMG_COMIC_HIRES : DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;

                echo json_encode(processFileTarget($tempFilePath, $shortName, $targetDir, $imageFileType));
            } else {
                // MANUELL: User muss entscheiden
                // Wir speichern temporär in Session und fragen den Client
                $_SESSION['pending_resolution_selection'][$shortName] = [
                    'temp_file' => $tempFilePath,
                    'file_type' => $imageFileType
                ];

                // Vorschau generieren
                $imageData = file_get_contents($tempFilePath);
                $base64 = base64_encode($imageData);
                $newDataUri = 'data:image/' . $imageFileType . ';base64,' . $base64;

                echo json_encode([
                    'status' => 'resolution_selection_needed',
                    'short_name' => $shortName,
                    'image_data_uri' => $newDataUri
                ]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => "Fehler beim Verschieben von '{$file['name']}'."]);
        }
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $response = ['success' => false, 'message' => ''];

        switch ($action) {
            // 2. MANUELLE AUSWAHL VERARBEITEN
            case 'select_resolution':
                $shortName = $_POST['short_name'] ?? null;
                $resolution = $_POST['resolution'] ?? null; // 'hires' oder 'lowres'

                if (!$shortName || !isset($_SESSION['pending_resolution_selection'][$shortName])) {
                    echo json_encode(['status' => 'error', 'message' => 'Ungültige Sitzung für Auflösungswahl.']);
                    exit;
                }

                $pendingData = $_SESSION['pending_resolution_selection'][$shortName];
                unset($_SESSION['pending_resolution_selection'][$shortName]);

                $targetDir = ($resolution === 'hires') ? DIRECTORY_PUBLIC_IMG_COMIC_HIRES : DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;

                echo json_encode(processFileTarget($pendingData['temp_file'], $shortName, $targetDir, $pendingData['file_type']));
                exit;

            // 3. ÜBERSCHREIBEN BESTÄTIGEN
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

            // 4. EINSTELLUNGEN SPEICHERN
            case 'save_settings':
                $input = json_decode($_POST['settings'] ?? '{}', true);

                // Aktuelle Settings laden um Timestamp ggf. zu behalten
                $currentData = loadGeneratorSettings($configPath, $currentUser);

                $newSettings = [
                    'last_run_timestamp' => $input['update_timestamp'] ? time() : $currentData['last_run_timestamp'],
                    'auto_detect_hires' => filter_var($input['auto_detect_hires'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'hires_threshold_width' => (int)($input['hires_threshold_width'] ?? 1800),
                    'hires_threshold_height' => (int)($input['hires_threshold_height'] ?? 1000)
                ];

                if (saveGeneratorSettings($configPath, $currentUser, $newSettings)) {
                    $response['success'] = true;
                } else {
                    $response['message'] = 'Fehler beim Speichern.';
                }
                break;
        }
        echo json_encode($response);
        exit;
    }
}

// LOGIK VIEW
$uploadSettings = loadGeneratorSettings($configPath, $currentUser);

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
                <?php else : ?>
                    <p class="status-message status-orange">Noch kein Upload durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>Bild-Upload</h2>
            <p>Ziehe Bilder per Drag & Drop in den Kasten oder wähle sie über den Button aus.</p>
        </div>

        <div id="statusMessages"></div>

        <!-- SETTINGS FORM (Neu) -->
        <div class="generator-settings">
            <div class="form-group checkbox-group" style="flex: 0 0 100%; justify-content: flex-start;">
                <label for="auto_detect_hires" title="Wenn aktiviert, entscheidet die Bildgröße über High-Res/Low-Res">
                    <input type="checkbox" id="auto_detect_hires" <?php echo ($uploadSettings['auto_detect_hires']) ? 'checked' : ''; ?>>
                    <strong>Automatische Erkennung (High-Res / Low-Res)</strong>
                </label>
            </div>

            <div class="form-group threshold-input" style="<?php echo (!$uploadSettings['auto_detect_hires']) ? 'display:none;' : ''; ?>">
                <label for="hires_threshold_width">Min. Breite für High-Res (px):</label>
                <input type="number" id="hires_threshold_width" value="<?php echo $uploadSettings['hires_threshold_width']; ?>">
            </div>

            <div class="form-group threshold-input" style="<?php echo (!$uploadSettings['auto_detect_hires']) ? 'display:none;' : ''; ?>">
                <label for="hires_threshold_height">Min. Höhe für High-Res (px):</label>
                <input type="number" id="hires_threshold_height" value="<?php echo $uploadSettings['hires_threshold_height']; ?>">
            </div>

            <div class="form-group" style="flex: 0 0 100%; display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="button" id="save-settings-btn" class="button button-blue" style="display: none;">
                    <i class="fas fa-save"></i> Einstellungen speichern
                </button>
            </div>
        </div>

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
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_thumbnail'  . ($dateiendungPHP ?? '.php'); ?>"
                   class="button button-orange"> <!--  target="_blank" -->
                   <i class="fas fa-images"></i> 1. Thumbnails generieren
                </a>

                <!-- Option 2: Cache -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting'  . ($dateiendungPHP ?? '.php'); ?>?autostart=lowres,hires"
                   class="button button-blue">
                   <i class="fas fa-sync"></i> 2. Cache aktualisieren
                </a>
            </div>
        </div>

        <!-- Confirm Overwrite Modal -->
        <div id="confirmationModal" class="modal hidden-by-default">
            <div class="modal-content modal-advanced-layout">
                <div class="modal-header-wrapper">
                    <h2 id="confirmationHeader">Überschreiben bestätigen</h2>
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

        <!-- NEW: Resolution Selection Modal -->
        <div id="resolutionModal" class="modal hidden-by-default">
            <div class="modal-content modal-advanced-layout">
                <div class="modal-header-wrapper">
                    <h2>Auflösung wählen</h2>
                    <!-- Kein Schließen-X, Entscheidung zwingend -->
                </div>

                <div class="modal-scroll-content" style="text-align: center;">
                    <p>Wohin soll das Bild <strong><span id="resShortName"></span></strong> hochgeladen werden?</p>
                    <div class="preview-container" style="margin: 20px auto; max-width: 400px;">
                        <img id="resPreviewImage" src="" alt="Vorschau" style="max-width: 100%; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>

                <div class="modal-footer-actions">
                    <div class="modal-buttons" style="justify-content: center;">
                        <button id="btnSelectHires" class="button button-blue">
                            <i class="fas fa-star"></i> High-Res (Original)
                        </button>
                        <button id="btnSelectLowres" class="button button-orange">
                            <i class="fas fa-compress"></i> Low-Res (Web)
                        </button>
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
        const saveSettingsBtn = document.getElementById('save-settings-btn');
        const settingsContainer = document.querySelector('.generator-settings');

        // Settings Inputs
        const autoDetectCheckbox = document.getElementById('auto_detect_hires');
        const thresholdInputs = document.querySelectorAll('.threshold-input');
        const widthInput = document.getElementById('hires_threshold_width');
        const heightInput = document.getElementById('hires_threshold_height');

        // Modals
        const confirmationModal = document.getElementById('confirmationModal');
        const resolutionModal = document.getElementById('resolutionModal');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');
        const lastRunContainer = document.getElementById('last-run-container');

        let filesToUpload = [];
        let isUploading = false;

        // --- Settings Logic ---
        function toggleThresholdInputs() {
            const isAuto = autoDetectCheckbox.checked;
            thresholdInputs.forEach(el => el.style.display = isAuto ? 'flex' : 'none');
            showSaveButton();
        }

        function showSaveButton() {
            if (!isUploading) saveSettingsBtn.style.display = 'inline-block';
        }

        autoDetectCheckbox.addEventListener('change', toggleThresholdInputs);
        widthInput.addEventListener('input', showSaveButton);
        heightInput.addEventListener('input', showSaveButton);

        saveSettingsBtn.addEventListener('click', async () => {
            const settings = {
                auto_detect_hires: autoDetectCheckbox.checked,
                hires_threshold_width: widthInput.value,
                hires_threshold_height: heightInput.value,
                update_timestamp: false // Settings save doesn't update "Last Upload"
            };

            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('settings', JSON.stringify(settings));
            formData.append('csrf_token', csrfToken);

            try {
                const res = await fetch('', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    saveSettingsBtn.style.display = 'none';
                    addStatusMessage('Einstellungen gespeichert.', 'success', 2000);
                } else {
                    addStatusMessage('Fehler beim Speichern: ' + data.message, 'error');
                }
            } catch(e) { console.error(e); }
        });

        // --- Drag & Drop ---
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);
        });
        dropZone.addEventListener('click', (e) => {
            if (e.target !== fileInput && !e.target.closest('label')) fileInput.click();
        });
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));

        function handleFiles(files) {
            filesToUpload = [...filesToUpload, ...Array.from(files)];
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

            isUploading = true;
            uploadButton.disabled = true;
            saveSettingsBtn.style.display = 'none';
            settingsContainer.style.opacity = '0.5';
            settingsContainer.style.pointerEvents = 'none';

            uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Lade hoch...';
            cacheUpdateNotification.style.display = 'none';
            statusMessages.innerHTML = '';

            let uploadSuccessCount = 0;
            let fileCounter = 0;

            for (const file of filesToUpload) {
                try {
                    const result = await uploadFile(file);

                    if (result.status === 'success') {
                        uploadSuccessCount++;
                        addStatusMessage(result.message, 'success', 2000);
                    } else if (result.status === 'info') {
                        addStatusMessage(result.message, 'info', 3000);
                    } else if (result.status === 'error') {
                        addStatusMessage(result.message, 'error');
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

            // Reset UI
            isUploading = false;
            settingsContainer.style.opacity = '1';
            settingsContainer.style.pointerEvents = 'auto';
            filesToUpload = [];
            updateFileList();
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload starten';
            fileInput.value = '';

            if (uploadSuccessCount > 0) {
                // Update Timestamp only on success
                const formData = new FormData();
                formData.append('action', 'save_settings');
                formData.append('settings', JSON.stringify({update_timestamp: true})); // Only update time
                formData.append('csrf_token', csrfToken);
                await fetch('', { method: 'POST', body: formData });

                updateTimestamp();
                cacheUpdateNotification.classList.remove('hidden-by-default');
                cacheUpdateNotification.style.display = 'block';
            }
        });

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('', { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`HTTP Fehler: ${response.status}`);
            const result = await response.json();

            // Entscheidungsbaum
            if (result.status === 'resolution_selection_needed') {
                // Manuelle Auswahl nötig -> dann weiter prüfen
                const selectionResult = await handleResolutionSelection(result);
                // Das Ergebnis der Auswahl könnte "confirmation_needed" oder "success/error" sein
                if (selectionResult.status === 'confirmation_needed') {
                    return await handleConfirmation(selectionResult);
                }
                return selectionResult;
            }
            else if (result.status === 'confirmation_needed') {
                return await handleConfirmation(result);
            }

            return result;
        }

        // --- Modals ---

        // 1. Manuelle Auswahl (Neu)
        async function handleResolutionSelection(data) {
            return new Promise(resolve => {
                resolutionModal.style.display = 'flex';
                document.getElementById('resShortName').textContent = data.short_name;
                document.getElementById('resPreviewImage').src = data.image_data_uri;

                const cleanup = () => { resolutionModal.style.display = 'none'; };

                const sendSelection = async (resType) => {
                    cleanup();
                    const fd = new FormData();
                    fd.append('action', 'select_resolution');
                    fd.append('short_name', data.short_name);
                    fd.append('resolution', resType);
                    fd.append('csrf_token', csrfToken);

                    try {
                        const r = await fetch('', {method: 'POST', body: fd});
                        resolve(await r.json());
                    } catch (e) {
                        resolve({status: 'error', message: e.message});
                    }
                };

                // Event Listener (Einmalig binden wäre sauberer, aber hier praktisch)
                document.getElementById('btnSelectHires').onclick = () => sendSelection('hires');
                document.getElementById('btnSelectLowres').onclick = () => sendSelection('lowres');
            });
        }

        // 2. Overwrite Confirmation
        async function handleConfirmation(data) {
            return new Promise(resolve => {
                const modal = document.getElementById('confirmationModal');
                const confirmBtn = document.getElementById('confirmOverwrite');
                const cancelBtn = document.getElementById('cancelOverwrite');
                const closeBtn = modal.querySelector('.close-button');

                modal.style.display = 'flex';
                document.getElementById('confirmationMessage').innerHTML = `Ein Bild mit dem Namen <strong>${escapeHtml(data.short_name)}</strong> existiert bereits. Überschreiben?`;
                document.getElementById('existingImage').src = data.existing_image_url;
                document.getElementById('newImage').src = data.new_image_data_uri;

                const cleanup = () => {
                    modal.style.display = 'none';
                    confirmBtn.onclick = null;
                    cancelBtn.onclick = null;
                    closeBtn.onclick = null;
                };

                const sendDecision = async (decision) => {
                    cleanup();
                    const fd = new FormData();
                    fd.append('action', 'confirm_overwrite');
                    fd.append('short_name', data.short_name);
                    fd.append('decision', decision);
                    fd.append('csrf_token', csrfToken);

                    try {
                        const r = await fetch('', { method: 'POST', body: fd });
                        resolve(await r.json());
                    } catch (e) {
                        resolve({status: 'error', message: e.message});
                    }
                };

                confirmBtn.onclick = () => sendDecision('yes');
                cancelBtn.onclick = () => sendDecision('no');
                closeBtn.onclick = () => sendDecision('no');
            });
        }

        // --- Helper ---
        function updateTimestamp() {
            const now = new Date();
            const formattedDate = now.toLocaleDateString('de-DE') + ' ' + now.toLocaleTimeString('de-DE');
            let p = lastRunContainer.querySelector('.status-message');
            if (!p) {
                p = document.createElement('p');
                p.className = 'status-message status-info';
                lastRunContainer.innerHTML = '';
                lastRunContainer.appendChild(p);
            }
            p.innerHTML = `Letzter Upload am ${formattedDate} Uhr.`;
        }

        function addStatusMessage(message, type, autoHideDuration = 0) {
            const div = document.createElement('div');
            let cssClass = 'status-info';
            if (type === 'success' || type === 'green') cssClass = 'status-green';
            if (type === 'error' || type === 'red') cssClass = 'status-red';
            if (type === 'orange') cssClass = 'status-orange';

            div.className = `status-message ${cssClass}`;
            div.innerHTML = `<p>${message}</p>`;
            statusMessages.prepend(div);

            if (autoHideDuration > 0) {
                setTimeout(() => {
                    div.style.opacity = '0';
                    setTimeout(() => div.remove(), 500);
                }, autoHideDuration);
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        updateFileList();
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
