<?php

/**
 * Adminseite zum Generieren der Comic-PHP-Dateien.
 * Erstellt fehlende .php Dateien im Public-Ordner basierend auf der Datenbank.
 *
 * @file      ROOT/public/admin/generator_comic.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Vollständige Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Implementierung dynamischer Pfadberechnungen und Anpassung an Schema v2 (comic_var.json).
 *
 *    LOGIK & FEATURES
 *    - Verbesserte Bilderkennung und detailliertes Protokollierungssystem.
 *    - Speicherung der letzten Ausführung für bessere Prozesskontrolle.
 *
 *    UI & DESIGN
 *    - Vollständige Überarbeitung mit modernem User Interface.
 *
 * @since 5.0.0
 * - Komplettes Refactoring auf Admin-Standard (Worker-Pattern, SCSS Layout).
 * - refactor(Config): Harmonisierung der Settings-Struktur (Flaches Array).
 * - refactor(Config): Nutzung von 'admin/config_generator_settings.json'.
 * - fix(Logic): Korrekter Zugriff auf Benutzereinstellungen und Fallback-Anzeige.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, string $username): array
{
    // FIX: Flache Struktur für Defaults
    $defaults = [
        'last_run_timestamp' => null,
        'pages_created' => 0
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
    $userSettings = $data['users'][$username]['generator_comic'] ?? [];

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

    $currentData = $data['users'][$username]['generator_comic'] ?? [];
    $data['users'][$username]['generator_comic'] = array_replace_recursive($currentData, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// --- LOGIK: Fehlende Seiten finden ---
$comicVarPath = Path::getDataPath('comic_var.json');
$missingPages = [];

if (file_exists($comicVarPath)) {
    $comicData = json_decode(file_get_contents($comicVarPath), true);
    // Support v1/v2
    $comics = (isset($comicData['schema_version']) && $comicData['schema_version'] >= 2) ? ($comicData['comics'] ?? []) : $comicData;

    foreach ($comics as $id => $data) {
        $targetFile = DIRECTORY_PUBLIC_COMIC . DIRECTORY_SEPARATOR . $id . '.php';
        if (!file_exists($targetFile)) {
            $missingPages[] = (string)$id;
        }
    }
}

$missingIdsJson = json_encode(array_values($missingPages));

// Settings laden (flaches Array)
$comicSettings = loadGeneratorSettings($configPath, $currentUser);

// AJAX Handler (nur Settings speichern, Generierung läuft über Worker)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $count = (int)($_POST['count'] ?? 0);
    $newSettings = [
        'last_run_timestamp' => time(),
        'pages_created' => $count
    ];

    if (saveGeneratorSettings($configPath, $currentUser, $newSettings)) {
        echo json_encode(['success' => true, 'timestamp' => $newSettings['last_run_timestamp']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern.']);
    }
    exit;
}

$pageTitle = 'Adminbereich - Comic-Seiten Generator';
$pageHeader = 'Comic-Seiten Generator';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="generator-container">
        <!-- HEADER -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if (!empty($comicSettings['last_run_timestamp'])) : ?>
                    <p class="status-message status-info">Letzter Lauf am
                        <?php echo date('d.m.Y \u\m H:i:s', $comicSettings['last_run_timestamp']); ?> Uhr
                        (<?php echo $comicSettings['pages_created']; ?> Seiten erstellt).
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Generierung durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>Comic-Seiten Generator</h2>
            <p>
                Erstellt die physischen PHP-Dateien (z.B. <code>20231224.php</code>) für alle Comics in der Datenbank.
                Diese Dateien laden den Renderer und zeigen den Comic an.
                <br>Aktuell fehlen: <strong><?php echo count($missingPages); ?></strong> Seiten.
            </p>
        </div>

        <!-- ACTIONS -->
        <div class="generator-actions">
            <button id="toggle-pause-resume-btn" class="button button-orange" style="display: none;">Pause</button>
            <button id="generate-btn" class="button button-green" <?php echo empty($missingPages) ? 'disabled' : ''; ?>>
                <i class="fas fa-file-code"></i> Fehlende Seiten generieren
            </button>
        </div>

        <!-- LOG CONSOLE -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit. <?php echo count($missingPages); ?> Seiten in der Warteschlange.</p>
        </div>

        <!-- SUCCESS NOTIFICATION -->
        <div id="success-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Generierung abgeschlossen</h4>
            <p>Die Comic-Seiten wurden erstellt. Die Links in der Sitemap und im RSS-Feed funktionieren nun.</p>
            <div class="next-steps-actions">
                <!-- Option 1: RSS -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_rss' . ($dateiendungPHP ?? '.php'); ?>" class="button button-orange">
                    <i class="fas fa-rss"></i> RSS-Feed aktualisieren
                </a>

                <!-- Option 2: Sitemap -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_sitemap' . ($dateiendungPHP ?? '.php'); ?>" class="button button-blue">
                    <i class="fas fa-sitemap"></i> Sitemap erstellen
                </a>
            </div>
        </div>

    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const initialMissingIds = <?php echo $missingIdsJson; ?>;

        const generateButton = document.getElementById('generate-btn');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-btn');
        const logContainer = document.getElementById('log-container');
        const successNotification = document.getElementById('success-notification');
        const lastRunContainer = document.getElementById('last-run-container');

        // State
        let queue = [...initialMissingIds];
        let totalFiles = initialMissingIds.length;
        let processedFiles = 0;
        let isPaused = false;
        let isGenerationActive = false;
        let createdCount = 0;

        function addLogMessage(message, type = 'info') {
            const now = new Date().toLocaleTimeString();
            const p = document.createElement('p');
            p.className = `log-${type}`;
            p.innerHTML = `<span class="log-time">[${now}]</span> ${message}`;
            logContainer.appendChild(p);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        async function saveSettings(count) {
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('count', count);
            formData.append('csrf_token', csrfToken);
            try { await fetch(window.location.href, { method: 'POST', body: formData }); } catch (e) { console.error("Save failed", e); }
        }

        async function processQueue() {
            if (isPaused) {
                setTimeout(processQueue, 500);
                return;
            }

            if (queue.length === 0) {
                finishGeneration();
                return;
            }

            if (!isGenerationActive) {
                isGenerationActive = true;
                updateButtonState();
            }

            const currentId = queue.shift();

            try {
                // Timeout Controller (30s)
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);

                const response = await fetch(`check_and_generate_comic.php?id=${currentId}&csrf_token=${csrfToken}`, {
                    signal: controller.signal
                });

                clearTimeout(timeoutId);

                // Erst Text holen
                const responseText = await response.text();
                let data;

                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    const errorMsg = responseText.replace(/<[^>]*>/g, '').substring(0, 150).trim();
                    throw new Error(`Ungültige Antwort: "${errorMsg}"`);
                }

                if (data.status === 'success') {
                    addLogMessage(data.message, 'success');
                    createdCount++;
                } else if (data.status === 'exists') {
                    addLogMessage(data.message, 'warning');
                } else {
                    addLogMessage(`Fehler bei ${currentId}: ${data.message}`, 'error');
                }

            } catch (error) {
                if (error.name === 'AbortError') {
                    addLogMessage(`Timeout bei ${currentId}.`, 'error');
                } else {
                    addLogMessage(`Fehler bei ${currentId}: ${error.message}`, 'error');
                }
            } finally {
                processedFiles++;
                setTimeout(processQueue, 50); // Schnell weitermachen, da File-Creation schnell geht
            }
        }

        async function finishGeneration() {
            isGenerationActive = false;
            updateButtonState();
            addLogMessage('Vorgang abgeschlossen!', 'info');

            if (createdCount > 0) {
                await saveSettings(createdCount);

                // Timestamp Update UI
                const now = new Date();
                lastRunContainer.innerHTML = `<p class="status-message status-info">Letzter Lauf am ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr (${createdCount} Seiten erstellt).</p>`;

                successNotification.style.display = 'block';
                successNotification.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        generateButton.addEventListener('click', processQueue);
        togglePauseResumeButton.addEventListener('click', () => {
            isPaused = !isPaused;
            if (isPaused) addLogMessage('Pausiert...', 'warning');
            else addLogMessage('Fortgesetzt...', 'info');
            updateButtonState();
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
