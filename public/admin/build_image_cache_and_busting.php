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
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

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

    // Speichern
    if (file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT))) {
        return ['success' => true, 'log' => $log, 'timestamp' => $cacheData['last_updated']];
    } else {
        return ['success' => false, 'log' => array_merge($log, ["Fehler beim Schreiben der Cache-Datei!"])];
    }
}

// === AJAX HANDLER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'build_cache') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    $mode = $_POST['mode'] ?? 'all';
    $result = buildImageCache($mode);

    echo json_encode($result);
    exit;
}

// === VIEW ===
$cacheFilePath = Path::getCachePath('comic_image_cache.json');
$lastRunTimestamp = file_exists($cacheFilePath) ? filemtime($cacheFilePath) : null;

$pageTitle = 'Adminbereich - Cache Builder';
$pageHeader = 'Bild-Cache aktualisieren';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="generator-container">
        <!-- HEADER & INFO -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($lastRunTimestamp) : ?>
                    <p class="status-message status-info">
                        Cache zuletzt aktualisiert am <?php echo date('d.m.Y \u\m H:i:s', $lastRunTimestamp); ?> Uhr.
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Cache-Datei existiert noch nicht.</p>
                <?php endif; ?>
            </div>

            <h2>Bild-Cache Management</h2>
            <p>
                Dieses Tool scannt die Bildverzeichnisse und erstellt eine Index-Datei (JSON).
                Dies beschleunigt das Laden der Seite erheblich und ermöglicht das "Cache-Busting" (Browser zwingen, neue Bilder zu laden).
                <br><strong>Wann ausführen?</strong> Immer nachdem Bilder hochgeladen, gelöscht oder umbenannt wurden.
            </p>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="generator-actions actions-center">
            <button class="button button-blue cache-btn" data-mode="thumbnails">
                <i class="fas fa-images"></i> Nur Thumbnails
            </button>
            <button class="button button-blue cache-btn" data-mode="lowres,hires">
                <i class="fas fa-book-open"></i> Comic Seiten (Low/Hi)
            </button>
            <button class="button button-orange cache-btn" data-mode="socialmedia">
                <i class="fas fa-share-alt"></i> Social Media
            </button>
            <button class="button button-green cache-btn" data-mode="all">
                <i class="fas fa-sync"></i> Alles aktualisieren
            </button>
        </div>

        <!-- LOG CONSOLE -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit. Wähle eine Aktion.</p>
        </div>

        <!-- SUCCESS NOTIFICATION -->
        <div id="success-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Cache erfolgreich aktualisiert</h4>
            <p>Die Änderungen sind nun auf der Webseite wirksam. Was möchtest du als nächstes tun?</p>
            <div class="next-steps-actions">
                <!-- Option 1: RSS Generator (Neu & Erste Position) -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_rss' . ($dateiendungPHP ?? '.php'); ?>" class="button button-orange">
                    <i class="fas fa-rss"></i> Zum RSS Generator
                </a>

                <!-- Option 2: Dashboard -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" class="button button-blue">
                    <i class="fas fa-home"></i> Zum Dashboard
                </a>

                <!-- Option 3: Webseite prüfen -->
                <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>" target="_blank" class="button button-green">
                    <i class="fas fa-external-link-alt"></i> Webseite prüfen
                </a>
            </div>
        </div>

    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const logContainer = document.getElementById('log-container');
        const buttons = document.querySelectorAll('.cache-btn');
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
            addLogMessage(`Starte Cache-Update (Modus: ${mode})...`, 'info');

            const formData = new FormData();
            formData.append('action', 'build_cache');
            formData.append('mode', mode);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error(`HTTP Fehler ${response.status}`);

                const data = await response.json();

                if (data.success) {
                    // Logs anzeigen
                    data.log.forEach(line => addLogMessage(line, 'success'));
                    addLogMessage('Cache erfolgreich gespeichert.', 'success');

                    // Timestamp aktualisieren
                    const now = new Date();
                    lastRunContainer.innerHTML = `<p class="status-message status-info">Cache zuletzt aktualisiert am ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr (gerade eben).</p>`;

                    successNotification.style.display = 'block';
                    successNotification.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
