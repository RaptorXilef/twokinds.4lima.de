<?php
/**
 * Dies ist die Administrationsseite für den Thumbnail-Generator.
 * 
 * @file      /admin/generator_thumbnail.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.6.0
 * @since     2.6.0 Behebt einen JavaScript ReferenceError aufgrund eines Scope-Problems.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$lowresDir = __DIR__ . '/../assets/comic_lowres/';
$hiresDir = __DIR__ . '/../assets/comic_hires/';
$thumbnailDir = __DIR__ . '/../assets/comic_thumbnails/';
$settingsFilePath = __DIR__ . '/../src/config/generator_settings.json';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'generator_thumbnail' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_run_timestamp' => null],
        'generator_socialmedia' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_used_resize_mode' => 'crop', 'last_run_timestamp' => null],
        'build_image_cache' => ['last_run_type' => null, 'last_run_timestamp' => null],
        'generator_comic' => ['last_run_timestamp' => null],
        'upload_image' => ['last_run_timestamp' => null]
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
    if (!isset($settings['generator_thumbnail']))
        $settings['generator_thumbnail'] = $defaults['generator_thumbnail'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// ... (Restliche PHP-Funktionen unverändert) ...
function getExistingComicIds(string $lowresDir, string $hiresDir, bool $debugMode): array
{
    $comicIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ([$lowresDir, $hiresDir] as $dir) {
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
    return array_keys($comicIds);
}
function getExistingThumbnailIds(string $thumbnailDir, bool $debugMode): array
{
    $thumbnailIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (is_dir($thumbnailDir)) {
        $files = scandir($thumbnailDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $thumbnailIds[$fileInfo['filename']] = true;
            }
        }
    }
    return array_keys($thumbnailIds);
}
function findMissingThumbnails(array $allComicIds, array $existingThumbnailIds, bool $debugMode): array
{
    return array_values(array_diff($allComicIds, $existingThumbnailIds));
}
/**
 * Generiert ein einzelnes Thumbnail in einem spezifizierten Format (jpg, png, oder webp).
 * @param string $comicId Die ID des Comics.
 * @param string $outputFormat Das gewünschte Ausgabeformat ('jpg', 'png', 'webp').
 * @param string $lowresDir Pfad zum Low-Res-Verzeichnis.
 * @param string $hiresDir Pfad zum High-Res-Verzeichnis.
 * @param string $thumbnailDir Pfad zum Thumbnail-Verzeichnis.
 * @param bool $debugMode Debug-Modus an/aus.
 * @return array Ergebnis-Array mit 'created' und 'errors'.
 */
function generateThumbnail(string $comicId, string $outputFormat, string $lowresDir, string $hiresDir, string $thumbnailDir, bool $debugMode, array $options = []): array
{
    $errors = [];
    $createdPath = '';
    if (!is_dir($thumbnailDir)) {
        if (!mkdir($thumbnailDir, 0755, true)) {
            $errors[] = "Zielverzeichnis '$thumbnailDir' konnte nicht erstellt werden.";
            return ['created' => '', 'errors' => $errors];
        }
    }
    if (!is_writable($thumbnailDir)) {
        $errors[] = "Zielverzeichnis '$thumbnailDir' ist nicht beschreibbar.";
        return ['created' => '', 'errors' => $errors];
    }

    // Ziel-Dimensionen
    $targetWidth = 198;
    $targetHeight = 258;

    // Quellbild finden
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
        return ['created' => '', 'errors' => ["Quellbild für Comic-ID '$comicId' nicht gefunden."]];
    }
    try {
        // Quellbild laden
        $imageInfo = @getimagesize($sourceImagePath);
        if ($imageInfo === false) {
            $errors[] = "Kann Bildinformationen für '$sourceImagePath' nicht abrufen.";
            return ['created' => '', 'errors' => $errors];
        }
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
            default:
                $errors[] = "Nicht unterstütztes Bildformat.";
        }
        if (!$sourceImage) {
            $errors[] = "Fehler beim Laden des Bildes von '$sourceImagePath'.";
            return ['created' => '', 'errors' => $errors];
        }

        // Skalierungsberechnung
        $ratio = min($targetWidth / $width, $targetHeight / $height);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;

        // Temporäres Bild erstellen
        $tempImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($tempImage === false) {
            $errors[] = "Fehler beim Erstellen des temporären Bildes.";
            imagedestroy($sourceImage);
            return ['created' => '', 'errors' => $errors];
        }

        // === FORMATSPEZIFISCHER HINTERGRUND ===
        if ($outputFormat === 'jpg') {
            // Für JPG einen soliden weißen Hintergrund erstellen.
            $backgroundColor = imagecolorallocate($tempImage, 255, 255, 255);
            imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $backgroundColor);
        } else {
            // Für PNG und WebP einen transparenten Hintergrund erstellen.
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
            // Schwarz mit 10% Deckkraft (90% Transparenz)
            $backgroundColor = imagecolorallocatealpha($tempImage, 0, 0, 0, 114);
            imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $backgroundColor);
            imagealphablending($tempImage, true);
        }

        // Bild resamplen und zentrieren
        $offsetX = ($targetWidth - $newWidth) / 2;
        $offsetY = ($targetHeight - $newHeight) / 2;
        imagecopyresampled($tempImage, $sourceImage, (int) $offsetX, (int) $offsetY, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);

        // === FORMATSPEZIFISCHES SPEICHERN ===
        $thumbnailPath = $thumbnailDir . $comicId . '.' . $outputFormat;
        $saveSuccess = false;
        switch ($outputFormat) {
            case 'jpg':
                $saveSuccess = imagejpeg($tempImage, $thumbnailPath, $options['quality'] ?? 90);
                break;
            case 'png':
                $saveSuccess = imagepng($tempImage, $thumbnailPath, $options['quality'] ?? 9);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $quality = $options['quality'] ?? 90;
                    $saveSuccess = imagewebp($tempImage, $thumbnailPath, $quality);
                } else {
                    $errors[] = "WebP-Unterstützung ist auf diesem Server nicht aktiviert.";
                }
                break;
        }
        if ($saveSuccess && filesize($thumbnailPath) > 0) {
            $createdPath = $thumbnailPath;
        } else {
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            if (empty($errors)) {
                $errors[] = "Fehler beim Speichern des Bildes (evtl. 0-Byte-Datei). Server-Konfiguration prüfen.";
            }
        }
        imagedestroy($sourceImage);
        imagedestroy($tempImage);
    } catch (Throwable $e) {
        $errors[] = "Ausnahme bei Comic-ID '$comicId': " . $e->getMessage();
    } finally {
        gc_collect_cycles();
        usleep(50000);
    }
    return ['created' => $createdPath, 'errors' => $errors];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    switch ($action) {
        case 'generate_single_thumbnail':
            if (!extension_loaded('gd')) {
                $response['message'] = "FEHLER: Die GD-Bibliothek ist nicht geladen.";
                echo json_encode($response);
                exit;
            }
            $outputFormat = $_POST['output_format'] ?? 'webp';
            if (!in_array($outputFormat, ['jpg', 'png', 'webp'])) {
                $outputFormat = 'webp';
            }
            $comicId = $_POST['comic_id'] ?? '';
            if (empty($comicId)) {
                $response['message'] = 'Keine Comic-ID angegeben.';
                echo json_encode($response);
                exit;
            }
            $options = ['quality' => $_POST['quality'] ?? null];
            $result = generateThumbnail($comicId, $outputFormat, $lowresDir, $hiresDir, $thumbnailDir, $debugMode, $options);
            if (empty($result['errors'])) {
                $response['success'] = true;
                $response['message'] = "Thumbnail für $comicId als .$outputFormat erstellt.";
                $response['imageUrl'] = '../assets/comic_thumbnails/' . $comicId . '.' . $outputFormat . '?' . time();
                $response['comicId'] = $comicId;
            } else {
                $response['message'] = 'Fehler bei ' . $comicId . ': ' . implode(', ', $result['errors']);
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings($settingsFilePath, $debugMode);
            $newThumbnailSettings = ['last_used_format' => $_POST['format'] ?? 'webp', 'last_used_quality' => (int) ($_POST['quality'] ?? 90), 'last_used_lossless' => ($_POST['lossless'] === 'true'), 'last_run_timestamp' => time()];
            $currentSettings['generator_thumbnail'] = $newThumbnailSettings;
            if (saveGeneratorSettings($settingsFilePath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}
ob_end_flush();
$settings = loadGeneratorSettings($settingsFilePath, $debugMode);
$thumbnailSettings = $settings['generator_thumbnail'];
$allComicIds = getExistingComicIds($lowresDir, $hiresDir, $debugMode);
$existingThumbnailIds = getExistingThumbnailIds($thumbnailDir, $debugMode);
$missingThumbnails = findMissingThumbnails($allComicIds, $existingThumbnailIds, $debugMode);

$pageTitle = 'Adminbereich - Thumbnail Generator';
$pageHeader = 'Thumbnail Generator';
$siteDescription = 'Seite zum Generieren der Vorschaubilder.';

include $headerPath;
?>

<article>
    <div class="content-section">
        <div id="last-run-container">
            <?php if ($thumbnailSettings['last_run_timestamp']): ?>
                <p class="status-message status-info">Letzte Ausführung am
                    <?php echo date('d.m.Y \u\m H:i:s', $thumbnailSettings['last_run_timestamp']); ?> Uhr (Format:
                    <?php echo htmlspecialchars(strtoupper($thumbnailSettings['last_used_format'])); ?>, Qualität:
                    <?php echo $thumbnailSettings['last_used_lossless'] ? 'Verlustfrei' : $thumbnailSettings['last_used_quality']; ?>).
                </p>
            <?php endif; ?>
        </div>

        <div id="settings-and-actions-container">
            <h2>Einstellungen & Status</h2>
            <p>Dieses Tool generiert Vorschaubilder (Thumbnails) für die Lesezeichen-Funktion und das Archiv.</p>
            <div class="settings-grid">
                <div class="format-switcher">
                    <label>Ausgabeformat:</label>
                    <div class="toggle-buttons">
                        <input type="radio" id="format-webp" name="format-toggle" value="webp"><label
                            for="format-webp">WebP</label>
                        <input type="radio" id="format-png" name="format-toggle" value="png"><label
                            for="format-png">PNG</label>
                        <input type="radio" id="format-jpg" name="format-toggle" value="jpg"><label
                            for="format-jpg">JPG</label>
                    </div>
                </div>
                <div id="quality-control-container">
                    <label for="quality-slider">Qualität: <span id="quality-value">90</span></label>
                    <input type="range" id="quality-slider" min="1" max="100" value="90" class="slider">
                </div>
                <div id="lossless-control-container">
                    <label class="checkbox-label"><input type="checkbox" id="lossless-checkbox">Verlustfrei</label>
                </div>
            </div>
            <div id="fixed-buttons-container">
                <button type="button" id="generate-thumbnails-button" <?php echo empty($missingThumbnails) ? 'disabled' : ''; ?>>Fehlende Thumbnails erstellen</button>
                <button type="button" id="toggle-pause-resume-button" class="hidden-by-default"></button>
            </div>
        </div>

        <div id="generation-results-section" class="hidden-by-default">
            <h2 class="results-header">Ergebnisse der Generierung</h2>
            <div id="created-images-container" class="image-grid"></div>
            <h3 class="status-red hidden-by-default" id="error-header">Protokoll & Fehler:</h3>
            <ul id="generation-log-list"></ul>
        </div>

        <div id="cache-update-notification" class="notification-box hidden-by-default">
            <h4>Nächster Schritt: Cache aktualisieren</h4>
            <p>Da neue Thumbnails hinzugefügt wurden, muss die Cache-JSON-Datei aktualisiert werden.</p>
            <a href="build_image_cache_and_busting.php?autostart=thumbnails" class="button">Cache jetzt
                aktualisieren</a>
        </div>

        <div id="loading-spinner" class="hidden-by-default">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Thumbnails...</p>
        </div>

        <div id="initial-status-container">
            <?php if (empty($allComicIds)): ?>
                <p class="status-message status-orange">Keine Comic-Bilder in den Verzeichnissen gefunden.</p>
            <?php elseif (empty($missingThumbnails)): ?>
                <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Thumbnails sind vorhanden.
                </p>
            <?php else: ?>
                <p class="status-message status-red">Es fehlen <?php echo count($missingThumbnails); ?> Thumbnails.</p>
                <h3>Fehlende Thumbnails (IDs):</h3>
                <div id="missing-thumbnails-grid" class="missing-items-grid">
                    <?php foreach ($missingThumbnails as $id): ?>
                        <span class="missing-item"
                            data-comic-id="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?></span>
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
        width: 187px;
        height: 260px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
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
    }

    .image-item span {
        word-break: break-all;
        font-size: 0.8em;
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

    .notification-box {
        border: 1px solid #bee5eb;
        background-color: #d1ecf1;
        color: #0c5460;
        padding: 15px;
        border-radius: 5px;
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

    .settings-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 15px 20px;
        align-items: center;
        margin-bottom: 20px;
        max-width: 500px;
    }

    .format-switcher {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    #quality-control-container,
    #lossless-control-container {
        display: contents;
    }

    .slider {
        width: 100%;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        user-select: none;
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

    #fixed-buttons-container {
        z-index: 1000;
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        #fixed-buttons-container {
            flex-direction: column;
            gap: 5px;
            align-items: flex-end;
        }
    }

    .hidden-by-default {
        display: none;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // KORREKTUR: Alle Konstanten, die auf DOM-Elemente zugreifen,
        // müssen INNERHALB des DOMContentLoaded-Listeners definiert werden.
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const settings = <?php echo json_encode($thumbnailSettings); ?>;
        const settingsAndActionsContainer = document.getElementById('settings-and-actions-container');
        const initialStatusContainer = document.getElementById('initial-status-container');
        const generateButton = document.getElementById('generate-thumbnails-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingThumbnailsGrid = document.getElementById('missing-thumbnails-grid');
        const createdImagesContainer = document.getElementById('created-images-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const errorHeader = document.getElementById('error-header');
        const logList = document.getElementById('generation-log-list');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');
        const qualitySlider = document.getElementById('quality-slider');
        const qualityValueSpan = document.getElementById('quality-value');
        const losslessCheckbox = document.getElementById('lossless-checkbox');
        const formatToggleInputs = document.querySelectorAll('input[name="format-toggle"]');
        const lastRunContainer = document.getElementById('last-run-container');
        const qualityControlContainer = document.getElementById('quality-control-container'); // Diese Zeile war das Problem
        const initialMissingIds = <?php echo json_encode($missingThumbnails); ?>;
        let isPaused = false;
        let isGenerationActive = false;

        function applySettings() { document.querySelector(`input[name="format-toggle"][value="${settings.last_used_format}"]`).checked = true; qualitySlider.value = settings.last_used_quality; qualityValueSpan.textContent = settings.last_used_quality; losslessCheckbox.checked = settings.last_used_lossless; updateUiFromSettings(); }
        function updateUiFromSettings() { const selectedFormat = document.querySelector('input[name="format-toggle"]:checked').value; const isLossless = losslessCheckbox.checked; qualityControlContainer.style.display = (isLossless || selectedFormat === 'png') ? 'none' : 'contents'; losslessCheckbox.parentElement.parentElement.style.display = (selectedFormat === 'webp') ? 'contents' : 'none'; }
        qualitySlider.addEventListener('input', () => { qualityValueSpan.textContent = qualitySlider.value; });
        losslessCheckbox.addEventListener('change', updateUiFromSettings);
        formatToggleInputs.forEach(input => input.addEventListener('change', updateUiFromSettings));

        function addLogMessage(message, type) { const li = document.createElement('li'); li.className = `log-${type}`; li.textContent = `[${new Date().toLocaleTimeString()}] ${message}`; logList.appendChild(li); errorHeader.style.display = 'block'; li.scrollIntoView({ behavior: 'smooth', block: 'end' }); }
        async function saveSettings(format, quality, lossless) { const formData = new URLSearchParams({ action: 'save_settings', csrf_token: csrfToken, format, quality, lossless: lossless.toString() }); await fetch(window.location.href, { method: 'POST', body: formData }); }
        async function generateSingleImage(comicId, format, quality) { const formData = new URLSearchParams({ action: 'generate_single_thumbnail', comic_id: comicId, output_format: format, csrf_token: csrfToken }); if (quality !== null) { formData.append('quality', quality); } try { const response = await fetch(window.location.href, { method: 'POST', body: formData }); if (!response.ok) throw new Error(`Server-Antwort: ${response.status}`); return await response.json(); } catch (error) { return { success: false, message: `Netzwerkfehler: ${error.message}` }; } }

        function updateTimestamp() {
            const now = new Date();
            const settings = {
                format: document.querySelector('input[name="format-toggle"]:checked').value,
                quality: losslessCheckbox.checked ? 'Verlustfrei' : qualitySlider.value,
            };
            const date = now.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = now.toLocaleTimeString('de-DE');
            const newStatusText = `Letzte Ausführung am ${date} um ${time} Uhr (Format: ${settings.format.toUpperCase()}, Qualität: ${settings.quality}).`;

            let pElement = lastRunContainer.querySelector('.status-message');
            if (!pElement) {
                pElement = document.createElement('p');
                pElement.className = 'status-message status-info';
                lastRunContainer.prepend(pElement);
            }
            pElement.innerHTML = newStatusText;
        }

        async function processGenerationQueue() {
            const userFormat = document.querySelector('input[name="format-toggle"]:checked').value;
            const userQuality = parseInt(qualitySlider.value, 10);
            const userLossless = losslessCheckbox.checked;
            isGenerationActive = true;
            updateButtonState();
            loadingSpinner.style.display = 'block';
            generationResultsSection.style.display = 'block';
            createdImagesContainer.innerHTML = '';
            logList.innerHTML = '';
            errorHeader.style.display = 'none';
            cacheUpdateNotification.style.display = 'none';
            let createdCount = 0;
            let lastSuccessfulSettings = {};
            for (let i = 0; i < initialMissingIds.length; i++) {
                if (isPaused) { await new Promise(resolve => { const interval = setInterval(() => { if (!isPaused) { clearInterval(interval); resolve(); } }, 200); }); }
                const currentId = initialMissingIds[i];
                progressText.textContent = `Verarbeite ${i + 1} von ${initialMissingIds.length} (${currentId})...`;
                const fallbackChain = [];
                if (userFormat === 'webp') {
                    if (userLossless) fallbackChain.push({ format: 'webp', quality: 101, lossless: true });
                    fallbackChain.push({ format: 'webp', quality: userQuality, lossless: false });
                    fallbackChain.push({ format: 'png', quality: 9, lossless: false });
                    fallbackChain.push({ format: 'jpg', quality: 90, lossless: false });
                } else if (userFormat === 'png') {
                    fallbackChain.push({ format: 'png', quality: 9, lossless: false });
                    fallbackChain.push({ format: 'jpg', quality: 90, lossless: false });
                } else {
                    fallbackChain.push({ format: 'jpg', quality: userQuality, lossless: false });
                }
                let success = false;
                for (const attempt of fallbackChain) {
                    addLogMessage(`Versuch für '${currentId}': Format=${attempt.format}, Qualität=${attempt.quality}`, 'info');
                    const result = await generateSingleImage(currentId, attempt.format, attempt.quality);
                    if (result.success) {
                        createdCount++;
                        const imageDiv = document.createElement('div');
                        imageDiv.className = 'image-item';
                        imageDiv.innerHTML = `<img src="${result.imageUrl}" alt="Thumbnail ${result.comicId}"><span>${result.comicId}</span>`;
                        createdImagesContainer.appendChild(imageDiv);
                        const missingItem = missingThumbnailsGrid.querySelector(`span[data-comic-id="${result.comicId}"]`);
                        if (missingItem) missingItem.remove();
                        lastSuccessfulSettings = attempt;
                        addLogMessage(`Erfolgreich für '${currentId}' mit Format ${attempt.format}.`, 'success');
                        success = true;
                        break;
                    } else {
                        addLogMessage(`Fehlgeschlagen für '${currentId}' mit Format ${attempt.format}: ${result.message}`, 'warning');
                    }
                }
                if (!success) { addLogMessage(`Alle Versuche für '${currentId}' fehlgeschlagen. Breche für dieses Bild ab.`, 'error'); }
                await new Promise(resolve => setTimeout(resolve, 50));
            }
            loadingSpinner.style.display = 'none';
            isGenerationActive = false;
            progressText.textContent = 'Prozess abgeschlossen.';
            if (settingsAndActionsContainer) settingsAndActionsContainer.style.display = 'none';
            if (initialStatusContainer) initialStatusContainer.style.display = 'none';
            if (Object.keys(lastSuccessfulSettings).length > 0) {
                await saveSettings(lastSuccessfulSettings.format, lastSuccessfulSettings.quality, lastSuccessfulSettings.lossless);
                addLogMessage(`Erfolgreiche Einstellungen (Format: ${lastSuccessfulSettings.format}, Qualität: ${lastSuccessfulSettings.quality}) wurden gespeichert.`, 'info');
                updateTimestamp();
            }
            if (createdCount > 0) {
                cacheUpdateNotification.style.display = 'block';
            }
        }
        function updateButtonState() { if (initialMissingIds.length === 0) { generateButton.disabled = true; togglePauseResumeButton.style.display = 'none'; } else if (isGenerationActive) { settingsAndActionsContainer.style.display = 'none'; togglePauseResumeButton.style.display = 'inline-block'; if (isPaused) { togglePauseResumeButton.textContent = 'Fortsetzen'; togglePauseResumeButton.className = 'status-green-button'; } else { togglePauseResumeButton.textContent = 'Pause'; togglePauseResumeButton.className = 'status-red-button'; } } else { settingsAndActionsContainer.style.display = 'block'; generateButton.disabled = false; togglePauseResumeButton.style.display = 'none'; } }

        generateButton.addEventListener('click', processGenerationQueue);
        togglePauseResumeButton.addEventListener('click', () => { isPaused = !isPaused; updateButtonState(); });

        // Initialen Zustand setzen
        applySettings();
        updateButtonState();
    });
</script>

<?php
if (file_exists($footerPath)) {
    include $footerPath;
}
?>