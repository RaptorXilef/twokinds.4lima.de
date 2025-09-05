<?php
/**
 * Dies ist die Administrationsseite für den Social Media Bild-Generator.
 * Sie kombiniert die Modi "Zuschneiden" (Crop) und "Anpassen" (Fit) sowie die
 * Ausgabeformate JPG, PNG und WebP in einer einzigen, steuerbaren Datei.
 * Die Generierung erfolgt schrittweise über AJAX, um Speicherprobleme zu vermeiden.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: generator_image_socialmedia.php (kombiniert) wird geladen.");

ob_start();

// PHP-Einstellungen
ini_set('memory_limit', '1G');
gc_enable();

session_start();

// NEU: Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
require_once __DIR__ . '/src/components/security_check.php';

// Logout-Funktion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Sicherheitscheck: Admin-Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$hiresDir = __DIR__ . '/../assets/comic_hires/';
$lowresDir = __DIR__ . '/../assets/comic_lowres/'; // Hinzugefügt für Fallback
$socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/';

// Setze Parameter für den Header.
$pageTitle = 'Adminbereich - Social Media Bild-Generator';
$pageHeader = 'Social Media Bild-Generator';
$siteDescription = 'Seite zum Generieren der Sozial Media Vorschaubilder.';
$robotsContent = 'noindex, nofollow'; // Diese Seite soll nicht indexiert werden
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

// GD-Bibliothek-Check
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Bilder können nicht generiert werden.";
    error_log($gdError);
} else {
    $gdError = null;
}

/**
 * Scannt die Comic-Verzeichnisse nach vorhandenen Comic-Bildern.
 */
function getExistingComicIds(string $hiresDir, string $lowresDir, bool $debugMode): array
{
    $comicIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    foreach ([$hiresDir, $lowresDir] as $dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                    $comicIds[$fileInfo['filename']] = true;
                }
            }
        }
    }
    if ($debugMode)
        error_log("DEBUG: " . count($comicIds) . " eindeutige Comic-IDs gefunden.");
    return array_keys($comicIds);
}

/**
 * Scannt das Social-Media-Verzeichnis nach vorhandenen Bildern.
 */
function getExistingSocialMediaImageIds(string $socialMediaImageDir, bool $debugMode): array
{
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (is_dir($socialMediaImageDir)) {
        $files = scandir($socialMediaImageDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $imageIds[$fileInfo['filename']] = true;
            }
        }
    }
    if ($debugMode)
        error_log("DEBUG: " . count($imageIds) . " Social Media Bilder gefunden.");
    return array_keys($imageIds);
}

/**
 * Vergleicht Comic-IDs mit Social-Media-Bild-IDs, um fehlende zu finden.
 */
function findMissingSocialMediaImages(array $allComicIds, array $existingImageIds, bool $debugMode): array
{
    $missing = array_values(array_diff($allComicIds, $existingImageIds));
    if ($debugMode)
        error_log("DEBUG: " . count($missing) . " fehlende Social Media Bilder gefunden.");
    return $missing;
}

/**
 * Generiert ein einzelnes Social Media Bild in einem spezifizierten Format und Modus.
 * @param string $comicId Die ID des Comics.
 * @param string $outputFormat Das gewünschte Ausgabeformat ('jpg', 'png', 'webp').
 * @param string $resizeMode Der Skalierungsmodus ('crop' oder 'fit').
 * @param string $hiresDir Pfad zum High-Res-Verzeichnis.
 * @param string $lowresDir Pfad zum Low-Res-Verzeichnis.
 * @param string $socialMediaImageDir Pfad zum Zielverzeichnis.
 * @param bool $debugMode Debug-Modus an/aus.
 * @return array Ergebnis-Array mit 'created' und 'errors'.
 */
function generateSocialMediaImage(string $comicId, string $outputFormat, string $resizeMode, string $hiresDir, string $lowresDir, string $socialMediaImageDir, bool $debugMode): array
{
    $errors = [];
    if ($debugMode)
        error_log("DEBUG: Generiere '$comicId' | Format: '$outputFormat' | Modus: '$resizeMode'");

    // Zielverzeichnis prüfen
    if (!is_dir($socialMediaImageDir) && !mkdir($socialMediaImageDir, 0755, true)) {
        return ['created' => '', 'errors' => ["Zielverzeichnis '$socialMediaImageDir' konnte nicht erstellt werden."]];
    }
    if (!is_writable($socialMediaImageDir)) {
        return ['created' => '', 'errors' => ["Zielverzeichnis '$socialMediaImageDir' ist nicht beschreibbar."]];
    }

    $targetWidth = 1200;
    $targetHeight = 630;

    // Quellbild finden (priorisiert hires)
    $sourceImagePath = '';
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ([$hiresDir, $lowresDir] as $dir) {
        foreach ($possibleExtensions as $ext) {
            $path = $dir . $comicId . '.' . $ext;
            if (file_exists($path)) {
                $sourceImagePath = $path;
                break 2;
            }
        }
    }

    if (empty($sourceImagePath)) {
        return ['created' => '', 'errors' => ["Quellbild für '$comicId' nicht gefunden."]];
    }

    try {
        $imageInfo = @getimagesize($sourceImagePath);
        if ($imageInfo === false)
            return ['created' => '', 'errors' => ["Bildinfo für '$sourceImagePath' nicht lesbar."]];
        list($width, $height, $type) = $imageInfo;

        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($sourceImagePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = @imagecreatefromwebp($sourceImagePath);
                break;
        }
        if (!$sourceImage)
            return ['created' => '', 'errors' => ["Bild '$sourceImagePath' konnte nicht geladen werden."]];

        $tempImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$tempImage) {
            imagedestroy($sourceImage);
            return ['created' => '', 'errors' => ["Temporäres Bild konnte nicht erstellt werden."]];
        }

        // Hintergrund basierend auf Format setzen
        if ($outputFormat === 'jpg') {
            $backgroundColor = imagecolorallocate($tempImage, 255, 255, 255);
            imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $backgroundColor);
        } else {
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
            $transparentColor = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
            imagefill($tempImage, 0, 0, $transparentColor);
            imagealphablending($tempImage, true);
        }

        // === MODUSSPEZIFISCHE SKALIERUNG ===
        if ($resizeMode === 'crop') {
            $srcRatio = $width / $height;
            $targetRatio = $targetWidth / $targetHeight;
            $srcX = 0;
            $srcY = 0;
            $srcW = $width;
            $srcH = $height;

            if ($srcRatio > $targetRatio) { // Quelle breiter -> horizontal zentriert beschneiden
                $srcW = $height * $targetRatio;
                $srcX = ($width - $srcW) / 2;
            } else { // Quelle höher -> vertikal von oben beschneiden
                $srcH = $width / $targetRatio;
            }
            imagecopyresampled($tempImage, $sourceImage, 0, 0, (int) $srcX, (int) $srcY, $targetWidth, $targetHeight, (int) $srcW, (int) $srcH);
        } else { // Modus 'fit'
            $scale = min($targetWidth / $width, $targetHeight / $height);
            $newWidth = $width * $scale;
            $newHeight = $height * $scale;
            $offsetX = ($targetWidth - $newWidth) / 2;
            $offsetY = ($targetHeight - $newHeight) / 2;
            imagecopyresampled($tempImage, $sourceImage, (int) $offsetX, (int) $offsetY, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);
        }

        // Speichern
        $socialMediaImagePath = $socialMediaImageDir . $comicId . '.' . $outputFormat;
        $saveSuccess = false;
        switch ($outputFormat) {
            case 'jpg':
                $saveSuccess = imagejpeg($tempImage, $socialMediaImagePath, 90);
                break;
            case 'png':
                $saveSuccess = imagepng($tempImage, $socialMediaImagePath, 9);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saveSuccess = imagewebp($tempImage, $socialMediaImagePath, 90);
                } else {
                    $errors[] = "WebP-Unterstützung nicht aktiviert.";
                }
                break;
        }

        if (!$saveSuccess && empty($errors))
            $errors[] = "Fehler beim Speichern des Bildes.";

        imagedestroy($sourceImage);
        imagedestroy($tempImage);

        return ['created' => ($saveSuccess ? $socialMediaImagePath : ''), 'errors' => $errors];

    } catch (Throwable $e) {
        return ['created' => '', 'errors' => ["Ausnahme: " . $e->getMessage()]];
    } finally {
        gc_collect_cycles();
        usleep(50000);
    }
}

// --- AJAX-Anfrage-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_single_social_media_image') {
    ob_end_clean();
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];
    if (!extension_loaded('gd')) {
        $response['message'] = "FEHLER: Die GD-Bibliothek ist nicht geladen.";
        echo json_encode($response);
        exit;
    }

    $outputFormat = in_array($_POST['output_format'] ?? '', ['jpg', 'png', 'webp']) ? $_POST['output_format'] : 'webp';
    $resizeMode = in_array($_POST['resize_mode'] ?? '', ['crop', 'fit']) ? $_POST['resize_mode'] : 'crop';
    $comicId = $_POST['comic_id'] ?? '';

    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID angegeben.';
    } else {
        $result = generateSocialMediaImage($comicId, $outputFormat, $resizeMode, $hiresDir, $lowresDir, $socialMediaImageDir, $debugMode);
        if (empty($result['errors'])) {
            $response['success'] = true;
            $response['message'] = "Bild für $comicId als .$outputFormat ($resizeMode) erstellt.";
            $response['imageUrl'] = '../assets/comic_socialmedia/' . $comicId . '.' . $outputFormat . '?' . time();
            $response['comicId'] = $comicId;
        } else {
            $response['message'] = 'Fehler bei ' . $comicId . ': ' . implode(', ', $result['errors']);
        }
    }
    echo json_encode($response);
    exit;
}
// --- Ende AJAX-Anfrage-Handler ---

ob_end_flush();

$allComicIds = getExistingComicIds($hiresDir, $lowresDir, $debugMode);
$existingSocialMediaImageIds = getExistingSocialMediaImageIds($socialMediaImageDir, $debugMode);
$missingSocialMediaImages = findMissingSocialMediaImages($allComicIds, $existingSocialMediaImageIds, $debugMode);

if (file_exists($headerPath))
    include $headerPath;
else
    echo "<!DOCTYPE html><html><head><title>Fehler</title></head><body><h1>Header nicht gefunden!</h1>";
?>

<article>
    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p><?php endif; ?>
        <h2>Einstellungen & Status</h2>

        <div class="settings-container">
            <!-- Format-Umschalter -->
            <div class="format-switcher">
                <label>Format:</label>
                <div class="toggle-buttons">
                    <input type="radio" id="format-webp" name="format-toggle" value="webp" checked><label
                        for="format-webp">WebP</label>
                    <input type="radio" id="format-png" name="format-toggle" value="png"><label
                        for="format-png">PNG</label>
                    <input type="radio" id="format-jpg" name="format-toggle" value="jpg"><label
                        for="format-jpg">JPG</label>
                </div>
            </div>
            <!-- NEU: Modus-Umschalter -->
            <div class="format-switcher">
                <label>Modus:</label>
                <div class="toggle-buttons">
                    <input type="radio" id="mode-crop" name="mode-toggle" value="crop" checked><label
                        for="mode-crop">Zuschneiden</label>
                    <input type="radio" id="mode-fit" name="mode-toggle" value="fit"><label
                        for="mode-fit">Anpassen</label>
                </div>
            </div>
        </div>

        <div id="fixed-buttons-container">
            <button type="button" id="generate-images-button" <?php echo $gdError || empty($missingSocialMediaImages) ? 'disabled' : ''; ?>>Fehlende Bilder erstellen</button>
            <button type="button" id="toggle-pause-resume-button" style="display:none;"></button>
        </div>

        <div id="generation-results-section" style="margin-top: 20px; display: none;">
            <h2 style="margin-top: 20px;">Ergebnisse der Generierung</h2>
            <p id="overall-status-message" class="status-message"></p>
            <div id="created-images-container" class="image-grid"></div>
            <p class="status-message status-red" style="display: none;" id="error-header-message">Fehler:</p>
            <ul id="generation-errors-list"></ul>
        </div>

        <div id="cache-update-notification" class="notification-box" style="display:none; margin-top: 20px;">
            <h4>Nächster Schritt: Cache aktualisieren</h4>
            <p>
                Da neue Bilder hinzugefügt wurden, muss die Cache-JSON-Datei aktualisiert werden.
                <br>
                <strong>Hinweis:</strong> Führe diesen Schritt erst aus, wenn alle Bilder generiert sind, da der Prozess
                kurzzeitig hohe Serverlast verursachen kann.
            </p>
            <a href="build_image_cache_and_busting.php?autostart=socialmedia" class="button">Cache jetzt
                aktualisieren</a>
        </div>

        <div id="loading-spinner" style="display: none; text-align: center; margin-top: 20px;">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Bilder...</p>
        </div>

        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Keine Comic-Bilder in den Verzeichnissen gefunden.</p>
        <?php elseif (empty($missingSocialMediaImages)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Social Media Bilder sind
                vorhanden.</p>
        <?php else: ?>
            <p class="status-message status-red">Es fehlen <?php echo count($missingSocialMediaImages); ?> Social Media
                Bilder.</p>
            <h3>Fehlende Bilder (IDs):</h3>
            <div id="missing-images-grid" class="missing-items-grid">
                <?php foreach ($missingSocialMediaImages as $id): ?>
                    <span class="missing-item"
                        data-comic-id="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</article>

<style>
    .settings-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 20px;
    }

    .format-switcher {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .format-switcher label {
        font-weight: bold;
        min-width: 60px;
    }

    .toggle-buttons {
        display: flex;
        border: 1px solid #ccc;
        border-radius: 5px;
        overflow: hidden;
    }

    .toggle-buttons input[type="radio"] {
        display: none;
    }

    .toggle-buttons label {
        padding: 8px 16px;
        cursor: pointer;
        background-color: #f0f0f0;
        color: #333;
        transition: background-color 0.2s ease;
        border-left: 1px solid #ccc;
    }

    .toggle-buttons label:first-of-type {
        border-left: none;
    }

    .toggle-buttons input[type="radio"]:checked+label {
        background-color: #007bff;
        color: white;
    }

    body.theme-night .toggle-buttons {
        border-color: #045d81;
    }

    body.theme-night .toggle-buttons label {
        background-color: #025373;
        color: #f0f0f0;
        border-left-color: #045d81;
    }

    body.theme-night .toggle-buttons input[type="radio"]:checked+label {
        background-color: #09f;
    }

    :root {
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --missing-item-bg-color: #e9e9e9;
        --missing-item-text-color: #333;
    }

    body.theme-night {
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --missing-item-bg-color: #025373;
        --missing-item-text-color: #f0f0f0;
    }

    .status-message {
        padding: 8px 12px;
        border-radius: 5px;
        margin-bottom: 10px;
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

    .status-red-button,
    .status-green-button {
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
        border: none;
    }

    .status-red-button {
        background-color: #dc3545;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-red-button:disabled,
    .status-green-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        cursor: not-allowed;
    }

    .status-green-button {
        background-color: #28a745;
    }

    .status-green-button:hover {
        background-color: #218838;
    }

    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border-left-color: #09f;
        animation: spin 1s ease infinite;
        margin: 0 auto 10px auto;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .image-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
        padding-bottom: 20px;
    }

    .image-item {
        text-align: center;
        border: 1px solid #ccc;
        padding: 5px;
        border-radius: 8px;
        width: calc(50% - 5px);
        min-width: 300px;
        height: auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-sizing: border-box;
        overflow: hidden;
    }

    .image-item img {
        display: block;
        max-width: 100%;
        height: auto;
        object-fit: contain;
        border-radius: 4px;
        margin-bottom: 5px;
    }

    .image-item span {
        word-break: break-all;
        font-size: 0.8em;
    }

    @media (max-width: 1000px) {
        .image-item {
            width: 100%;
        }
    }

    #fixed-buttons-container {
        z-index: 1000;
        display: flex;
        gap: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        #fixed-buttons-container {
            flex-direction: column;
            gap: 5px;
            align-items: flex-end;
        }
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
        color: var(--missing-item-text-color);
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.9em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
        flex-shrink: 0;
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const generateButton = document.getElementById('generate-images-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingImagesGrid = document.getElementById('missing-images-grid');
        const createdImagesContainer = document.getElementById('created-images-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const overallStatusMessage = document.getElementById('overall-status-message');
        const errorHeaderMessage = document.getElementById('error-header-message');
        const errorsList = document.getElementById('generation-errors-list');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');

        const initialMissingIds = <?php echo json_encode($missingSocialMediaImages); ?>;
        let remainingIds = [];
        let createdCount = 0, errorCount = 0, isPaused = false, isGenerationActive = false;

        // Sticky-Buttons Logik (vereinfacht)
        const fixedButtonsContainer = document.getElementById('fixed-buttons-container');
        if (fixedButtonsContainer) {
            let stickyThreshold = fixedButtonsContainer.offsetTop;
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > stickyThreshold) {
                    // Logik für sticky-Positionierung kann hier hinzugefügt werden
                } else {
                    // Logik zum Entfernen der sticky-Positionierung
                }
            });
        }

        function updateButtonState() {
            if (initialMissingIds.length === 0) {
                generateButton.disabled = true;
                togglePauseResumeButton.style.display = 'none';
            } else if (isGenerationActive) {
                generateButton.style.display = 'none';
                togglePauseResumeButton.style.display = 'inline-block';
                if (isPaused) {
                    togglePauseResumeButton.textContent = 'Fortsetzen';
                    togglePauseResumeButton.className = 'status-green-button';
                    progressText.textContent = `Pausiert. ${createdCount + errorCount} von ${initialMissingIds.length} verarbeitet.`;
                } else {
                    togglePauseResumeButton.textContent = 'Pause';
                    togglePauseResumeButton.className = 'status-red-button';
                }
            } else {
                generateButton.style.display = 'inline-block';
                generateButton.disabled = false;
                togglePauseResumeButton.style.display = 'none';
            }
        }

        generateButton.addEventListener('click', function () {
            if (initialMissingIds.length === 0) return;
            remainingIds = [...initialMissingIds];
            createdImagesContainer.innerHTML = '';
            errorsList.innerHTML = '';
            errorHeaderMessage.style.display = 'none';
            overallStatusMessage.style.display = 'none';
            generationResultsSection.style.display = 'block';
            createdCount = 0; errorCount = 0; isPaused = false; isGenerationActive = true;
            cacheUpdateNotification.style.display = 'none';
            updateButtonState();
            loadingSpinner.style.display = 'block';
            processNextImage();
        });

        togglePauseResumeButton.addEventListener('click', function () {
            isPaused = !isPaused;
            updateButtonState();
            if (!isPaused) processNextImage();
        });

        async function processNextImage() {
            if (isPaused || remainingIds.length === 0) {
                if (remainingIds.length === 0 && isGenerationActive) {
                    loadingSpinner.style.display = 'none';
                    isGenerationActive = false;
                    updateButtonState();
                    overallStatusMessage.textContent = `Abgeschlossen: ${createdCount} erfolgreich, ${errorCount} Fehler.`;
                    overallStatusMessage.className = `status-message ${errorCount > 0 ? 'status-orange' : 'status-green'}`;
                    overallStatusMessage.style.display = 'block';
                    if (createdCount > 0) {
                        cacheUpdateNotification.style.display = 'block';
                    }
                }
                return;
            }

            const currentId = remainingIds.shift();
            progressText.textContent = `Generiere ${createdCount + errorCount + 1} von ${initialMissingIds.length} (${currentId})...`;

            // === ANPASSUNG: BEIDE Einstellungen auslesen ===
            const selectedFormat = document.querySelector('input[name="format-toggle"]:checked').value;
            const selectedMode = document.querySelector('input[name="mode-toggle"]:checked').value;

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'generate_single_social_media_image',
                        comic_id: currentId,
                        output_format: selectedFormat,
                        resize_mode: selectedMode // Neuen Modus mitsenden
                    })
                });

                if (!response.ok) throw new Error(`HTTP-Fehler! Status: ${response.status}`);
                const data = await response.json();

                if (data.success) {
                    createdCount++;
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'image-item';
                    imageDiv.innerHTML = `<img src="${data.imageUrl}" alt="Social Media Bild ${data.comicId}"><span>${data.comicId}</span>`;
                    createdImagesContainer.appendChild(imageDiv);
                    const missingItemSpan = missingImagesGrid?.querySelector(`span[data-comic-id="${data.comicId}"]`);
                    if (missingItemSpan) missingItemSpan.remove();
                } else {
                    errorCount++;
                    const errorItem = document.createElement('li');
                    errorItem.textContent = data.message || 'Unbekannter Fehler.';
                    errorsList.appendChild(errorItem);
                    errorHeaderMessage.style.display = 'block';
                }
            } catch (error) {
                errorCount++;
                const errorItem = document.createElement('li');
                errorItem.textContent = `Netzwerk/Skriptfehler bei ${currentId}: ${error.message}`;
                errorsList.appendChild(errorItem);
                errorHeaderMessage.style.display = 'block';
            }

            setTimeout(processNextImage, 1000);
        }
        updateButtonState();
    });
</script>

<?php
if (file_exists($footerPath))
    include $footerPath;
else
    echo "</body></html>";
?>