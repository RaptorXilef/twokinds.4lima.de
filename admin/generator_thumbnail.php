<?php
/**
 * Dies ist die Administrationsseite für den Thumbnail-Generator.
 * Sie überprüft, welche Thumbnails fehlen und bietet die Möglichkeit, diese zu erstellen.
 * Diese Version kombiniert die Generierung für JPG, PNG und WebP in einer einzigen Datei.
 * Der Benutzer kann das gewünschte Ausgabeformat über ein Frontend-Steuerelement auswählen.
 * Die Generierung erfolgt schrittweise über AJAX, um Speicherprobleme zu vermeiden.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG (enthält Nonce und CSRF-Setup) ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$lowresDir = __DIR__ . '/../assets/comic_lowres/';
$hiresDir = __DIR__ . '/../assets/comic_hires/';
$thumbnailDir = __DIR__ . '/../assets/comic_thumbnails/';

// Setze Parameter für den Header.
$pageTitle = 'Adminbereich - Thumbnail Generator';
$pageHeader = 'Thumbnail Generator';
$siteDescription = 'Seite zum Generieren der Vorschaubilder.';
$robotsContent = 'noindex, nofollow'; // Diese Seite soll nicht indexiert werden
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

// GD-Bibliothek-Check
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Thumbnails können nicht generiert werden.";
    error_log($gdError);
} else {
    $gdError = null;
}

/**
 * Scannt die Comic-Verzeichnisse nach vorhandenen Comic-Bildern.
 */
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
    if ($debugMode)
        error_log("DEBUG: " . count($comicIds) . " eindeutige Comic-IDs gefunden.");
    return array_keys($comicIds);
}

/**
 * Scannt das Thumbnail-Verzeichnis nach vorhandenen Thumbnails.
 */
function getExistingThumbnailIds(string $thumbnailDir, bool $debugMode): array
{
    $thumbnailIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (is_dir($thumbnailDir)) {
        $files = scandir($thumbnailDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $thumbnailIds[$fileInfo['filename']] = true; // Verwende Keys für Eindeutigkeit
            }
        }
        if ($debugMode)
            error_log("DEBUG: " . count($thumbnailIds) . " Thumbnails im Verzeichnis gefunden.");
    }
    return array_keys($thumbnailIds);
}


/**
 * Vergleicht Comic-IDs mit Thumbnail-IDs, um fehlende zu finden.
 */
function findMissingThumbnails(array $allComicIds, array $existingThumbnailIds, bool $debugMode): array
{
    $missing = array_values(array_diff($allComicIds, $existingThumbnailIds));
    if ($debugMode)
        error_log("DEBUG: Fehlende Thumbnails gefunden: " . count($missing));
    return $missing;
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
function generateThumbnail(string $comicId, string $outputFormat, string $lowresDir, string $hiresDir, string $thumbnailDir, bool $debugMode): array
{
    $errors = [];
    $createdPath = '';
    if ($debugMode)
        error_log("DEBUG: Starte Generierung für ID '$comicId' im Format '$outputFormat'.");

    // Zielverzeichnis prüfen und erstellen
    if (!is_dir($thumbnailDir)) {
        if (!mkdir($thumbnailDir, 0755, true)) {
            $errors[] = "Zielverzeichnis '$thumbnailDir' konnte nicht erstellt werden.";
            return ['created' => '', 'errors' => $errors];
        }
    } elseif (!is_writable($thumbnailDir)) {
        $errors[] = "Zielverzeichnis '$thumbnailDir' ist nicht beschreibbar.";
        return ['created' => '', 'errors' => $errors];
    }

    // Ziel-Dimensionen
    $targetWidth = 198;
    $targetHeight = 258;

    // Quellbild finden
    $sourceImagePath = '';
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($possibleExtensions as $ext) {
        if (file_exists($hiresDir . $comicId . '.' . $ext)) {
            $sourceImagePath = $hiresDir . $comicId . '.' . $ext;
            break;
        }
        if (file_exists($lowresDir . $comicId . '.' . $ext)) {
            $sourceImagePath = $lowresDir . $comicId . '.' . $ext;
            break;
        }
    }

    if (empty($sourceImagePath)) {
        $errors[] = "Quellbild für Comic-ID '$comicId' nicht gefunden.";
        return ['created' => '', 'errors' => $errors];
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
                $saveSuccess = imagejpeg($tempImage, $thumbnailPath, 90);
                break;
            case 'png':
                $saveSuccess = imagepng($tempImage, $thumbnailPath, 9);
                break;
            case 'webp':
                // Prüfen, ob WebP-Unterstützung vorhanden ist
                if (function_exists('imagewebp')) {
                    // KORREKTUR: Verwende eine Standard-Qualität statt der speziellen '101' für verlustfrei.
                    // Dies ist kompatibler mit verschiedenen Server-GD-Versionen.
                    $saveSuccess = imagewebp($tempImage, $thumbnailPath, 90);
                } else {
                    $errors[] = "WebP-Unterstützung ist auf diesem Server nicht aktiviert.";
                }
                break;
        }

        if ($saveSuccess) {
            $createdPath = $thumbnailPath;
        } else {
            if (empty($errors)) {
                // KORREKTUR: Verbesserte Fehlermeldung und Aufräumen von 0-Byte-Dateien.
                if (file_exists($thumbnailPath) && filesize($thumbnailPath) === 0) {
                    unlink($thumbnailPath); // Lösche die leere Datei
                    $errors[] = "Fehler beim Speichern des WebP-Bildes (leere Datei erstellt). Dies deutet oft auf ein Problem mit der GD-Bibliothek-Konfiguration auf dem Server hin.";
                } else {
                    $errors[] = "Fehler beim Speichern des Thumbnails nach '$thumbnailPath'.";
                }
            }
        }

        // Speicher freigeben
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


// --- AJAX-Anfrage-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_single_thumbnail') {
    // SICHERHEIT: CSRF-Token validieren
    verify_csrf_token();
    ob_end_clean();
    ini_set('display_errors', 0);
    error_reporting(0);
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];

    if (!extension_loaded('gd')) {
        $response['message'] = "FEHLER: Die GD-Bibliothek ist nicht geladen.";
        echo json_encode($response);
        exit;
    }

    // Das vom Frontend ausgewählte Format auslesen, Standard ist 'webp'
    $outputFormat = $_POST['output_format'] ?? 'webp';
    if (!in_array($outputFormat, ['jpg', 'png', 'webp'])) {
        $outputFormat = 'webp'; // Sicherheits-Fallback
    }

    $comicId = $_POST['comic_id'] ?? '';
    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID angegeben.';
        echo json_encode($response);
        exit;
    }

    // Thumbnail mit dem ausgewählten Format generieren
    $result = generateThumbnail($comicId, $outputFormat, $lowresDir, $hiresDir, $thumbnailDir, $debugMode);

    if (empty($result['errors'])) {
        $response['success'] = true;
        $response['message'] = "Thumbnail für $comicId als .$outputFormat erstellt.";
        // Die URL mit der korrekten, dynamischen Dateiendung zurückgeben
        $response['imageUrl'] = '../assets/comic_thumbnails/' . $comicId . '.' . $outputFormat . '?' . time();
        $response['comicId'] = $comicId;
    } else {
        $response['message'] = 'Fehler bei ' . $comicId . ': ' . implode(', ', $result['errors']);
    }

    echo json_encode($response);
    exit;
}
// --- Ende AJAX-Anfrage-Handler ---


ob_end_flush();

// Variablen für die Anzeige initialisieren
$allComicIds = getExistingComicIds($lowresDir, $hiresDir, $debugMode);
$existingThumbnailIds = getExistingThumbnailIds($thumbnailDir, $debugMode);
$missingThumbnails = findMissingThumbnails($allComicIds, $existingThumbnailIds, $debugMode);

// Header einbinden
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    echo "<!DOCTYPE html><html><head><title>Fehler</title></head><body><h1>Header nicht gefunden!</h1>";
}
?>

<article>
    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
        <?php endif; ?>

        <h2>Einstellungen & Status</h2>

        <!-- Format-Umschalter -->
        <div class="format-switcher">
            <label>Ausgabeformat:</label>
            <div class="toggle-buttons">
                <input type="radio" id="format-webp" name="format-toggle" value="webp" checked>
                <label for="format-webp">WebP</label>
                <input type="radio" id="format-png" name="format-toggle" value="png">
                <label for="format-png">PNG</label>
                <input type="radio" id="format-jpg" name="format-toggle" value="jpg">
                <label for="format-jpg">JPG</label>
            </div>
        </div>

        <div id="fixed-buttons-container">
            <button type="button" id="generate-thumbnails-button" <?php echo $gdError || empty($missingThumbnails) ? 'disabled' : ''; ?>>Fehlende Thumbnails erstellen</button>
            <button type="button" id="toggle-pause-resume-button"></button>
        </div>

        <div id="generation-results-section">
            <h2 class="results-header">Ergebnisse der Generierung</h2>
            <p id="overall-status-message" class="status-message"></p>
            <div id="created-images-container" class="image-grid"></div>
            <p class="status-message status-red" id="error-header-message">Fehler:</p>
            <ul id="generation-errors-list"></ul>
        </div>

        <div id="cache-update-notification" class="notification-box">
            <h4>Nächster Schritt: Cache aktualisieren</h4>
            <p>
                Da neue Thumbnails hinzugefügt wurden, muss die Cache-JSON-Datei aktualisiert werden.
                <br>
                <strong>Hinweis:</strong> Führe diesen Schritt erst aus, wenn alle Bilder generiert sind, da der Prozess
                kurzzeitig hohe Serverlast verursachen kann.
            </p>
            <a href="build_image_cache_and_busting.php?autostart=thumbnails" class="button">Cache jetzt
                aktualisieren</a>
        </div>

        <div id="loading-spinner">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Thumbnails...</p>
        </div>

        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Keine Comic-Bilder in den Verzeichnissen gefunden.</p>
        <?php elseif (empty($missingThumbnails)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Thumbnails sind vorhanden.</p>
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
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
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
    }

    .status-red-button {
        background-color: #dc3545;
        border: 1px solid #dc3545;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-red-button:disabled,
    .status-green-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #e9ecef;
        cursor: not-allowed;
    }

    .status-green-button {
        background-color: #28a745;
        border: 1px solid #28a745;
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

    .format-switcher {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
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

    /* KORREKTUREN FÜR CSP */
    #toggle-pause-resume-button,
    #generation-results-section,
    #error-header-message,
    #cache-update-notification,
    #loading-spinner {
        display: none;
    }

    #generation-results-section,
    #cache-update-notification,
    #loading-spinner {
        margin-top: 20px;
    }

    .results-header {
        margin-top: 20px;
    }

    #loading-spinner {
        text-align: center;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const generateButton = document.getElementById('generate-thumbnails-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingThumbnailsGrid = document.getElementById('missing-thumbnails-grid');
        const createdImagesContainer = document.getElementById('created-images-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const overallStatusMessage = document.getElementById('overall-status-message');
        const errorHeaderMessage = document.getElementById('error-header-message');
        const errorsList = document.getElementById('generation-errors-list');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');

        const initialMissingIds = <?php echo json_encode($missingThumbnails); ?>;
        let remainingIds = [...initialMissingIds];
        let createdCount = 0;
        let errorCount = 0;
        let isPaused = false;
        let isGenerationActive = false;

        // Sticky-Buttons Logik
        const mainContent = document.getElementById('content');
        const fixedButtonsContainer = document.getElementById('fixed-buttons-container');
        if (!fixedButtonsContainer) return;
        let initialButtonTopOffset, stickyThreshold;
        const stickyOffset = 18, rightOffset = 24;

        function calculateInitialPositions() {
            fixedButtonsContainer.style.position = 'static';
            initialButtonTopOffset = fixedButtonsContainer.getBoundingClientRect().top + window.scrollY;
            stickyThreshold = initialButtonTopOffset - stickyOffset;
        }

        function handleScroll() {
            if (window.scrollY >= stickyThreshold) {
                if (fixedButtonsContainer.style.position !== 'fixed') {
                    fixedButtonsContainer.style.position = 'fixed';
                    fixedButtonsContainer.style.top = `${stickyOffset}px`;
                    if (mainContent) {
                        const mainRect = mainContent.getBoundingClientRect();
                        fixedButtonsContainer.style.right = (window.innerWidth - mainRect.right + rightOffset) + 'px';
                    } else {
                        fixedButtonsContainer.style.right = `${rightOffset}px`;
                    }
                }
            } else {
                if (fixedButtonsContainer.style.position === 'fixed') {
                    fixedButtonsContainer.style.position = 'static';
                }
            }
        }
        calculateInitialPositions();
        handleScroll();
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('resize', () => { calculateInitialPositions(); handleScroll(); });


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
                    togglePauseResumeButton.className = 'status-orange-button';
                }
            } else {
                generateButton.style.display = 'inline-block';
                generateButton.disabled = false;
                togglePauseResumeButton.style.display = 'none';
            }
        }

        generateButton.addEventListener('click', function () {
            if (remainingIds.length === 0) return;

            cacheUpdateNotification.style.display = 'none';

            createdImagesContainer.innerHTML = '';
            errorsList.innerHTML = '';
            errorHeaderMessage.style.display = 'none';
            overallStatusMessage.style.display = 'none';
            generationResultsSection.style.display = 'block';

            createdCount = 0;
            errorCount = 0;
            isPaused = false;
            isGenerationActive = true;
            updateButtonState();

            loadingSpinner.style.display = 'block';
            processNextImage();
        });

        togglePauseResumeButton.addEventListener('click', function () {
            isPaused = !isPaused;
            updateButtonState();
            if (!isPaused) {
                processNextImage();
            }
        });

        async function processNextImage() {
            if (isPaused) return;

            if (remainingIds.length === 0) {
                loadingSpinner.style.display = 'none';
                isGenerationActive = false;
                updateButtonState();

                if (errorCount > 0) {
                    overallStatusMessage.textContent = `Abgeschlossen mit Fehlern: ${createdCount} erfolgreich, ${errorCount} Fehler.`;
                    overallStatusMessage.className = 'status-message status-orange';
                } else {
                    overallStatusMessage.textContent = `Alle ${createdCount} Thumbnails erfolgreich generiert!`;
                    overallStatusMessage.className = 'status-message status-green';
                }
                overallStatusMessage.style.display = 'block';
                if (createdCount > 0) {
                    cacheUpdateNotification.style.display = 'block';
                }
                return;
            }

            const currentId = remainingIds.shift();
            progressText.textContent = `Generiere ${createdCount + errorCount + 1} von ${initialMissingIds.length} (${currentId})...`;

            const selectedFormat = document.querySelector('input[name="format-toggle"]:checked').value;

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'generate_single_thumbnail',
                        comic_id: currentId,
                        output_format: selectedFormat,
                        csrf_token: csrfToken
                    })
                });

                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    throw new Error(`Ungültige JSON-Antwort vom Server.`);
                }

                if (data.success) {
                    createdCount++;
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'image-item';
                    imageDiv.innerHTML = `<img src="${data.imageUrl}" alt="Thumbnail ${data.comicId}"><span>${data.comicId}</span>`;
                    createdImagesContainer.appendChild(imageDiv);

                    if (missingThumbnailsGrid) {
                        const missingItemSpan = missingThumbnailsGrid.querySelector(`span[data-comic-id="${data.comicId}"]`);
                        if (missingItemSpan) {
                            missingItemSpan.remove();
                        }
                    }
                } else {
                    errorCount++;
                    const errorItem = document.createElement('li');
                    errorItem.textContent = data.message;
                    errorsList.appendChild(errorItem);
                    errorHeaderMessage.style.display = 'block';
                }
            } catch (error) {
                errorCount++;
                const errorItem = document.createElement('li');
                errorItem.textContent = `Netzwerkfehler bei ${currentId}: ${error.message}`;
                errorsList.appendChild(errorItem);
                errorHeaderMessage.style.display = 'block';
            }

            setTimeout(processNextImage, 1000);
        }
        updateButtonState();
    });
</script>

<?php
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "</body></html>";
}
?>