<?php

/**
 * Administrationsseite zum Erstellen des Bild-Caches inkl. Cache-Busting.
 * Scannt die Bildverzeichnisse und erstellt eine JSON-Datenbank für schnellen Zugriff.
 *
 * @file      ROOT/public/admin/build_image_cache_and_busting.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 3.0.0 -- 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Vollständige Umstellung auf die dynamische Path-Helfer-Klasse und granulare Pfad-Konstanten.
 *    - Speicherung der letzten Ausführung in der zentralen Einstellungs-JSON.
 *
 *    UI, SICHERHEIT & UX
 *    - Überarbeitung mit modernem UI und Herstellung der CSP-Konformität.
 *    - Deaktivierung des Auto-Reloads nach Prozessende für bessere Kontrolle.
 * @since 5.0.0
 * - Komplettes Refactoring auf Admin-Standard (SCSS, Fetch-API, Path-Klasse).
 * - Workflow-Optimierung: Link zum RSS-Generator als nächsten Schritt hinzugefügt.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> build_image_cache).
 * - fix(UI): Fallback-Anzeige für fehlenden Zeitstempel.
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
    // Flache Defaults
    $defaults = [
        'last_run_timestamp' => null,
        'total_files' => 0
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
    $userSettings = $data['users'][$username]['build_image_cache'] ?? [];

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

    $currentData = $data['users'][$username]['build_image_cache'] ?? [];
    $data['users'][$username]['build_image_cache'] = array_replace_recursive($currentData, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// --- LOGIK ---

/**
 * Scannt ein Verzeichnis und gibt ein Array der gefundenen Bilddateien zurück.
 */
function scanDirectory(string $dir): array
{
    $files = [];
    if (!is_dir($dir)) {
        return [];
    }

    $iterator = new DirectoryIterator($dir);
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isFile()) {
            $ext = strtolower($fileinfo->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $files[$fileinfo->getBasename('.' . $ext)] = $fileinfo->getFilename();
            }
        }
    }
    return $files;
}

/**
 * Führt den Cache-Build-Prozess durch.
 */
function buildImageCache(string $mode): array
{
    $cacheData = [];
    $log = [];

    // Bestehenden Cache laden, um nicht betroffene Teile zu erhalten (optional)
    // Hier entscheiden wir uns für einen sauberen Neubau der angeforderten Teile
    // und Mergen mit dem Rest, falls wir "Teil-Updates" unterstützen wollen.
    // Für maximale Konsistenz laden wir alles neu, wenn "all" gewählt ist.

    $cacheFile = Path::getCachePath('comic_image_cache.json');
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true) ?? [];
    }

    // Zeitstempel für Cache-Busting
    $cacheData['last_updated'] = time();

    // 1. THUMBNAILS
    if ($mode === 'thumbnails' || $mode === 'all') {
        $thumbs = scanDirectory(DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS);
        $cacheData['thumbnails'] = $thumbs;
        $log[] = "Thumbnails gescannt: " . count($thumbs) . " Dateien.";
    }

    // 2. LOWRES (Standard Comic Seiten)
    if ($mode === 'lowres' || $mode === 'all' || $mode === 'lowres,hires') {
        $lowres = scanDirectory(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES);
        $cacheData['lowres'] = $lowres;
        $log[] = "LowRes (Comic) gescannt: " . count($lowres) . " Dateien.";
    }

    // 3. HIRES
    if ($mode === 'hires' || $mode === 'all' || $mode === 'lowres,hires') {
        $hires = scanDirectory(DIRECTORY_PUBLIC_IMG_COMIC_HIRES);
        $cacheData['hires'] = $hires;
        $log[] = "HiRes gescannt: " . count($hires) . " Dateien.";
    }

    // 4. SOCIAL MEDIA
    if ($mode === 'socialmedia' || $mode === 'all') {
        $social = scanDirectory(DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA);
        $cacheData['socialmedia'] = $social;
        $log[] = "Social Media Bilder gescannt: " . count($social) . " Dateien.";
    }

    // Gesamtzahl berechnen
    $totalCount = 0;
    foreach ($cacheData as $key => $val) {
        if (is_array($val)) {
            $totalCount += count($val);
        }
    }

    // Speichern
    if (file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT))) {
        return [
            'success' => true,
            'log' => $log,
            'timestamp' => $cacheData['last_updated'],
            'total_files' => $totalCount
        ];
    } else {
        return ['success' => false, 'log' => array_merge($log, ["Fehler beim Schreiben der Cache-Datei!"])];
    }
}

// === LOGIK VIEW ===
$cacheSettings = loadGeneratorSettings($configPath, $currentUser);

// === AJAX HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'build_cache') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $mode = $_POST['mode'] ?? 'all';
    $result = buildImageCache($mode);

    if ($result['success']) {
        $newSettings = [
            'last_run_timestamp' => $result['timestamp'],
            'total_files' => $result['total_files'] ?? 0
        ];
        saveGeneratorSettings($configPath, $currentUser, $newSettings);
        $result['last_run_formatted'] = date('d.m.Y \u\m H:i:s', $result['timestamp']);
    }

    echo json_encode($result);
    exit;
}

$pageTitle = 'Adminbereich - Cache Builder';
$pageHeader = 'Bild-Cache aktualisieren';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="generator-container">
        <!-- HEADER & INFO -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($cacheSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">
                        Cache zuletzt aktualisiert am <?php echo date('d.m.Y \u\m H:i:s', $cacheSettings['last_run_timestamp']); ?> Uhr
                        (<?php echo $cacheSettings['total_files']; ?> Dateien im Index).
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Generierung durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>Bild-Cache Management</h2>
            <p>
                Dieses Tool scannt die Bildverzeichnisse und erstellt eine Index-Datei (JSON).
                Dies beschleunigt das Laden der Seite erheblich und ermöglicht das "Cache-Busting" (Browser zwingen, neue Bilder zu laden).
                <br><strong>Wann ausführen?</strong> Nach jedem Upload neuer Comic-Seiten oder Thumbnails.
            </p>
        </div>

        <!-- ACTIONS CENTERED -->
        <div class="generator-actions actions-center">
            <button class="button button-blue" data-mode="all">
                <i class="fas fa-sync"></i> Alles aktualisieren
            </button>
            <button class="button" data-mode="thumbnails">
                <i class="fas fa-images"></i> Nur Thumbnails
            </button>
            <button class="button" data-mode="lowres,hires">
                <i class="fas fa-file-image"></i> Comic-Seiten (Low/Hi)
            </button>
            <button class="button" data-mode="socialmedia">
                <i class="fas fa-share-alt"></i> Social Media
            </button>
        </div>

        <!-- LOG CONSOLE -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit.</p>
        </div>

        <!-- SUCCESS NOTIFICATION -->
        <div id="success-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Cache aktualisiert</h4>
            <p>Die Datenbank ist auf dem neuesten Stand.</p>
            <div class="next-steps-actions">
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_rss' . ($dateiendungPHP ?? '.php'); ?>" class="button button-orange">
                    <i class="fas fa-rss"></i> RSS-Feed aktualisieren
                </a>
                <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>" target="_blank" class="button button-blue">
                    <i class="fas fa-external-link-alt"></i> Zur Webseite
                </a>
            </div>
        </div>

    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const buttons = document.querySelectorAll('.generator-actions button');
        const logContainer = document.getElementById('log-container');
        const successNotification = document.getElementById('success-notification');
        const lastRunContainer = document.getElementById('last-run-container');

        function addLogMessage(message, type = 'info') {
            const now = new Date().toLocaleTimeString();
            const p = document.createElement('p');
            p.className = `log-${type}`;
            p.innerHTML = `<span class="log-time">[${now}]</span> ${message}`;
            logContainer.appendChild(p);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        async function runCacheBuild(mode) {
            // UI sperren
            buttons.forEach(b => b.disabled = true);
            successNotification.style.display = 'none';
            logContainer.innerHTML = '';
            addLogMessage(`Starte Cache-Build (Modus: ${mode})...`, 'info');

            const formData = new FormData();
            formData.append('action', 'build_cache');
            formData.append('mode', mode);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                let data;

                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error("Raw Response:", responseText);
                    throw new Error("Ungültige Server-Antwort.");
                }

                if (data.success) {
                    data.log.forEach(line => addLogMessage(line, 'success'));
                    addLogMessage('Fertig!', 'success');

                    // Timestamp Update
                    if (data.last_run_formatted) {
                        lastRunContainer.innerHTML = `<p class="status-message status-info">Cache zuletzt aktualisiert am ${data.last_run_formatted} Uhr (${data.total_files} Dateien im Index).</p>`;
                    }

                    successNotification.style.display = 'block';
                    successNotification.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                } else {
                    data.log.forEach(line => addLogMessage(line, 'error'));
                    addLogMessage('Fehler beim Aktualisieren des Caches.', 'error');
                }

            } catch (error) {
                console.error(error);
                addLogMessage(`Kritischer Fehler: ${error.message}`, 'error');
            } finally {
                // UI freigeben
                buttons.forEach(b => b.disabled = false);
            }
        }

        // Button Event Listeners
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                runCacheBuild(btn.dataset.mode);
            });
        });

        // Autostart Logic (z.B. ?autostart=thumbnails)
        const urlParams = new URLSearchParams(window.location.search);
        const autostartMode = urlParams.get('autostart');

        if (autostartMode) {
            // Validierung, um unerwünschte Aktionen zu verhindern
            const validModes = ['thumbnails', 'lowres', 'hires', 'socialmedia', 'all', 'lowres,hires'];
            if (validModes.includes(autostartMode)) {
                // Kurze Verzögerung für bessere UX (Seite erst laden lassen)
                setTimeout(() => runCacheBuild(autostartMode), 500);
            } else {
                addLogMessage(`Ungültiger Autostart-Modus: ${autostartMode}`, 'warning');
            }
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
