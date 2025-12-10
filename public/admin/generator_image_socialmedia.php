<?php

/**
 * Administrationsseite für den Social Media Bild-Generator.
 *
 * @file      ROOT/public/admin/generator_image_socialmedia.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Integration von Debug-Funktionen für bessere Fehleranalyse.
 *
 *    LOGIK & FEATURES
 *    - Vollständige Überarbeitung mit intelligenter Thumbnail-Logik (Qualitätsregler, verlustfreie Option).
 *    - Robuste Fallback-Automatik (WebP -> PNG -> JPG) und Speicherung der Benutzereinstellungen.
 *
 * @since 5.0.0
 * - Komplettes Refactoring auf Basis des Thumbnail - Generators(SCSS, JS - Logik) .
 * - Einstellung für Ausschnitt-Position (Top, Center, Bottom...) hinzugefügt.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'socialmedia_generator' => [
            'last_run_timestamp' => null,
            'format' => 'webp',
            'quality' => 85,
            'lossless' => false,
            'resize_mode' => 'cover',
            'crop_position' => 'center' // Default: Mitte
        ]
    ];
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
    if (json_last_error() === JSON_ERROR_NONE && isset($settings['socialmedia_generator'])) {
        // Merge mit Defaults für neue Keys
        return array_replace_recursive($defaults, $settings);
    }
    return $defaults;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    return file_put_contents($filePath, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

// --- LOGIK ---
$settingsFile = Path::getConfigPath('config_generator_settings.json');
$currentSettings = loadGeneratorSettings($settingsFile, $debugMode);
$generatorSettings = $currentSettings['socialmedia_generator'];

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $input = json_decode($_POST['settings'] ?? '{}', true);

    $currentSettings['socialmedia_generator'] = [
        'last_run_timestamp' => time(),
        'format' => $input['format'] ?? 'webp',
        'quality' => (int)($input['quality'] ?? 85),
        'lossless' => filter_var($input['lossless'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'resize_mode' => $input['resize_mode'] ?? 'cover',
        'crop_position' => $input['crop_position'] ?? 'center'
    ];

    if (saveGeneratorSettings($settingsFile, $currentSettings, $debugMode)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der Einstellungen.']);
    }
    exit;
}

// Dateien scannen (Quelle: HIRES!)
$sourceDir = DIRECTORY_PUBLIC_IMG_COMIC_HIRES;
$targetDir = DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA;

// Prüfe ob Zielverzeichnis existiert
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$sourceFiles = glob($sourceDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
$existingImages = glob($targetDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];

$missingImages = [];
$existingMap = [];

foreach ($existingImages as $file) {
    $existingMap[pathinfo($file, PATHINFO_FILENAME)] = true;
}

foreach ($sourceFiles as $file) {
    $filename = pathinfo($file, PATHINFO_FILENAME);
    if (!isset($existingMap[$filename])) {
        $missingImages[] = basename($file);
    }
}

$missingIdsJson = json_encode(array_values($missingImages));

$pageTitle = 'Adminbereich - Social Media Generator';
$pageHeader = 'Social Media Bilder Generator';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="generator-container">
        <!-- HEADER -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($generatorSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">Letzter Lauf am
                        <?php echo date('d.m.Y \u\m H:i:s', $generatorSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Social Media Bilder Generator</h2>
            <p>
                Generiert Bilder im Format 1200x630 Pixel für OpenGraph (Facebook, Twitter, Discord etc.).
                <br>Gefundene fehlende Bilder: <strong><?php echo count($missingImages); ?></strong>
            </p>
        </div>

        <!-- SETTINGS FORM -->
        <div class="generator-settings">
            <div class="form-group">
                <label for="format">Format:</label>
                <select id="format">
                    <option value="webp" <?php echo ($generatorSettings['format'] === 'webp') ? 'selected' : ''; ?>>WebP (Empfohlen)</option>
                    <option value="jpeg" <?php echo ($generatorSettings['format'] === 'jpeg') ? 'selected' : ''; ?>>JPEG</option>
                    <option value="png" <?php echo ($generatorSettings['format'] === 'png') ? 'selected' : ''; ?>>PNG</option>
                </select>
            </div>

            <div class="form-group">
                <label for="resize_mode" title="Wie soll das Bild in 1200x630 eingepasst werden?">Modus:</label>
                <select id="resize_mode">
                    <option value="cover" <?php echo ($generatorSettings['resize_mode'] === 'cover') ? 'selected' : ''; ?>>Ausfüllen (Zuschneiden)</option>
                    <option value="contain" <?php echo ($generatorSettings['resize_mode'] === 'contain') ? 'selected' : ''; ?>>Einpassen (Ränder)</option>
                </select>
            </div>

            <!-- NEU: Crop Position -->
            <div class="form-group">
                <label for="crop_position" title="Welcher Teil des Bildes soll sichtbar sein?">Fokus / Ausschnitt:</label>
                <select id="crop_position">
                    <option value="top" <?php echo ($generatorSettings['crop_position'] === 'top') ? 'selected' : ''; ?>>Oben (0%)</option>
                    <option value="top_center" <?php echo ($generatorSettings['crop_position'] === 'top_center') ? 'selected' : ''; ?>>Oben-Mitte (25%)</option>
                    <option value="center" <?php echo ($generatorSettings['crop_position'] === 'center') ? 'selected' : ''; ?>>Mitte (50%)</option>
                    <option value="bottom_center" <?php echo ($generatorSettings['crop_position'] === 'bottom_center') ? 'selected' : ''; ?>>Mitte-Unten (75%)</option>
                    <option value="bottom" <?php echo ($generatorSettings['crop_position'] === 'bottom') ? 'selected' : ''; ?>>Unten (100%)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quality">Qualität (1-100):</label>
                <input type="number" id="quality" min="1" max="100" value="<?php echo htmlspecialchars((string)$generatorSettings['quality']); ?>">
            </div>

            <div class="form-group checkbox-group">
                <label for="lossless">
                    <input type="checkbox" id="lossless" <?php echo ($generatorSettings['lossless']) ? 'checked' : ''; ?>>
                    Verlustfrei
                </label>
            </div>
        </div>

        <!-- LOG CONSOLE -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit. <?php echo count($missingImages); ?> Bilder in der Warteschlange.</p>
        </div>

        <!-- ACTIONS -->
        <div class="generator-actions">
            <button id="toggle-pause-resume-btn" class="button button-orange" style="display: none;">Pause</button>
            <button id="generate-btn" class="button button-green" <?php echo empty($missingImages) ? 'disabled' : ''; ?>>
                <i class="fas fa-play"></i> Generierung starten
            </button>
        </div>

        <!-- NOTIFICATION BOX -->
        <div id="cache-update-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Generierung abgeschlossen</h4>
            <p>Die Social-Media-Bilder wurden erstellt. Als letzter Schritt sollte der Cache aktualisiert werden.</p>
            <div class="next-steps-actions">
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting' . ($dateiendungPHP ?? '.php'); ?>?autostart=socialmedia"
                   class="button button-blue">
                   <i class="fas fa-sync"></i> Cache aktualisieren
                </a>
            </div>
        </div>

        <!-- IMAGE GRID -->
        <div id="created-images-container" class="image-grid"></div>

    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const initialMissingIds = <?php echo $missingIdsJson; ?>;

        // UI Refs
        const generateButton = document.getElementById('generate-btn');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-btn');
        const logContainer = document.getElementById('log-container');
        const cacheUpdateNotification = document.getElementById('cache-update-notification');
        const settingsContainer = document.querySelector('.generator-settings');
        const createdImagesContainer = document.getElementById('created-images-container');

        // State
        let queue = [...initialMissingIds];
        let totalFiles = initialMissingIds.length;
        let processedFiles = 0;
        let isPaused = false;
        let isGenerationActive = false;
        let createdCount = 0;
        let lastSuccessfulSettings = null;

        function addLogMessage(message, type = 'info') {
            const now = new Date().toLocaleTimeString();
            const p = document.createElement('p');
            p.className = `log-${type}`;
            p.innerHTML = `<span class="log-time">[${now}]</span> ${message}`;
            logContainer.appendChild(p);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        async function saveSettings(settings) {
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('settings', JSON.stringify(settings));
            formData.append('csrf_token', csrfToken);
            try { await fetch(window.location.href, { method: 'POST', body: formData }); } catch (e) { console.error("Settings save failed", e); }
        }

        async function processGenerationQueue() {
            if (isPaused) {
                setTimeout(processGenerationQueue, 500);
                return;
            }

            if (queue.length === 0) {
                finishGeneration();
                return;
            }

            if (!isGenerationActive) {
                isGenerationActive = true;
                updateButtonState();
                settingsContainer.style.opacity = '0.5';
                settingsContainer.style.pointerEvents = 'none';
                createdImagesContainer.innerHTML = '';
                cacheUpdateNotification.style.display = 'none';

                lastSuccessfulSettings = {
                    format: document.getElementById('format').value,
                    quality: parseInt(document.getElementById('quality').value, 10),
                    lossless: document.getElementById('lossless').checked,
                    resize_mode: document.getElementById('resize_mode').value,
                    crop_position: document.getElementById('crop_position').value
                };
            }

            const currentFile = queue.shift();

            try {
                const params = new URLSearchParams({
                    image: currentFile,
                    format: lastSuccessfulSettings.format,
                    quality: lastSuccessfulSettings.quality,
                    lossless: lastSuccessfulSettings.lossless ? '1' : '0',
                    resize_mode: lastSuccessfulSettings.resize_mode,
                    crop_position: lastSuccessfulSettings.crop_position,
                    csrf_token: csrfToken
                });

                const response = await fetch(`check_and_generate_socialmedia.php?${params.toString()}`);

                if (!response.ok) throw new Error(`HTTP Fehler ${response.status}`);

                const responseText = await response.text();
                let data;

                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    const errorMsg = responseText.replace(/<[^>]*>/g, '').substring(0, 150).replace(/\s+/g, ' ').trim();
                    throw new Error(`Server Error (kein JSON): "${errorMsg}"`);
                }

                if (data.status === 'success') {
                    addLogMessage(`Erstellt: ${data.message} (${data.details || ''})`, 'success');
                    createdCount++;

                    if (data.imageUrl) {
                        const imgDiv = document.createElement('div');
                        imgDiv.className = 'image-item';
                        imgDiv.style.aspectRatio = "1.91 / 1"; // Social Media Format
                        imgDiv.innerHTML = `
                            <img src="${data.imageUrl}" alt="${data.message}" loading="lazy">
                            <div class="image-overlay">
                                <span class="filename">${data.message}</span>
                            </div>
                        `;
                        createdImagesContainer.prepend(imgDiv);
                    }

                } else if (data.status === 'exists') {
                    addLogMessage(`Übersprungen: ${data.message}`, 'warning');
                } else {
                    addLogMessage(`Fehler bei ${currentFile}: ${data.message}`, 'error');
                }

            } catch (error) {
                addLogMessage(`Fehler bei ${currentFile}: ${error.message}`, 'error');
            }

            processedFiles++;
            setTimeout(processGenerationQueue, 100);
        }

        async function finishGeneration() {
            isGenerationActive = false;
            updateButtonState();
            settingsContainer.style.opacity = '1';
            settingsContainer.style.pointerEvents = 'auto';
            addLogMessage('Generierung abgeschlossen!', 'info');

            if (createdCount > 0 && lastSuccessfulSettings) {
                await saveSettings(lastSuccessfulSettings);
                cacheUpdateNotification.classList.remove('hidden-by-default');
                cacheUpdateNotification.style.display = 'block';
                cacheUpdateNotification.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function updateButtonState() {
            if (isGenerationActive) {
                generateButton.style.display = 'none';
                togglePauseResumeButton.style.display = 'inline-block';
                if (isPaused) {
                    togglePauseResumeButton.textContent = 'Fortsetzen';
                    togglePauseResumeButton.className = 'button button-green';
                    togglePauseResumeButton.innerHTML = '<i class="fas fa-play"></i> Fortsetzen';
                } else {
                    togglePauseResumeButton.textContent = 'Pause';
                    togglePauseResumeButton.className = 'button button-orange';
                    togglePauseResumeButton.innerHTML = '<i class="fas fa-pause"></i> Pause';
                }
            } else {
                generateButton.style.display = 'inline-block';
                togglePauseResumeButton.style.display = 'none';

                if (queue.length === 0 && processedFiles === totalFiles) {
                    generateButton.disabled = true;
                    generateButton.innerHTML = '<i class="fas fa-check"></i> Fertig';
                }
            }
        }

        generateButton.addEventListener('click', processGenerationQueue);
        togglePauseResumeButton.addEventListener('click', () => {
            isPaused = !isPaused;
            if (isPaused) addLogMessage('Pausiert...', 'warning');
            else addLogMessage('Fortgesetzt...', 'info');
            updateButtonState();
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
