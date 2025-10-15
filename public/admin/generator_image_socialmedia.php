<?php
/**
 * Administrationsseite für den Social Media Bild-Generator.
 * 
 * @file      ROOT/public/admin/generator_image_socialmedia.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.0.0 Vollständig überarbeitet mit der intelligenten Logik des Thumbnail-Generators.
 * Bietet Qualitätsregler, verlustfreie Option, eine robuste Fallback-Automatik (WebP -> PNG -> JPG) und speichert Benutzereinstellungen.
 * @since     2.1.0 Integriert Debug-Funktionen.
 * @since     2.2.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     3.0.0 Vollständige Umstellung auf neueste Konstanten-Struktur.
 * @since     4.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin_init.php';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'generator_socialmedia' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_used_resize_mode' => 'crop', 'last_run_timestamp' => null],
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
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $defaults;
    }
    if (!isset($settings['generator_socialmedia']))
        $settings['generator_socialmedia'] = $defaults['generator_socialmedia'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// GD-Bibliothek-Check
$gdError = !extension_loaded('gd') ? "FEHLER: Die GD-Bibliothek ist nicht geladen. Bilder können nicht generiert werden." : null;
if ($gdError)
    error_log($gdError);

// Helper-Funktionen
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
    return array_keys($comicIds);
}

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
    return array_keys($imageIds);
}

function findMissingSocialMediaImages(array $allComicIds, array $existingImageIds, bool $debugMode): array
{
    return array_values(array_diff($allComicIds, $existingImageIds));
}

function generateSocialMediaImage(string $comicId, string $outputFormat, string $resizeMode, string $hiresDir, string $lowresDir, string $socialMediaImageDir, bool $debugMode, array $options = []): array
{
    if ($debugMode)
        error_log("DEBUG: Starte Generierung für ID '$comicId' | Modus: '$resizeMode' | Format: '$outputFormat' | Qualität: " . ($options['quality'] ?? 'default'));
    $errors = [];
    if (!is_dir($socialMediaImageDir) && !mkdir($socialMediaImageDir, 0755, true)) {
        return ['created' => '', 'errors' => ["Zielverzeichnis '$socialMediaImageDir' konnte nicht erstellt werden."]];
    }
    if (!is_writable($socialMediaImageDir)) {
        return ['created' => '', 'errors' => ["Zielverzeichnis '$socialMediaImageDir' ist nicht beschreibbar."]];
    }
    $targetWidth = 1200;
    $targetHeight = 630;
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
        if ($resizeMode === 'crop') {
            $srcRatio = $width / $height;
            $targetRatio = $targetWidth / $targetHeight;
            $srcX = 0;
            $srcY = 0;
            $srcW = $width;
            $srcH = $height;
            if ($srcRatio > $targetRatio) {
                $srcW = $height * $targetRatio;
                $srcX = ($width - $srcW) / 2;
            } else {
                $srcH = $width / $targetRatio;
            }
            imagecopyresampled($tempImage, $sourceImage, 0, 0, (int) $srcX, (int) $srcY, $targetWidth, $targetHeight, (int) $srcW, (int) $srcH);
        } else {
            $scale = min($targetWidth / $width, $targetHeight / $height);
            $newWidth = $width * $scale;
            $newHeight = $height * $scale;
            $offsetX = ($targetWidth - $newWidth) / 2;
            $offsetY = ($targetHeight - $newHeight) / 2;
            imagecopyresampled($tempImage, $sourceImage, (int) $offsetX, (int) $offsetY, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);
        }
        $socialMediaImagePath = $socialMediaImageDir . $comicId . '.' . $outputFormat;
        $saveSuccess = false;
        switch ($outputFormat) {
            case 'jpg':
                $saveSuccess = imagejpeg($tempImage, $socialMediaImagePath, $options['quality'] ?? 90);
                break;
            case 'png':
                $saveSuccess = imagepng($tempImage, $socialMediaImagePath, $options['quality'] ?? 9);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $quality = $options['quality'] ?? 90;
                    $saveSuccess = imagewebp($tempImage, $socialMediaImagePath, $quality);
                } else {
                    $errors[] = "WebP-Unterstützung nicht aktiviert.";
                }
                break;
        }
        $createdPath = '';
        if ($saveSuccess && filesize($socialMediaImagePath) > 0) {
            $createdPath = $socialMediaImagePath;
        } else {
            if (file_exists($socialMediaImagePath))
                unlink($socialMediaImagePath);
            if (empty($errors))
                $errors[] = "Fehler beim Speichern des Bildes (evtl. 0-Byte-Datei).";
        }
        imagedestroy($sourceImage);
        imagedestroy($tempImage);
        return ['created' => $createdPath, 'errors' => $errors];
    } catch (Throwable $e) {
        return ['created' => '', 'errors' => ["Ausnahme: " . $e->getMessage()]];
    } finally {
        gc_collect_cycles();
        usleep(50000);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    $generatorSettingsJsonPath = Path::getConfig('config_generator_settings.json');

    switch ($action) {
        case 'generate_single_social_media_image':
            if ($gdError) {
                $response['message'] = $gdError;
                http_response_code(500);
                echo json_encode($response);
                exit;
            }
            $outputFormat = in_array($_POST['output_format'] ?? '', ['jpg', 'png', 'webp']) ? $_POST['output_format'] : 'webp';
            $resizeMode = in_array($_POST['resize_mode'] ?? '', ['crop', 'fit']) ? $_POST['resize_mode'] : 'crop';
            $comicId = $_POST['comic_id'] ?? '';
            if (empty($comicId)) {
                $response['message'] = 'Keine Comic-ID.';
                http_response_code(400);
            } else {
                $options = ['quality' => $_POST['quality'] ?? null];
                $result = generateSocialMediaImage($comicId, $outputFormat, $resizeMode, DIRECTORY_PUBLIC_IMG_COMIC_HIRES . DIRECTORY_SEPARATOR, DIRECTORY_PUBLIC_IMG_COMIC_LOWRES . DIRECTORY_SEPARATOR, DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA . DIRECTORY_SEPARATOR, $debugMode, $options);
                if (empty($result['errors'])) {
                    $response['success'] = true;
                    $response['message'] = "Bild für $comicId als .$outputFormat ($resizeMode) erstellt.";
                    $response['imageUrl'] = Path::getSocialMedia($comicId . '.' . $outputFormat) . '?' . time();
                    $response['comicId'] = $comicId;
                } else {
                    $response['message'] = 'Fehler bei ' . $comicId . ': ' . implode(', ', $result['errors']);
                    http_response_code(500);
                }
            }
            break;
        case 'save_settings':
            $currentSettings = loadGeneratorSettings($generatorSettingsJsonPath, $debugMode);
            $newSocialMediaSettings = [
                'last_used_format' => $_POST['format'] ?? 'webp',
                'last_used_quality' => (int) ($_POST['quality'] ?? 90),
                'last_used_lossless' => ($_POST['lossless'] === 'true'),
                'last_used_resize_mode' => $_POST['resize_mode'] ?? 'crop',
                'last_run_timestamp' => time()
            ];
            $currentSettings['generator_socialmedia'] = $newSocialMediaSettings;
            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings(Path::getConfig('config_generator_settings.json'), $debugMode);
$socialMediaSettings = $settings['generator_socialmedia'];
$allComicIds = getExistingComicIds(DIRECTORY_PUBLIC_IMG_COMIC_HIRES . DIRECTORY_SEPARATOR, DIRECTORY_PUBLIC_IMG_COMIC_LOWRES . DIRECTORY_SEPARATOR, $debugMode);
$existingSocialMediaImageIds = getExistingSocialMediaImageIds(DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA . DIRECTORY_SEPARATOR, $debugMode);
$missingSocialMediaImages = findMissingSocialMediaImages($allComicIds, $existingSocialMediaImageIds, $debugMode);

$pageTitle = 'Adminbereich - Social Media Bild-Generator';
$pageHeader = 'Social Media Bild-Generator';
$siteDescription = 'Seite zum Generieren der Sozial Media Vorschaubilder.';

include Path::getTemplatePartial('header.php');
?>

<article>
    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
        <?php endif; ?>

        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($socialMediaSettings['last_run_timestamp']): ?>
                    <p class="status-message status-info">Letzte Ausführung am
                        <?php echo date('d.m.Y \u\m H:i:s', $socialMediaSettings['last_run_timestamp']); ?> Uhr (Modus:
                        <?php echo htmlspecialchars(ucfirst($socialMediaSettings['last_used_resize_mode'])); ?>, Format:
                        <?php echo htmlspecialchars(strtoupper($socialMediaSettings['last_used_format'])); ?>, Qualität:
                        <?php echo $socialMediaSettings['last_used_lossless'] ? 'Verlustfrei' : $socialMediaSettings['last_used_quality']; ?>).
                    </p>
                <?php endif; ?>
            </div>

            <h2>Einstellungen & Status</h2>
            <p>Generiert Vorschaubilder (1200x630 Pixel) für Social-Media-Plattformen wie Facebook, Twitter etc.</p>
            <div class="settings-grid">
                <div class="format-switcher">
                    <label>Modus:</label>
                    <div class="toggle-buttons">
                        <input type="radio" id="mode-crop" name="mode-toggle" value="crop">
                        <label for="mode-crop">Zuschneiden</label>
                        <input type="radio" id="mode-fit" name="mode-toggle" value="fit">
                        <label for="mode-fit">Anpassen</label>
                    </div>
                </div>
                <div class="format-switcher">
                    <label>Format:</label>
                    <div class="toggle-buttons">
                        <input type="radio" id="format-webp" name="format-toggle" value="webp">
                        <label for="format-webp">WebP</label>
                        <input type="radio" id="format-png" name="format-toggle" value="png">
                        <label for="format-png">PNG</label>
                        <input type="radio" id="format-jpg" name="format-toggle" value="jpg">
                        <label for="format-jpg">JPG</label>
                    </div>
                </div>
                <div id="quality-control-container">
                    <label for="quality-slider">Qualität: <span id="quality-value">90</span></label>
                    <input type="range" id="quality-slider" min="1" max="100" value="90" class="slider">
                </div>
                <div id="lossless-control-container">
                    <label class="checkbox-label">
                        <input type="checkbox" id="lossless-checkbox">
                        Verlustfrei
                    </label>
                </div>
            </div>
            <div id="fixed-buttons-container">
                <button type="button" id="generate-images-button" <?php echo $gdError || empty($missingSocialMediaImages) ? 'disabled' : ''; ?>>Fehlende Bilder erstellen</button>
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
            <p>
                Da neue Bilder hinzugefügt wurden, muss die Cache-JSON-Datei aktualisiert werden.
                <br>
                <strong>Hinweis:</strong> Führe diesen Schritt erst aus, wenn alle Bilder generiert sind.
            </p>
            <a href="build_image_cache_and_busting.php?autostart=socialmedia" class="button">Cache jetzt
                aktualisieren</a>
        </div>

        <div id="loading-spinner" class="hidden-by-default">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Bilder...</p>
        </div>

        <div id="initial-status-container">
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
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const settings = <?php echo json_encode($socialMediaSettings); ?>;
        const settingsAndActionsContainer = document.getElementById('settings-and-actions-container');
        const initialStatusContainer = document.getElementById('initial-status-container');
        const generateButton = document.getElementById('generate-images-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingImagesGrid = document.getElementById('missing-images-grid');
        const createdImagesContainer = document.getElementById('created-images-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const errorHeader = document.getElementById('error-header');
        const logList = document.getElementById('generation-log-list');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');
        const qualitySlider = document.getElementById('quality-slider');
        const qualityValueSpan = document.getElementById('quality-value');
        const losslessCheckbox = document.getElementById('lossless-checkbox');
        const qualityControlContainer = document.getElementById('quality-control-container');
        const formatToggleInputs = document.querySelectorAll('input[name="format-toggle"]');
        const modeToggleInputs = document.querySelectorAll('input[name="mode-toggle"]');
        const lastRunContainer = document.getElementById('last-run-container');
        const initialMissingIds = <?php echo json_encode($missingSocialMediaImages); ?>;
        let isPaused = false;
        let isGenerationActive = false;

        function applySettings() {
            document.querySelector(`input[name="format-toggle"][value="${settings.last_used_format}"]`).checked = true;
            document.querySelector(`input[name="mode-toggle"][value="${settings.last_used_resize_mode}"]`).checked = true;
            qualitySlider.value = settings.last_used_quality;
            qualityValueSpan.textContent = settings.last_used_quality;
            losslessCheckbox.checked = settings.last_used_lossless;
            updateUiFromSettings();
        }

        function updateUiFromSettings() {
            const selectedFormat = document.querySelector('input[name="format-toggle"]:checked').value;
            const isLossless = losslessCheckbox.checked;
            qualityControlContainer.style.display = (isLossless || selectedFormat === 'png') ? 'none' : 'contents';
            losslessCheckbox.parentElement.parentElement.style.display = (selectedFormat === 'webp') ? 'contents' : 'none';
        }

        qualitySlider.addEventListener('input', () => { qualityValueSpan.textContent = qualitySlider.value; });
        losslessCheckbox.addEventListener('change', updateUiFromSettings);
        formatToggleInputs.forEach(input => input.addEventListener('change', updateUiFromSettings));

        function addLogMessage(message, type) { const li = document.createElement('li'); li.className = `log-${type}`; li.textContent = `[${new Date().toLocaleTimeString()}] ${message}`; logList.appendChild(li); errorHeader.style.display = 'block'; li.scrollIntoView({ behavior: 'smooth', block: 'end' }); }
        async function saveSettings(format, quality, lossless, resizeMode) { const formData = new URLSearchParams({ action: 'save_settings', csrf_token: csrfToken, format, quality, lossless: lossless.toString(), resize_mode: resizeMode }); await fetch(window.location.href, { method: 'POST', body: formData }); }
        async function generateSingleImage(comicId, format, quality, resizeMode) { const formData = new URLSearchParams({ action: 'generate_single_social_media_image', comic_id: comicId, output_format: format, resize_mode: resizeMode, csrf_token: csrfToken }); if (quality !== null) { formData.append('quality', quality); } try { const response = await fetch(window.location.href, { method: 'POST', body: formData }); if (!response.ok) throw new Error(`Server-Antwort: ${response.status}`); return await response.json(); } catch (error) { return { success: false, message: `Netzwerkfehler: ${error.message}` }; } }

        function updateTimestamp() {
            const now = new Date();
            const settings = {
                format: document.querySelector('input[name="format-toggle"]:checked').value,
                quality: losslessCheckbox.checked ? 'Verlustfrei' : qualitySlider.value,
                resize_mode: document.querySelector('input[name="mode-toggle"]:checked').value
            };
            const date = now.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = now.toLocaleTimeString('de-DE');
            const newStatusText = `Letzte Ausführung am ${date} um ${time} Uhr (Modus: ${settings.resize_mode.charAt(0).toUpperCase() + settings.resize_mode.slice(1)}, Format: ${settings.format.toUpperCase()}, Qualität: ${settings.quality}).`;

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
            const userResizeMode = document.querySelector('input[name="mode-toggle"]:checked').value;
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
                    addLogMessage(`Versuch für '${currentId}': Modus=${userResizeMode}, Format=${attempt.format}, Qualität=${attempt.quality}`, 'info');
                    const result = await generateSingleImage(currentId, attempt.format, attempt.quality, userResizeMode);
                    if (result.success) {
                        createdCount++;
                        const imageDiv = document.createElement('div');
                        imageDiv.className = 'image-item';
                        imageDiv.innerHTML = `<img src="${result.imageUrl}" alt="Social Media Bild ${result.comicId}"><span>${result.comicId}</span>`;
                        createdImagesContainer.appendChild(imageDiv);
                        const missingItem = missingImagesGrid.querySelector(`span[data-comic-id="${result.comicId}"]`);
                        if (missingItem) missingItem.remove();
                        lastSuccessfulSettings = { ...attempt, resize_mode: userResizeMode };
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
                await saveSettings(lastSuccessfulSettings.format, lastSuccessfulSettings.quality, lastSuccessfulSettings.lossless, lastSuccessfulSettings.resize_mode);
                addLogMessage(`Erfolgreiche Einstellungen (Modus: ${lastSuccessfulSettings.resize_mode}, Format: ${lastSuccessfulSettings.format}, Qualität: ${lastSuccessfulSettings.quality}) wurden gespeichert.`, 'info');
                updateTimestamp();
            }
            if (createdCount > 0) {
                cacheUpdateNotification.style.display = 'block';
            }
        }
        function updateButtonState() { if (initialMissingIds.length === 0) { generateButton.disabled = true; togglePauseResumeButton.style.display = 'none'; } else if (isGenerationActive) { settingsAndActionsContainer.style.display = 'none'; togglePauseResumeButton.style.display = 'inline-block'; if (isPaused) { togglePauseResumeButton.textContent = 'Fortsetzen'; togglePauseResumeButton.className = 'status-green-button'; } else { togglePauseResumeButton.textContent = 'Pause'; togglePauseResumeButton.className = 'status-red-button'; } } else { settingsAndActionsContainer.style.display = 'block'; generateButton.disabled = false; togglePauseResumeButton.style.display = 'none'; } }

        generateButton.addEventListener('click', processGenerationQueue);
        togglePauseResumeButton.addEventListener('click', () => { isPaused = !isPaused; updateButtonState(); });

        applySettings();
        updateButtonState();
    });
</script>

<?php include Path::getTemplatePartial('footer.php'); ?>