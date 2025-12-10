<?php

/**
 * Dies ist die Administrationsseite für den Thumbnail-Generator.
 *
 * @file      ROOT/public/admin/generator_thumbnail.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
* @since 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Vollständige Umstellung auf die neueste Konstanten-Struktur.
 *
 *    BUGFIXES
 *    - Behebung eines JavaScript ReferenceErrors (Scope-Problem).
 *
 * @since     5.0.0
 * - refactor(UI): Inline-Styles durch SCSS-Komponenten (.generator-container, .log-console) ersetzt.
 * - refactor(Code): HTML-Struktur an Admin-Layout angepasst.
 * - fix(JS): Modernisierung auf fetch/async-await und verbessertes State-Management.
 * - Verbesserte Fehlerdiagnose bei nicht-JSON Antworten (HTML/404/500).
 * - Layout-Optimierung: Log oben, Bilder unten. Auto-Scroll Option hinzugefügt.
 * - Entfernung der Auto-Scroll Funktion, da neue Bilder oben angefügt werden.
 * - Workflow-Optimierung: "Generierung abgeschlossen"-Box zwischen Log und Bildern platziert, mit Links zu Social-Media-Generator und Cache-Update.
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
        'thumbnail_generator' => [
            'last_run_timestamp' => null,
            'format' => 'webp',
            'quality' => 80,
            'lossless' => false
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
    if (json_last_error() === JSON_ERROR_NONE && isset($settings['thumbnail_generator'])) {
        return $settings;
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
$generatorSettings = $currentSettings['thumbnail_generator'];

// AJAX Handler für Settings-Speicherung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $input = json_decode($_POST['settings'] ?? '{}', true);

    $currentSettings['thumbnail_generator'] = [
        'last_run_timestamp' => time(),
        'format' => $input['format'] ?? 'webp',
        'quality' => (int)($input['quality'] ?? 80),
        'lossless' => filter_var($input['lossless'] ?? false, FILTER_VALIDATE_BOOLEAN)
    ];

    if (saveGeneratorSettings($settingsFile, $currentSettings, $debugMode)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der Einstellungen.']);
    }
    exit;
}

// Dateien scannen
$sourceDir = DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;
$targetDir = DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS;

$sourceFiles = glob($sourceDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
$existingThumbnails = glob($targetDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];

$missingThumbnails = [];
$existingMap = [];

foreach ($existingThumbnails as $file) {
    $existingMap[pathinfo($file, PATHINFO_FILENAME)] = true;
}

foreach ($sourceFiles as $file) {
    $filename = pathinfo($file, PATHINFO_FILENAME);
    if (!isset($existingMap[$filename])) {
        $missingThumbnails[] = basename($file);
    }
}

$missingIdsJson = json_encode(array_values($missingThumbnails));

$pageTitle = 'Adminbereich - Thumbnail Generator';
$pageHeader = 'Thumbnail Generator';
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
            <h2>Thumbnail Generator</h2>
            <p>
                Dieses Tool generiert fehlende Vorschaubilder (Thumbnails) für die Comic-Übersichten.
                <br>Gefundene fehlende Bilder: <strong><?php echo count($missingThumbnails); ?></strong>
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
                <label for="quality">Qualität (1-100):</label>
                <input type="number" id="quality" min="1" max="100" value="<?php echo htmlspecialchars((string)$generatorSettings['quality']); ?>">
            </div>
            <div class="form-group checkbox-group">
                <label for="lossless" title="Nur für WebP und PNG relevant">
                    <input type="checkbox" id="lossless" <?php echo ($generatorSettings['lossless']) ? 'checked' : ''; ?>>
                    Verlustfrei
                </label>
            </div>
        </div>

        <!-- PROGRESS BAR -->
        <div class="progress-wrapper">
            <div id="progress-bar" class="progress-bar"></div>
            <div id="progress-text" class="progress-text">0% (0/0)</div>
        </div>

        <!-- ACTIONS -->
        <div class="generator-actions">
            <button id="toggle-pause-resume-btn" class="button button-orange" style="display: none;">Pause</button>
            <button id="generate-btn" class="button button-green" <?php echo empty($missingThumbnails) ? 'disabled' : ''; ?>>
                <i class="fas fa-play"></i> Generierung starten
            </button>
        </div>

        <!-- LOG CONSOLE (Log oben) -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit. <?php echo count($missingThumbnails); ?> Bilder in der Warteschlange.</p>
        </div>

        <!-- NOTIFICATIONS (Zwischen Console und Bildern) -->
        <div id="cache-update-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Generierung abgeschlossen</h4>
            <p>Die Thumbnails wurden erfolgreich erstellt. Bitte wähle den nächsten Schritt:</p>
            <div class="next-steps-actions">
                <!-- Option 1: Social Media Bilder -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_image_socialmedia' . ($dateiendungPHP ?? '.php'); ?>"
                   class="button button-orange">
                   <i class="fas fa-share-alt"></i> 1. Social-Media-Bilder generieren
                </a>

                <!-- Option 2: Cache -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting' . ($dateiendungPHP ?? '.php'); ?>?autostart=thumbnails"
                   class="button button-blue">
                   <i class="fas fa-sync"></i> 2. Cache aktualisieren
                </a>
            </div>
        </div>

        <!-- IMAGE GRID CONTAINER (Bilder unten) -->
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
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
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
            logContainer.scrollTop = logContainer.scrollHeight; // Log scrollt weiterhin, aber Seite nicht
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
                cacheUpdateNotification.style.display = 'none'; // Verstecken beim Start

                lastSuccessfulSettings = {
                    format: document.getElementById('format').value,
                    quality: parseInt(document.getElementById('quality').value, 10),
                    lossless: document.getElementById('lossless').checked
                };
            }

            const currentFile = queue.shift();

            try {
                const params = new URLSearchParams({
                    image: currentFile,
                    format: lastSuccessfulSettings.format,
                    quality: lastSuccessfulSettings.quality,
                    lossless: lastSuccessfulSettings.lossless ? '1' : '0',
                    csrf_token: csrfToken
                });

                const response = await fetch(`check_and_generate_thumbnail.php?${params.toString()}`);

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
                        // Dateiname ist jetzt im Overlay versteckt und nur bei Hover sichtbar
                        imgDiv.innerHTML = `
                            <img src="${data.imageUrl}" alt="${data.message}" loading="lazy">
                            <div class="image-overlay">
                                <span class="filename">${data.message}</span>
                            </div>
                        `;
                        // Neue Bilder werden OBEN eingefügt
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
            const percent = Math.round((processedFiles / totalFiles) * 100);
            progressBar.style.width = `${percent}%`;
            progressText.textContent = `${percent}% (${processedFiles}/${totalFiles})`;

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
                // Optional: Zu den Buttons scrollen
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
