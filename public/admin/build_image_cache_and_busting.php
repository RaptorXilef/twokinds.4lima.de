<?php

/**
 * Administrationsseite zum Erstellen des Bild-Caches inkl. Cache-Busting.
 * Scannt die Bildverzeichnisse und erstellt eine ID-zentrierte JSON-Datenbank.
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
 *
 * @since 5.0.0
 * - Komplettes Refactoring auf Admin-Standard (SCSS, Fetch-API, Path-Klasse).
 * - Workflow-Optimierung: Link zum RSS-Generator als nächsten Schritt hinzugefügt.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> build_image_cache).
 * - fix(UI): Fallback-Anzeige für fehlenden Zeitstempel.
 * - fix(Cache): Datenstruktur korrigiert (ID-zentriert statt Kategorie-zentriert).
 * - fix(Cache): Speichert nun wieder relative Pfade inkl. Dateimodifikations-Zeitstempel (?c=).
 * - feat(Cache): Priorisierung von Dateiendungen (WebP vor PNG/JPG) implementiert.
 * - refactor(Logic): Bestehende manuelle Einträge (z.B. Original-URLs) werden beim Scan erhalten.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// Definition der Scan-Ziele und deren relativen Pfad-Prefixe
// Wir berechnen den Pfad relativ zum Root der Webseite
$dirsToScan = [
    'thumbnails'  => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS,  'rel' => 'assets/images/comic/thumbnails/'],
    'lowres'      => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_LOWRES,      'rel' => 'assets/images/comic/lowres/'],
    'hires'       => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_HIRES,       'rel' => 'assets/images/comic/hires/'],
    'socialmedia' => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA, 'rel' => 'assets/images/comic/socialmedia/'],
];

// --- HILFSFUNKTIONEN ---

/**
 * Lädt die Einstellungen für den aktuellen Benutzer aus der zentralen Config.
 */
function loadGeneratorSettings(string $filePath, string $username): array
{
    // Flache Defaults
    $defaults = [
        'last_run_timestamp' => null,
        'total_files' => 0,
    ];

    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode(['users' => []], JSON_PRETTY_PRINT));
        return $defaults;
    }

    $data = json_decode(file_get_contents($filePath), true);
    return $data['users'][$username]['build_image_cache'] ?? $defaults;
}

/**
 * Speichert die Laufzeit-Metadaten zentral.
 */
function saveGeneratorSettings(string $filePath, string $username, array $newSettings): bool
{
    $data = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
    if (!isset($data['users'][$username])) {
        $data['users'][$username] = [];
    }

    $current = $data['users'][$username]['build_image_cache'] ?? [];
    $data['users'][$username]['build_image_cache'] = array_merge($current, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

/**
 * Scannt ein Verzeichnis nach Bildern und generiert Pfade mit Cache-Busting.
 * Erkennt die Comic-ID anhand des Dateinamens (8 Ziffern).
 */
function scanDirForCache(string $dir, string $relPrefix): array
{
    $results = [];
    if (!is_dir($dir)) {
        return $results;
    }

    $extensions = ['webp', 'png', 'jpg', 'jpeg', 'gif'];
    $files = scandir($dir);

    // Temporäre Sammlung um Prioritäten (WebP > JPG) zu handhaben
    $tempMap = [];

    foreach ($files as $file) {
        $pathInfo = pathinfo($file);
        $fileName = $pathInfo['filename'];
        $ext = strtolower($pathInfo['extension'] ?? '');

        // Nur 8-stellige IDs verarbeiten
        if (!preg_match('/^\d{8}$/', $fileName) || !in_array($ext, $extensions)) {
            continue;
        }

        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
        $mtime = filemtime($fullPath) ?: time();

        // Falls ID noch nicht vorhanden oder neue Endung eine höhere Prio hat (WebP am Anfang der Liste)
        if (isset($tempMap[$fileName]) && array_search($ext, $extensions) >= array_search($tempMap[$fileName]['ext'], $extensions)) {
            continue;
        }

        $tempMap[$fileName] = [
            'path' => $relPrefix . $file . '?c=' . $mtime,
            'ext'  => $ext,
        ];
    }

    foreach ($tempMap as $id => $data) {
        $results[$id] = $data['path'];
    }

    return $results;
}

/**
 * Baut den Image Cache im korrekten ID-zentrierten Format auf.
 */
function buildImageCache(string $mode, array $dirsToScan): array
{
    $cacheFile = Path::getCachePath('comic_image_cache.json');
    $existingCache = file_exists($cacheFile) ? json_decode(file_get_contents($cacheFile), true) : [];
    if (!is_array($existingCache)) {
        $existingCache = [];
    }

    $log = [];
    $typesToProcess = $mode === 'all' ? array_keys($dirsToScan) : explode(',', $mode);
    $totalFound = 0;

    foreach ($typesToProcess as $type) {
        if (!isset($dirsToScan[$type])) {
            continue;
        }

        $found = scanDirForCache($dirsToScan[$type]['path'], $dirsToScan[$type]['rel']);
        $count = count($found);
        $totalFound += $count;
        $log[] = "Typ '$type': $count Bilder gefunden.";

        foreach ($found as $id => $fullRelPath) {
            if (!isset($existingCache[$id])) {
                $existingCache[$id] = [];
            }
            $existingCache[$id][$type] = $fullRelPath;
        }
    }

    // Sortierung nach ID absteigend (neueste IDs zuerst)
    krsort($existingCache);

    if (file_put_contents($cacheFile, json_encode($existingCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        return [
            'success' => true,
            'log' => $log,
            'timestamp' => time(),
            'total_files' => count($existingCache), // Anzahl der Comic-IDs im Index
        ];
    }

    return ['success' => false, 'log' => ["Fehler: Cache-Datei konnte nicht geschrieben werden."]];
}

// === AJAX HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'build_cache') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $mode = $_POST['mode'] ?? 'all';
    $result = buildImageCache($mode, $dirsToScan);

    if ($result['success']) {
        $newSettings = [
            'last_run_timestamp' => $result['timestamp'],
            'total_files' => $result['total_files'],
        ];
        saveGeneratorSettings($configPath, $currentUser, $newSettings);
        $result['last_run_formatted'] = date('d.m.Y \u\m H:i:s', $result['timestamp']);
    }

    echo json_encode($result);
    exit;
}

// Daten für die View laden
$cacheSettings = loadGeneratorSettings($configPath, $currentUser);

$pageTitle = 'Adminbereich - Cache Builder';
$pageHeader = 'Bild-Cache aktualisieren';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="generator-container">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($cacheSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">
                        Cache zuletzt aktualisiert am <?php echo date('d.m.Y \u\m H:i:s', $cacheSettings['last_run_timestamp']); ?> Uhr
                        (<?php echo $cacheSettings['total_files']; ?> Comic-IDs im Index).
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Generierung durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>Bild-Cache Management (ID-zentriert)</h2>
            <p>
                Scannt Verzeichnisse und erstellt die <code>comic_image_cache.json</code>.
                Das Format stellt sicher, dass alle Assets (Thumbnails, LowRes, etc.) einer Comic-ID zugeordnet sind.
                Wird zwingend benötigt um die Bilder anzuzeigen!
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
            <p class="log-info"><span class="log-time">[System]</span> Bereit für Scan.</p>
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
            addLogMessage(`Starte ID-basierten Scan (Modus: ${mode})...`, 'info');

            const formData = new FormData();
            formData.append('action', 'build_cache');
            formData.append('mode', mode);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    data.log.forEach(line => addLogMessage(line, 'success'));
                    addLogMessage(`Fertig! Insgesamt ${data.total_files} Comic-IDs indexiert.`, 'success');

                    if (data.last_run_formatted) {
                        lastRunContainer.innerHTML = `<p class="status-message status-info">Cache zuletzt aktualisiert am ${data.last_run_formatted} Uhr (${data.total_files} IDs im Index).</p>`;
                    }
                    successNotification.style.display = 'block';
                } else {
                    addLogMessage('Fehler: ' + data.log.join(' '), 'error');
                }
            } catch (error) {
                addLogMessage(`Kritischer Fehler: ${error.message}`, 'error');
            } finally {
                buttons.forEach(b => b.disabled = false);
            }
        }

        buttons.forEach(btn => {
            btn.addEventListener('click', () => runCacheBuild(btn.dataset.mode));
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
