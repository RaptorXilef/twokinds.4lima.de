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
 * @since 5.0.0
 * - refactor(UI): Inline-Styles durch SCSS-Komponenten (.generator-container, .log-console) ersetzt.
 * - refactor(Code): HTML-Struktur an Admin-Layout angepasst.
 * - fix(JS): Modernisierung auf fetch/async-await und verbessertes State-Management.
 * - Verbesserte Fehlerdiagnose bei nicht-JSON Antworten (HTML/404/500).
 * - Layout-Optimierung: Log oben, Bilder unten. Auto-Scroll Option hinzugefügt.
 * - Entfernung der Auto-Scroll Funktion, da neue Bilder oben angefügt werden.
 * - Workflow-Optimierung: "Generierung abgeschlossen"-Box zwischen Log und Bildern platziert, mit Links zu
 *    Social-Media-Generator und Cache-Update.
 * - Angleichung an Social-Media-Generator (User-Config, manueller Save-Button, Delete-Funktion, White-BG Option).
 * - Feature: Einstellungen (Format, Qualität, Lossless) sind nun konfigurierbar und werden gespeichert.
 * - Feature: "Zwingend weißer Hintergrund" Option hinzugefügt.
 * - Feature: Bilder können nun direkt aus der Übersicht gelöscht werden.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> generator_thumbnail).
 * - fix(UI): Fallback-Anzeige für fehlenden Zeitstempel.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
// Neuer Pfad im Unterordner 'admin'
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, string $username): array
{
    $defaults = [
        'format' => 'webp',
        'quality' => 80,
        'lossless' => false,
        'force_white_bg' => false,
        'last_run_timestamp' => null
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
    $userSettings = $data['users'][$username]['generator_thumbnail'] ?? [];

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

    $currentData = $data['users'][$username]['generator_thumbnail'] ?? [];
    $data['users'][$username]['generator_thumbnail'] = array_replace_recursive($currentData, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// --- LOGIK ---
$thumbSettings = loadGeneratorSettings($configPath, $currentUser);

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $action = $_POST['action'];

    if ($action === 'save_settings') {
        $input = json_decode($_POST['settings'] ?? '{}', true);

        $settingsToSave = [
            'format' => $input['format'] ?? 'webp',
            'quality' => (int)($input['quality'] ?? 80),
            'lossless' => filter_var($input['lossless'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'force_white_bg' => filter_var($input['force_white_bg'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ];

        if (isset($input['update_timestamp']) && $input['update_timestamp'] === true) {
            $settingsToSave['last_run_timestamp'] = time();
        } else {
            $currentData = loadGeneratorSettings($configPath, $currentUser);
            $settingsToSave['last_run_timestamp'] = $currentData['last_run_timestamp'];
        }

        if (saveGeneratorSettings($configPath, $currentUser, $settingsToSave)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der Einstellungen.']);
        }
    } elseif ($action === 'delete_image') {
        $filename = basename($_POST['filename'] ?? '');
        $targetDir = DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS;
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (empty($filename) || !file_exists($targetFile)) {
            echo json_encode(['success' => false, 'message' => 'Datei nicht gefunden.']);
        } else {
            if (unlink($targetFile)) {
                echo json_encode(['success' => true, 'message' => 'Datei gelöscht.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Konnte Datei nicht löschen.']);
            }
        }
    }
    exit;
}

// Dateien scannen
$sourceDir = DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;
$targetDir = DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

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
                <?php if ($thumbSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">Letzter Lauf am
                        <?php echo date('d.m.Y \u\m H:i:s', $thumbSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Generierung durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>Thumbnail Generator</h2>
            <p>
                Generiert Vorschaubilder (96px Breite) für die Comic-Navigation und Lesezeichen.
                <br>Gefundene fehlende Bilder: <strong><?php echo count($missingThumbnails); ?></strong>
            </p>
        </div>

        <!-- SETTINGS FORM -->
        <div class="generator-settings">
            <div class="form-group">
                <label for="format">Format:</label>
                <select id="format">
                    <option value="webp" <?php echo ($thumbSettings['format'] === 'webp') ? 'selected' : ''; ?>>WebP (Empfohlen)</option>
                    <option value="jpeg" <?php echo ($thumbSettings['format'] === 'jpeg') ? 'selected' : ''; ?>>JPEG</option>
                    <option value="png" <?php echo ($thumbSettings['format'] === 'png') ? 'selected' : ''; ?>>PNG</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quality">Qualität (1-100):</label>
                <input type="number" id="quality" min="1" max="100" value="<?php echo htmlspecialchars((string)$thumbSettings['quality']); ?>">
            </div>

            <div class="form-group checkbox-group">
                <label for="force_white_bg" title="Falls aktiviert, wird der Hintergrund immer weiß statt transparent (Standard bei WebP/PNG)">
                    <input type="checkbox" id="force_white_bg" <?php echo ($thumbSettings['force_white_bg']) ? 'checked' : ''; ?>>
                    Hintergrund: Weiß
                </label>
            </div>

            <div class="form-group checkbox-group">
                <label for="lossless">
                    <input type="checkbox" id="lossless" <?php echo ($thumbSettings['lossless']) ? 'checked' : ''; ?>>
                    Verlustfrei
                </label>
            </div>

            <!-- Manueller Speicher-Button -->
            <div class="form-group" style="flex: 0 0 100%; display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="button" id="save-settings-btn" class="button button-blue" style="display: none;">
                    <i class="fas fa-save"></i> Einstellungen speichern
                </button>
            </div>
        </div>

        <!-- LOG CONSOLE (Log oben) -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit. <?php echo count($missingThumbnails); ?> Bilder in der Warteschlange.</p>
        </div>

        <!-- ACTIONS -->
        <div class="generator-actions">
            <button id="toggle-pause-resume-btn" class="button button-orange" style="display: none;">Pause</button>
            <button id="generate-btn" class="button button-green" <?php echo empty($missingThumbnails) ? 'disabled' : ''; ?>>
                <i class="fas fa-play"></i> Generierung starten
            </button>
        </div>

        <!-- NOTIFICATION BOX -->
        <div id="cache-update-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Generierung abgeschlossen</h4>
            <p>Die Thumbnails wurden erstellt. Als letzter Schritt sollte der Cache aktualisiert werden.</p>
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
        const saveSettingsBtn = document.getElementById('save-settings-btn');

        const inputs = settingsContainer.querySelectorAll('input, select');

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

        function getCurrentSettings() {
            return {
                format: document.getElementById('format').value,
                quality: parseInt(document.getElementById('quality').value, 10),
                lossless: document.getElementById('lossless').checked,
                force_white_bg: document.getElementById('force_white_bg').checked
            };
        }

        async function saveSettings(settings, updateTimestamp = false) {
            const formData = new FormData();
            formData.append('action', 'save_settings');
            settings.update_timestamp = updateTimestamp;
            formData.append('settings', JSON.stringify(settings));
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    return true;
                } else {
                    console.error("Save failed:", result.message);
                    return false;
                }
            } catch (e) {
                console.error("Settings save failed", e);
                return false;
            }
        }

        inputs.forEach(input => {
            input.addEventListener('change', () => {
                if (!isGenerationActive) {
                    saveSettingsBtn.style.display = 'inline-block';
                }
            });
            if (input.type === 'number' || input.type === 'range' || input.type === 'text') {
                input.addEventListener('input', () => {
                    if (!isGenerationActive) {
                        saveSettingsBtn.style.display = 'inline-block';
                    }
                });
            }
        });

        saveSettingsBtn.addEventListener('click', async () => {
            const currentSettings = getCurrentSettings();
            if (await saveSettings(currentSettings, false)) {
                saveSettingsBtn.style.display = 'none';
                addLogMessage('Einstellungen erfolgreich gespeichert.', 'success');
            } else {
                addLogMessage('Fehler beim Speichern der Einstellungen.', 'error');
            }
        });

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
                saveSettingsBtn.style.display = 'none';
                createdImagesContainer.innerHTML = '';
                cacheUpdateNotification.style.display = 'none';

                lastSuccessfulSettings = getCurrentSettings();
            }

            const currentFile = queue.shift();

            try {
                const params = new URLSearchParams({
                    image: currentFile,
                    format: lastSuccessfulSettings.format,
                    quality: lastSuccessfulSettings.quality,
                    lossless: lastSuccessfulSettings.lossless ? '1' : '0',
                    force_white_bg: lastSuccessfulSettings.force_white_bg ? '1' : '0',
                    csrf_token: csrfToken
                });

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);

                const response = await fetch(`check_and_generate_thumbnail.php?${params.toString()}`, {
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

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
                            <button class="delete-btn" title="Bild löschen" onclick="deleteGeneratedImage('${data.message}', this)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
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
                if (error.name === 'AbortError') {
                    addLogMessage(`Timeout bei ${currentFile}: Server antwortet nicht. Überspringe...`, 'error');
                } else {
                    addLogMessage(`Fehler bei ${currentFile}: ${error.message}`, 'error');
                }
            } finally {
                processedFiles++;
                setTimeout(processGenerationQueue, 100);
            }
        }

        async function finishGeneration() {
            isGenerationActive = false;
            updateButtonState();
            settingsContainer.style.opacity = '1';
            settingsContainer.style.pointerEvents = 'auto';
            addLogMessage('Generierung abgeschlossen!', 'info');

            if (createdCount > 0 && lastSuccessfulSettings) {
                await saveSettings(lastSuccessfulSettings, true);
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

        window.deleteGeneratedImage = async function(filename, btnElement) {
            if (!confirm(`Soll das Bild "${filename}" wirklich unwiderruflich gelöscht werden?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_image');
            formData.append('filename', filename);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    const item = btnElement.closest('.image-item');
                    item.style.transition = 'all 0.3s';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => item.remove(), 300);
                    addLogMessage(`Bild gelöscht: ${filename}`, 'warning');
                } else {
                    alert('Fehler beim Löschen: ' + result.message);
                }
            } catch (e) {
                alert('Netzwerkfehler beim Löschen.');
                console.error(e);
            }
        };

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
