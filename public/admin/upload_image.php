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
 * @version   4.4.0
 * @since     4.1.0 Integriert das Speichern und Anzeigen der letzten Ausführung konsistent.
 * @since     4.2.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     4.9.0 Vollständige Umstellung auf neueste Konstanten-Struktur und Code-Bereinigung.
 * @since     5.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
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
        if (!is_dir($dir))
            mkdir($dir, 0755, true);
        file_put_contents($filePath, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }
    $content = file_get_contents($filePath);
    $settings = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        return $defaults;
    if (!isset($settings['upload_image']))
        $settings['upload_image'] = $defaults['upload_image'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// Sicherstellen, dass die Upload-Verzeichnisse existieren
if (!is_dir(DIRECTORY_PUBLIC_IMG_COMIC_HIRES))
    mkdir(DIRECTORY_PUBLIC_IMG_COMIC_HIRES, 0777, true);
if (!is_dir(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES))
    mkdir(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES, 0777, true);

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
                    if (file_exists($tempFilePath))
                        unlink($tempFilePath);
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
        $settingsFile = Path::getConfig('config_generator_settings.json');

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
                    if (file_exists($uploadData['existing_file']))
                        unlink($uploadData['existing_file']);
                    if (rename($uploadData['temp_file'], $uploadData['target_dir'] . DIRECTORY_SEPARATOR . $shortName)) {
                        echo json_encode(['status' => 'success', 'message' => "Datei '{$shortName}' wurde überschrieben."]);
                    } else {
                        if (file_exists($uploadData['temp_file']))
                            unlink($uploadData['temp_file']);
                        echo json_encode(['status' => 'error', 'message' => "Fehler beim Überschreiben von '{$shortName}'."]);
                    }
                } else {
                    if (file_exists($uploadData['temp_file']))
                        unlink($uploadData['temp_file']);
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

$settings = loadGeneratorSettings(Path::getConfig('config_generator_settings.json'), $debugMode);
$uploadSettings = $settings['upload_image'];
$pageTitle = 'Adminbereich - Bild-Upload';
$pageHeader = 'Bild-Upload';
$siteDescription = 'Seite zum hochladen der Comicseiten auf den Server (ohne FTP).';
$robotsContent = 'noindex, nofollow';

require_once Path::getTemplatePartial('header.php');
?>

<article>
    <div class="content-section">
        <div id="last-run-container">
            <?php if ($uploadSettings['last_run_timestamp']): ?>
                <p class="status-message status-info">Letzter Upload am
                    <?php echo date('d.m.Y \u\m H:i:s', $uploadSettings['last_run_timestamp']); ?> Uhr.
                </p>
            <?php endif; ?>
        </div>

        <h2>Bild-Upload</h2>
        <p>Ziehe Bilder per Drag & Drop in den Kasten oder wähle sie über den Button aus, um sie hochzuladen. Das Skript
            erkennt automatisch, ob es sich um eine High-Res- oder Low-Res-Version handelt.</p>

        <div id="statusMessages"></div>

        <div id="uploadContainer">
            <form id="uploadForm">
                <div id="dropZone" class="drag-drop-zone">
                    <p class="instructions">Dateien hierher ziehen, oder klicken</p>
                    <input type="file" id="fileInput" multiple class="hidden-by-default">
                    <label for="fileInput" class="button">Bilder auswählen</label>
                </div>
                <div id="fileList"></div>
                <button type="submit" id="uploadButton" class="button upload-button" disabled>Upload starten</button>
            </form>
        </div>

        <div id="cache-update-notification" class="notification-box hidden-by-default">
            <h4>Nächster Schritt: Cache aktualisieren</h4>
            <p>
                Da neue Bilder hinzugefügt wurden, muss die Cache-JSON-Datei aktualisiert werden.
                <br>
                <strong>Hinweis:</strong> Führe diesen Schritt erst aus, wenn alle Bilder hochgeladen sind.
            </p>
            <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting.php?autostart=lowres,hires'; ?>"
                class="button">Cache jetzt
                aktualisieren</a>
        </div>

        <div id="confirmationModal" class="modal hidden-by-default">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2 id="confirmationHeader">Bestätigung erforderlich</h2>
                <p id="confirmationMessage"></p>
                <div class="image-comparison">
                    <div class="image-box">
                        <h3>Bestehendes Bild</h3>
                        <img id="existingImage" src="" alt="Bestehendes Bild">
                    </div>
                    <div class="image-box">
                        <h3>Neues Bild</h3>
                        <img id="newImage" src="" alt="Neues Bild">
                    </div>
                </div>
                <div class="modal-buttons">
                    <button id="confirmOverwrite" class="button status-green-button">Ja, überschreiben</button>
                    <button id="cancelOverwrite" class="button status-red-button">Nein, abbrechen</button>
                </div>
            </div>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    .drag-drop-zone {
        border: 2px dashed #ccc;
        border-radius: 5px;
        padding: 50px;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-bottom: 15px;
    }

    body.theme-night .drag-drop-zone {
        border-color: #555;
    }

    .drag-drop-zone.drag-over {
        background-color: #f0f0f0;
    }

    body.theme-night .drag-drop-zone.drag-over {
        background-color: #333;
    }

    #fileList {
        margin-top: 10px;
        font-style: italic;
        margin-bottom: 15px;
        min-height: 1.2em;
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

    .status-orange {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .status-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .image-comparison {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 20px 0;
    }

    .image-box {
        text-align: center;
        border: 1px solid #ccc;
        padding: 10px;
        border-radius: 5px;
        background-color: #fff;
    }

    body.theme-night .image-box {
        background-color: #00334c;
        border-color: #2a6177;
    }

    .image-box img {
        display: block;
        margin-top: 10px;
        max-width: 250px;
        max-height: 250px;
    }

    .upload-button {
        margin-top: 15px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 10001;
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
        border: 1px solid #888;
        width: 80%;
        max-width: 700px;
        border-radius: 5px;
        position: relative;
        text-align: center;
    }

    body.theme-night .modal-content {
        background-color: #00334c;
        border: 1px solid #2a6177;
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
        justify-content: center;
        gap: 15px;
    }

    .notification-box {
        border: 1px solid #bee5eb;
        background-color: #d1ecf1;
        color: #0c5460;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }

    body.theme-night .notification-box {
        background-color: #0c5460;
        border-color: #17a2b8;
        color: #f8f9fa;
    }

    .notification-box h4 {
        margin-top: 0;
    }

    .notification-box .button {
        margin-top: 10px;
        display: inline-block;
    }

    .hidden-by-default {
        display: none;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadForm = document.getElementById('uploadForm');
        const uploadButton = document.getElementById('uploadButton');
        const statusMessages = document.getElementById('statusMessages');
        const confirmationModal = document.getElementById('confirmationModal');
        const closeButton = document.querySelector('.close-button');
        const confirmOverwriteButton = document.getElementById('confirmOverwrite');
        const cancelOverwriteButton = document.getElementById('cancelOverwrite');
        const existingImage = document.getElementById('existingImage');
        const newImage = document.getElementById('newImage');
        const confirmationMessage = document.getElementById('confirmationMessage');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');
        const lastRunContainer = document.getElementById('last-run-container');
        let filesToUpload = [];

        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('drag-over'); if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; handleFiles(fileInput.files); } });
        dropZone.addEventListener('click', (e) => { if (!e.target.closest('label')) fileInput.click(); });
        fileInput.addEventListener('change', () => handleFiles(fileInput.files));

        function handleFiles(files) { filesToUpload = Array.from(files); updateFileList(); }

        function updateFileList() {
            if (filesToUpload.length > 0) {
                fileList.textContent = `Ausgewählte Dateien (${filesToUpload.length}): ${filesToUpload.map(f => f.name).join(', ')}`;
                uploadButton.disabled = false;
            } else {
                fileList.textContent = '';
                uploadButton.disabled = true;
            }
        }

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            uploadButton.disabled = true;
            cacheUpdateNotification.style.display = 'none';
            let uploadSuccess = false;
            let fileCounter = 0;

            for (const file of filesToUpload) {
                const result = await uploadFile(file);
                if (result && (result.status === 'success' || result.status === 'info')) {
                    fileCounter++;
                }
                if (result && result.status === 'success') {
                    uploadSuccess = true;
                }
            }

            if (fileCounter > 0) {
                addStatusMessage("Upload-Prozess abgeschlossen.", "info");
            }

            filesToUpload = [];
            updateFileList();
            uploadButton.disabled = false;

            if (uploadSuccess) {
                await saveSettings();
                updateTimestamp();
                cacheUpdateNotification.style.display = 'block';
            }
        });

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/upload_image.php' . $dateiendungPHP; ?>', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'confirmation_needed') {
                return await handleConfirmation(result);
            } else {
                addStatusMessage(result.message, result.status);
                return result;
            }
        }

        async function handleConfirmation(data) {
            return new Promise(resolve => {
                confirmationModal.style.display = 'flex';
                confirmationMessage.innerHTML = `Ein Bild mit dem Namen <strong>${data.short_name}</strong> existiert bereits. Soll es überschrieben werden?`;
                existingImage.src = data.existing_image_url;
                newImage.src = data.new_image_data_uri;

                const handleDecision = async (decision) => {
                    confirmationModal.style.display = 'none';
                    const formData = new FormData();
                    formData.append('action', 'confirm_overwrite');
                    formData.append('short_name', data.short_name);
                    formData.append('decision', decision);
                    formData.append('csrf_token', csrfToken);

                    const response = await fetch('<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/upload_image.php' . $dateiendungPHP; ?>', { method: 'POST', body: formData });
                    const result = await response.json();
                    addStatusMessage(result.message, result.status);
                    resolve(result);
                };

                confirmOverwriteButton.onclick = () => handleDecision('yes');
                cancelOverwriteButton.onclick = () => handleDecision('no');
                closeButton.onclick = () => handleDecision('no');
                window.onclick = (event) => { if (event.target === confirmationModal) handleDecision('no'); };
            });
        }

        async function saveSettings() {
            await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'save_settings', csrf_token: csrfToken })
            });
        }

        function updateTimestamp() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const formattedDate = `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`;

            let pElement = lastRunContainer.querySelector('.status-message');
            if (!pElement) {
                pElement = document.createElement('p');
                pElement.className = 'status-message status-info';
                lastRunContainer.prepend(pElement);
            }
            pElement.innerHTML = `Letzter Upload am ${formattedDate} Uhr.`;
        }

        function addStatusMessage(message, type) {
            const messageDiv = document.createElement('div');
            if (type === 'success') type = 'green';
            if (type === 'error') type = 'red';
            if (type === 'info') type = 'orange';

            messageDiv.className = `status-message status-${type}`;
            messageDiv.innerHTML = `<p>${message}</p>`;
            statusMessages.prepend(messageDiv);
        }

        updateFileList();
    });
</script>

<?php require_once Path::getTemplatePartial('footer.php');
ob_end_flush(); ?>