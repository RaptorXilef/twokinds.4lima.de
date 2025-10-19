<?php
/**
 * Administrationsseite zum Erstellen des Bild-Caches inkl. Cache-Busting.
 * * @file      ROOT/public/admin/build_image_cache_and_busting.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   3.0.0
 * @since     2.0.0 Überarbeitet mit modernem UI, CSP-Konformität und Speicherung der letzten Ausführung in der zentralen Einstellungs-JSON.
 * @since     2.1.0 Pfadanpassungen und Einführung von Konstanten
 * @since     2.1.1 Umstellung auf Template-Pfad-Konstanten
 * @since     2.2.0 Direkte Verwendung von Konstanten anstelle von temporären Variablen.
 * @since     2.3.0 Umstellung auf neue, granulare Asset-Pfad-Konstanten.
 * @since     3.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// TODO: Autoreload entfernen

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// Die zu scannenden Verzeichnisse werden dynamisch aufgebaut.
$dirsToScan = [
    'thumbnails' => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS, 'relativePath' => str_replace(DIRECTORY_PUBLIC_URL, '', DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS_URL) . '/'],
    'lowres' => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_LOWRES, 'relativePath' => str_replace(DIRECTORY_PUBLIC_URL, '', DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL) . '/'],
    'hires' => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_HIRES, 'relativePath' => str_replace(DIRECTORY_PUBLIC_URL, '', DIRECTORY_PUBLIC_IMG_COMIC_HIRES_URL) . '/'],
    'socialmedia' => ['path' => DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA, 'relativePath' => str_replace(DIRECTORY_PUBLIC_URL, '', DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA_URL) . '/']
];

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = ['build_image_cache' => ['last_run_type' => null, 'last_run_timestamp' => null]];
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
    if (!isset($settings['build_image_cache']))
        $settings['build_image_cache'] = $defaults['build_image_cache'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

/**
 * Scannt ein Verzeichnis und gibt Bildpfade mit Cache-Busting zurück.
 */
function scanDirectoryForImagesWithCacheBusting(string $dir, string $relativePathPrefix): array
{
    $images = [];
    if (!is_dir($dir))
        return [];
    $prioritizedExtensions = ['webp', 'png', 'gif', 'jpg', 'jpeg'];
    $allowedExtensions = array_flip($prioritizedExtensions);
    $baseNames = [];
    $files = scandir($dir);
    foreach ($files as $file) {
        $fileInfo = pathinfo($file);
        if (isset($fileInfo['filename'], $fileInfo['extension']) && !empty($fileInfo['filename']) && isset($allowedExtensions[strtolower($fileInfo['extension'])])) {
            $baseNames[$fileInfo['filename']] = true;
        }
    }
    foreach (array_keys($baseNames) as $baseName) {
        foreach ($prioritizedExtensions as $ext) {
            $fileName = $baseName . '.' . $ext;
            $absolutePath = $dir . DIRECTORY_SEPARATOR . $fileName;
            if (is_file($absolutePath)) {
                $lastModified = @filemtime($absolutePath) ?: time();
                $images[$baseName] = ltrim($relativePathPrefix, '/') . $fileName . '?c=' . $lastModified;
                break;
            }
        }
    }
    return $images;
}


// --- AJAX-Anfrage-Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    $comicImageCacheJsonPath = Path::getCachePath('comic_image_cache.json');
    $generatorSettingsJsonPath = Path::getConfigPath('config_generator_settings.json');


    switch ($action) {
        case 'build_cache':
            $typesToProcess = [];
            $typeInput = $_POST['type'] ?? '';
            if ($typeInput === 'all') {
                $typesToProcess = array_keys($dirsToScan);
            } else {
                $submittedTypes = explode(',', $typeInput);
                foreach ($submittedTypes as $submittedType) {
                    if (array_key_exists($submittedType, $dirsToScan)) {
                        $typesToProcess[] = $submittedType;
                    }
                }
            }
            if (empty($typesToProcess)) {
                $response['message'] = 'Ungültiger Typ für Cache-Erstellung.';
                echo json_encode($response);
                exit;
            }

            $existingCache = file_exists($comicImageCacheJsonPath) ? json_decode(file_get_contents($comicImageCacheJsonPath), true) : [];
            if (!is_array($existingCache))
                $existingCache = [];

            foreach ($typesToProcess as $currentType) {
                $dirInfo = $dirsToScan[$currentType];
                $foundImages = scanDirectoryForImagesWithCacheBusting($dirInfo['path'], $dirInfo['relativePath']);
                $response['counts'][$currentType] = count($foundImages);
                foreach ($foundImages as $comicId => $path) {
                    if (!isset($existingCache[$comicId]))
                        $existingCache[$comicId] = [];
                    $existingCache[$comicId][$currentType] = $path;
                }
            }
            if (file_put_contents($comicImageCacheJsonPath, json_encode($existingCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                $response['success'] = true;
                $response['message'] = 'Cache erfolgreich aktualisiert für: ' . implode(', ', $typesToProcess);
            } else {
                $response['message'] = 'Fehler: Die Cache-Datei konnte nicht geschrieben werden.';
                error_log("Fehler beim Schreiben der Cache-Datei: " . $comicImageCacheJsonPath);
            }
            break;

        case 'save_settings':
            $currentSettings = loadGeneratorSettings($generatorSettingsJsonPath, $debugMode);
            $newCacheSettings = [
                'last_run_type' => $_POST['type'] ?? 'unknown',
                'last_run_timestamp' => time()
            ];
            $currentSettings['build_image_cache'] = $newCacheSettings;
            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}
ob_end_flush();

$settings = loadGeneratorSettings(Path::getConfigPath('config_generator_settings.json'), $debugMode);
$cacheSettings = $settings['build_image_cache'];

$pageTitle = 'Adminbereich - Bild-Cache & Busting Generator';
$pageHeader = 'Bild-Cache & Busting Generator';
$siteDescription = 'Tool zum Erstellen des Bild-Caches mit Cache-Busting-Parametern.';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="content-section">
        <?php if ($cacheSettings['last_run_timestamp']): ?>
            <p class="status-message status-info">Letzte Ausführung:
                <strong><?php echo htmlspecialchars($cacheSettings['last_run_type']); ?></strong> am
                <?php echo date('d.m.Y \u\m H:i:s', $cacheSettings['last_run_timestamp']); ?> Uhr.
            </p>
        <?php endif; ?>

        <h2>Cache inkl. Cache-Busting aktualisieren</h2>
        <p>Dieses Tool scannt die Bildverzeichnisse, hängt den Zeitstempel der letzten Dateiänderung als
            Cache-Busting-Parameter an (<code>?c=...</code>) und speichert das Ergebnis in
            <code>comic_image_cache.json</code>. Dies stellt sicher, dass Browser immer die neuste
            Version
            eines
            Bildes laden, nachdem es geändert wurde.
        </p>


        <div id="fixed-buttons-container">
            <button type="button" class="cache-build-button" data-type="thumbnails">Thumbnails</button>
            <button type="button" class="cache-build-button" data-type="lowres">Low-Res</button>
            <button type="button" class="cache-build-button" data-type="hires">High-Res</button>
            <button type="button" class="cache-build-button" data-type="socialmedia">Social Media</button>
            <button type="button" id="build-all-button" class="cache-build-button" data-type="all"><strong>Alle
                    aktualisieren</strong></button>
        </div>

        <div id="loading-spinner" class="hidden-by-default">
            <div class="spinner"></div>
            <p id="progress-text">Aktualisiere Cache...</p>
        </div>

        <div id="generation-results-section" class="hidden-by-default">
            <h2>Ergebnis</h2>
            <p id="overall-status-message" class="status-message"></p>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    #fixed-buttons-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .cache-build-button {
        padding: 8px 15px;
        border: 1px solid #007bff;
        background-color: transparent;
        color: #007bff;
        border-radius: 5px;
        cursor: pointer;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .cache-build-button:hover,
    .cache-build-button:focus {
        background-color: #007bff;
        color: white;
    }

    .cache-build-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #ced4da;
        cursor: not-allowed;
    }

    #build-all-button {
        background-color: #007bff;
        color: white;
    }

    #build-all-button:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    body.theme-night .cache-build-button {
        color: #09f;
        border-color: #09f;
    }

    body.theme-night .cache-build-button:hover,
    body.theme-night .cache-build-button:focus {
        background-color: #09f;
        color: #fff;
    }

    body.theme-night #build-all-button {
        background-color: #09f;
        border-color: #09f;
        color: #fff;
    }

    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border-left-color: #09f;
        animation: spin 1s ease infinite;
        margin: 20px auto 10px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
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

    .hidden-by-default {
        display: none;
    }

    #loading-spinner {
        text-align: center;
        margin-top: 20px;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const buttons = document.querySelectorAll('.cache-build-button');
        const spinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const resultsSection = document.getElementById('generation-results-section');
        const statusMessage = document.getElementById('overall-status-message');

        async function saveLastRun(type) {
            await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'save_settings', type: type, csrf_token: csrfToken })
            });
        }

        async function runCacheBuild(type) {
            let typeName = type.replace(/,/, ' & ');
            const button = document.querySelector(`.cache-build-button[data-type="${type}"]`);
            if (button && type !== 'all') typeName = button.textContent;
            if (type === 'all') typeName = 'Alle';

            resultsSection.style.display = 'none';
            statusMessage.innerHTML = '';
            spinner.style.display = 'block';
            progressText.textContent = `Aktualisiere Cache für '${typeName}'... Bitte warten.`;
            buttons.forEach(b => b.disabled = true);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'build_cache', type: type, csrf_token: csrfToken })
                });

                if (!response.ok) throw new Error(`HTTP-Fehler: Status ${response.status}`);
                const data = await response.json();
                resultsSection.style.display = 'block';
                statusMessage.className = data.success ? 'status-message status-green' : 'status-message status-red';

                let message = data.message;
                if (data.success && data.counts) {
                    const countDetails = Object.entries(data.counts)
                        .map(([key, value]) => `<li><strong>${key}:</strong> ${value} Bilder gefunden</li>`)
                        .join('');
                    message += `<br><br><strong>Details:</strong><ul>${countDetails}</ul>`;
                }
                statusMessage.innerHTML = message;

                if (data.success) {
                    await saveLastRun(type);
                    // Lade die Seite neu, um die "Letzte Ausführung"-Nachricht zu aktualisieren
                    setTimeout(() => window.location.reload(), 2000);
                }

            } catch (error) {
                resultsSection.style.display = 'block';
                statusMessage.className = 'status-message status-red';
                statusMessage.innerHTML = `Ein unerwarteter Fehler ist aufgetreten: ${error.message}.`;
                console.error('Fehler bei der Cache-Erstellung:', error);
            } finally {
                spinner.style.display = 'none';
                if (!statusMessage.classList.contains('status-green')) {
                    buttons.forEach(b => b.disabled = false);
                }
            }
        }

        buttons.forEach(button => {
            button.addEventListener('click', function () {
                runCacheBuild(this.dataset.type);
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const autostartType = urlParams.get('autostart');
        if (autostartType) {
            const allowedTypes = ['thumbnails', 'lowres', 'hires', 'socialmedia', 'all', 'lowres,hires'];
            if (allowedTypes.includes(autostartType)) {
                runCacheBuild(autostartType);
            }
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>