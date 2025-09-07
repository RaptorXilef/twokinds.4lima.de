<?php
/**
 * Administrationsseite zum asynchronen Hochladen von Comic-Bildern.
 *
 * Die Seite ermöglicht den Multi-File-Upload über eine Drag-and-Drop-Zone.
 * Jede Datei wird einzeln verarbeitet. Falls eine Überschreibung notwendig ist,
 * wird der Nutzer für jedes Bild individuell über eine interaktive Oberfläche
 * um Bestätigung gebeten.
 *
 * @version 3.6 (Sicherheits-Härtung mit CSRF & CSP)
 * @author Felix
 * @date 2025-09-07
 */

// === DEBUG-MODUS & KONFIGURATION ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG (enthält Nonce und CSRF-Setup) ===
require_once __DIR__ . '/src/components/admin_init.php';

// Korrigierte Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$uploadHiresDir = __DIR__ . '/../assets/comic_hires';
$uploadLowresDir = __DIR__ . '/../assets/comic_lowres';
$tempDir = sys_get_temp_dir();

if ($debugMode)
    error_log("DEBUG: Pfade definiert: headerPath=" . $headerPath . ", uploadHiresDir=" . $uploadHiresDir . ", uploadLowresDir=" . $uploadLowresDir);

if (!is_dir($uploadHiresDir)) {
    mkdir($uploadHiresDir, 0777, true);
}
if (!is_dir($uploadLowresDir)) {
    mkdir($uploadLowresDir, 0777, true);
}

/**
 * Hilfsfunktion, um Dateinamen zu kürzen (JJJJMMTT.ext).
 */
function shortenFilename($filename)
{
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    preg_match('/(\d{8})/', $filename, $matches);
    $shortName = !empty($matches) ? $matches[0] : pathinfo($filename, PATHINFO_FILENAME);
    return $shortName . '.' . $extension;
}

// Hilfsfunktion, um existierende Dateien im spezifischen Verzeichnis zu finden
function findExistingFileInDir($shortName, $dir)
{
    $baseName = pathinfo($shortName, PATHINFO_FILENAME);
    foreach (glob($dir . '/' . $baseName . '.*') as $file) {
        return $file;
    }
    return null;
}

// API-Logik für den initialen Datei-Upload (einzeln pro Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    header('Content-Type: application/json');
    // SICHERHEIT: CSRF-Token validieren
    verify_csrf_token();

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Upload.']);
        exit;
    }

    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowedExtensions)) {
        echo json_encode(['status' => 'error', 'message' => 'Ungültiger Dateityp.']);
        exit;
    }

    $shortName = shortenFilename($file['name']);
    $tempFilePath = $tempDir . '/' . uniqid() . '_' . $shortName;

    if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        list($width, $height) = getimagesize($tempFilePath);
        $targetDir = ($width >= 1800 && $height >= 1000) ? $uploadHiresDir : $uploadLowresDir;
        $existingFile = findExistingFileInDir($shortName, $targetDir);

        if ($existingFile !== null) {
            $_SESSION['pending_upload'][$shortName] = [
                'temp_file' => $tempFilePath,
                'existing_file' => $existingFile,
                'target_dir' => $targetDir
            ];
            $existingFileUrl = '../assets/' . basename($targetDir) . '/' . basename($existingFile);

            echo json_encode([
                'status' => 'confirmation_needed',
                'short_name' => $shortName,
                'existing_image_url' => $existingFileUrl,
                'new_image_data_uri' => 'data:image/' . $imageFileType . ';base64,' . base64_encode(file_get_contents($tempFilePath))
            ]);
            exit;
        } else {
            $targetPath = $targetDir . '/' . $shortName;
            if (rename($tempFilePath, $targetPath)) {
                echo json_encode(['status' => 'success', 'message' => "Datei '{$shortName}' erfolgreich hochgeladen."]);
            } else {
                unlink($tempFilePath);
                echo json_encode(['status' => 'error', 'message' => "Fehler beim Speichern von '{$shortName}'."]);
            }
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "Fehler beim Verschieben von '{$file['name']}'.", 'error_code' => $file['error']]);
        exit;
    }
}

// API-Logik für die Überschreib-Bestätigung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_overwrite') {
    header('Content-Type: application/json');
    // SICHERHEIT: CSRF-Token validieren
    verify_csrf_token();

    $shortName = $_POST['short_name'] ?? null;
    $decision = $_POST['decision'] ?? null;

    if (!$shortName || !isset($_SESSION['pending_upload'][$shortName])) {
        echo json_encode(['status' => 'error', 'message' => 'Ungültige Bestätigungsanfrage.']);
        exit;
    }

    $uploadData = $_SESSION['pending_upload'][$shortName];
    $tempFilePath = $uploadData['temp_file'];
    $existingFile = $uploadData['existing_file'];
    $targetDir = $uploadData['target_dir'];

    unset($_SESSION['pending_upload'][$shortName]);

    if ($decision === 'yes') {
        if (file_exists($existingFile)) {
            unlink($existingFile);
        }
        $targetPath = $targetDir . '/' . $shortName;
        if (rename($tempFilePath, $targetPath)) {
            echo json_encode(['status' => 'success', 'message' => "Datei '{$shortName}' wurde erfolgreich überschrieben."]);
        } else {
            unlink($tempFilePath);
            echo json_encode(['status' => 'error', 'message' => "Fehler beim Überschreiben von '{$shortName}'.", 'temp_file' => basename($tempFilePath)]);
        }
    } else {
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
        echo json_encode(['status' => 'info', 'message' => "Upload von '{$shortName}' wurde abgebrochen."]);
    }
    exit;
}

// Setze Parameter für den Header.
$pageTitle = 'Adminbereich - Bild-Upload';
$pageHeader = 'Bild-Upload für Comic-Seiten (hires und lowres)';
$siteDescription = 'Seite zum hochladen der Comicseiten auf den Server (ohne FTP).';
$robotsContent = 'noindex, nofollow';
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<div class="main-content">
    <article class="center">
        <div id="statusMessages"></div>

        <div id="uploadContainer">
            <form id="uploadForm">
                <div id="dropZone" class="drag-drop-zone">
                    <p class="instructions">Dateien hierher ziehen, oder klicken</p>
                    <input type="file" id="fileInput" multiple hidden>
                    <label for="fileInput" class="button">Bilder auswählen</label>
                </div>
                <div id="fileList" style="margin-top: 10px; font-style: italic;"></div>
                <button type="submit" id="uploadButton" class="button upload-button">Upload starten</button>
            </form>
        </div>

        <div id="cache-update-notification" class="notification-box" style="display:none; margin-top: 20px;">
            <h4>Nächster Schritt: Cache aktualisieren</h4>
            <p>
                Da neue Bilder hinzugefügt wurden, muss die Cache-JSON-Datei aktualisiert werden.
                <br>
                <strong>Hinweis:</strong> Führe diesen Schritt erst aus, wenn alle Bilder hochgeladen sind, da der
                Prozess kurzzeitig hohe Serverlast verursachen kann.
            </p>
            <a href="build_image_cache_and_busting.php?autostart=lowres,hires" class="button">Cache jetzt
                aktualisieren</a>
        </div>

        <div id="confirmationModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close-button">&times;</span>
                <h2 id="confirmationHeader">Bestätigung erforderlich</h2>
                <p id="confirmationMessage">Ein Bild mit dem Namen **DATEN** existiert bereits. Soll es
                    überschrieben werden?</p>
                <div class="image-comparison">
                    <div class="image-box">
                        <h3>Bestehendes Bild</h3>
                        <img id="existingImage" src="" alt="Bestehendes Bild"
                            style="max-width: 250px; max-height: 250px;">
                    </div>
                    <div class="image-box">
                        <h3>Neues Bild</h3>
                        <img id="newImage" src="" alt="Neues Bild" style="max-width: 250px; max-height: 250px;">
                    </div>
                </div>
                <div class="modal-buttons">
                    <button id="confirmOverwrite" class="button button-confirm">Ja, überschreiben</button>
                    <button id="cancelOverwrite" class="button button-cancel">Nein, abbrechen</button>
                </div>
            </div>
        </div>
    </article>
</div>
</div>

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

        let filesToUpload = [];

        // Drag-and-Drop Funktionalität
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
                fileInput.files = e.dataTransfer.files;
                handleFiles(fileInput.files);
            }
        });

        // Dateiauswahl über Klick
        dropZone.addEventListener('click', (e) => {
            if (!e.target.closest('label')) {
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', () => handleFiles(fileInput.files));

        function handleFiles(files) {
            filesToUpload = Array.from(files);
            updateFileList();
        }

        function updateFileList() {
            if (filesToUpload.length > 0) {
                const fileNames = filesToUpload.map(f => f.name).join(', ');
                fileList.textContent = `Ausgewählte Dateien (${filesToUpload.length}): ${fileNames}`;
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

            for (const file of filesToUpload) {
                await uploadFile(file);
            }

            addStatusMessage("Upload-Prozess abgeschlossen.", "info");
            filesToUpload = [];
            updateFileList();
            uploadButton.disabled = false;

            cacheUpdateNotification.style.display = 'block';
        });

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', csrfToken); // CSRF-Token hinzufügen

            const response = await fetch('upload_image.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'confirmation_needed') {
                await handleConfirmation(result);
            } else {
                addStatusMessage(result.message, result.status);
            }
        }

        async function handleConfirmation(data) {
            confirmationModal.style.display = 'flex';
            confirmationMessage.innerHTML = `Ein Bild mit dem Namen <strong>${data.short_name}</strong> existiert bereits. Soll es überschrieben werden?`;
            existingImage.src = data.existing_image_url;
            newImage.src = data.new_image_data_uri;

            return new Promise(resolve => {
                const handleDecision = async (decision) => {
                    confirmationModal.style.display = 'none';

                    const formData = new FormData();
                    formData.append('action', 'confirm_overwrite');
                    formData.append('short_name', data.short_name);
                    formData.append('decision', decision);
                    formData.append('csrf_token', csrfToken); // CSRF-Token hinzufügen

                    const response = await fetch('upload_image.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    addStatusMessage(result.message, result.status);
                    resolve();
                };

                confirmOverwriteButton.onclick = () => handleDecision('yes');
                cancelOverwriteButton.onclick = () => handleDecision('no');
                closeButton.onclick = () => handleDecision('no');
                window.onclick = (event) => {
                    if (event.target === confirmationModal) {
                        handleDecision('no');
                    }
                };
            });
        }

        function addStatusMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `status-message ${type}`;
            messageDiv.innerHTML = `<p>${message}</p>`;
            statusMessages.prepend(messageDiv);
        }
    });
</script>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    .main-container {
        padding: 20px;
        max-width: 1200px;
        margin: 20px auto;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    body.theme-night .main-container {
        background-color: #00425c;
        color: #fff;
    }

    .drag-drop-zone {
        border: 2px dashed #ccc;
        border-radius: 5px;
        padding: 50px;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .drag-drop-zone.drag-over {
        background-color: #f0f0f0;
    }

    .status-message {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
    }

    .status-message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .status-message.info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .image-comparison {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
        margin-bottom: 20px;
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
        /* Dunklerer Hintergrund für die Boxen */
        border-color: #2a6177;
    }

    .image-box img {
        display: block;
        margin-top: 10px;
    }

    .upload-button {
        margin-top: 15px;
    }

    /* Modal-Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 101;
        /* Erhöhter z-index, um über dem Banner zu liegen */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
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
        /* Dunkler Hintergrund für das Modal */
        border: 1px solid #2a6177;
        color: #fff;
    }

    .close-button {
        color: #aaa;
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 28px;
        font-weight: bold;
    }

    .close-button:hover,
    .close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .modal-buttons {
        margin-top: 20px;
    }

    .banner {
        z-index: 100;
    }

    .notification-box {
        border: 1px solid #bee5eb;
        background-color: #d1ecf1;
        color: #0c5460;
        padding: 15px;
        border-radius: 5px;
    }

    .notification-box h4 {
        margin-top: 0;
    }

    .notification-box .button {
        margin-top: 10px;
        display: inline-block;
    }

    body.theme-night .notification-box {
        background-color: #0c5460;
        border-color: #17a2b8;
        color: #f8f9fa;
    }
</style>

<?php
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>