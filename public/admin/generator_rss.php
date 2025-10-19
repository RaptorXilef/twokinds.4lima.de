<?php
/**
 * Adminseite zum Generieren des RSS-Feeds für die Comic-Webseite.
 *
 * @file      ROOT/public/admin/generator_rss.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.2.0 Design vollständig an den Admin-Standard angepasst.
 * @since     2.3.0 Anpassung an versionierte comic_var.json (Schema v2).
 * @since     2.4.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     3.0.0 Vollständige Umstellung auf neueste Konstanten-Struktur und Entfernung redundanter Logik.
 * @since     4.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- HILFSFUNKTIONEN ---
function loadGeneratorSettings(string $filePath): array
{
    $defaults = [
        'generator_rss' => ['last_run_timestamp' => null]
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
    if (json_last_error() !== JSON_ERROR_NONE)
        return $defaults;
    if (!isset($settings['generator_rss']))
        $settings['generator_rss'] = $defaults['generator_rss'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

function loadJsonFile($filePath)
{
    if (!file_exists($filePath))
        return ['status' => 'error', 'message' => "Datei nicht gefunden: " . basename($filePath), 'data' => null];
    $content = file_get_contents($filePath);
    if ($content === false)
        return ['status' => 'error', 'message' => "Fehler beim Lesen: " . basename($filePath), 'data' => null];
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        return ['status' => 'error', 'message' => "Fehler beim Parsen von JSON in " . basename($filePath), 'data' => null];
    return ['status' => 'success', 'message' => "Datei " . basename($filePath) . " erfolgreich geladen.", 'data' => $data];
}

// --- AJAX-HANDLER ---
if (isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    $comicVarJsonPath = Path::getDataPath('comic_var.json');
    $rssConfigJsonPath = Path::getConfigPath('config_rss.json');
    $generatorSettingsJsonPath = Path::getConfigPath('config_generator_settings.json');


    switch ($action) {
        case 'generate_rss':
            try {
                $comicDataResult = loadJsonFile($comicVarJsonPath, $debugMode);
                $rssConfigResult = loadJsonFile($rssConfigJsonPath, $debugMode);

                if ($comicDataResult['status'] !== 'success') {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Fehler: ' . basename($comicVarJsonPath) . ' konnte nicht geladen werden.']);
                    exit;
                }

                $rssConfig = ['max_items' => 10, 'feed_title' => 'Twokinds in deutsch - Comic-Feed', 'feed_description' => 'Der offizielle RSS-Feed für die neuesten deutschen Übersetzungen von Twokinds.', 'homepage_url' => DIRECTORY_PUBLIC_URL];
                if ($rssConfigResult['status'] === 'success' && is_array($rssConfigResult['data'])) {
                    $rssConfig = array_merge($rssConfig, $rssConfigResult['data']);
                }

                $decodedData = $comicDataResult['data'];
                $comicData = (is_array($decodedData) && isset($decodedData['schema_version']) && $decodedData['schema_version'] >= 2) ? ($decodedData['comics'] ?? []) : $decodedData;

                $maxItems = $rssConfig['max_items'];
                $comicFiles = glob(DIRECTORY_PUBLIC_COMIC . DIRECTORY_SEPARATOR . '*.php');
                if ($comicFiles === false) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Fehler beim Zugriff auf das Comic-Verzeichnis.']);
                    exit;
                }
                rsort($comicFiles);

                $rssItems = [];
                $processedCount = 0;
                foreach ($comicFiles as $filePath) {
                    if ($processedCount >= $maxItems)
                        break;
                    if (preg_match('/^(\d{8})\.php$/', basename($filePath), $matches)) {
                        $comicId = $matches[1];
                        if (is_array($comicData) && isset($comicData[$comicId])) {
                            $comicInfo = $comicData[$comicId];
                            if (isset($comicInfo['type']) && $comicInfo['type'] === 'Comicseite' && !empty($comicInfo['name']) && !empty(trim(strip_tags($comicInfo['transcript'])))) {
                                $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
                                foreach ($imageExtensions as $ext) {
                                    $imageFileName = $comicId . $ext;
                                    if (file_exists(DIRECTORY_PUBLIC_IMG_COMIC_LOWRES . DIRECTORY_SEPARATOR . $imageFileName)) {
                                        $comicLink = htmlspecialchars(Url::getComicPageUrl(basename($filePath)));
                                        $imageUrl = htmlspecialchars(Url::getImgComicLowresUrl($imageFileName));
                                        $imageHtml = '<p><img src="' . $imageUrl . '" alt="' . htmlspecialchars($comicInfo['name']) . '" style="max-width: 100%; height: auto;" /></p>';
                                        $rssItems[] = [
                                            'title' => htmlspecialchars($comicInfo['name']),
                                            'link' => $comicLink,
                                            'guid' => $comicLink,
                                            'description' => $imageHtml . '<p>' . htmlspecialchars($comicInfo['transcript']) . '</p>',
                                            'pubDate' => date(DATE_RSS, strtotime($comicId))
                                        ];
                                        $processedCount++;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
                $channel = $xml->addChild('channel');
                $channel->addChild('title', $rssConfig['feed_title']);
                $channel->addChild('link', htmlspecialchars($rssConfig['homepage_url']));
                $channel->addChild('description', $rssConfig['feed_description']);
                $channel->addChild('language', 'de-de');
                $channel->addChild('lastBuildDate', date(DATE_RSS));

                foreach ($rssItems as $item) {
                    $rssItem = $channel->addChild('item');
                    foreach ($item as $key => $value) {
                        $rssItem->addChild($key, $value);
                    }
                }

                $rssOutputPath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'rss.xml';
                if (file_put_contents($rssOutputPath, $xml->asXML()) !== false) {
                    $response = ['success' => true, 'message' => 'RSS-Feed erfolgreich erstellt/aktualisiert.', 'rssUrl' => DIRECTORY_PUBLIC_URL . '/rss.xml'];
                } else {
                    $response['message'] = 'Fehler beim Speichern der rss.xml. Dateiberechtigungen prüfen.';
                    http_response_code(500);
                }
            } catch (Exception $e) {
                http_response_code(500);
                $response['message'] = 'Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage();
            }
            break;

        case 'save_settings':
            $currentSettings = loadGeneratorSettings($generatorSettingsJsonPath, $debugMode);
            $currentSettings['generator_rss']['last_run_timestamp'] = time();
            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings(Path::getConfigPath('config_generator_settings.json'), $debugMode);
$rssSettings = $settings['generator_rss'];
$comicDataResult = loadJsonFile(Path::getDataPath('comic_var.json'), $debugMode);
$rssConfigResult = loadJsonFile(Path::getConfigPath('config_rss.json'), $debugMode);

$pageTitle = 'Adminbereich - RSS Generator';
$pageHeader = 'RSS Feed Generator';
$siteDescription = 'Seite zum erstellen des RSS-Feeds.';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($rssSettings['last_run_timestamp']): ?>
                    <p class="status-message status-info">Letzte Ausführung am
                        <?php echo date('d.m.Y \u\m H:i:s', $rssSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>

            <h2>RSS-Feed generieren</h2>
            <p>Dieses Tool erstellt die <code>rss.xml</code>-Datei im Hauptverzeichnis der Webseite. Es liest die
                neuesten Comic-Einträge und die Konfigurationen aus den entsprechenden JSON-Dateien.</p>

            <div class="status-list">
                <div class="status-item">
                    <?php echo basename(Path::getDataPath('comic_var.json')); ?>:
                    <span
                        class="status-indicator <?php echo ($comicDataResult['status'] === 'success') ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $comicDataResult['status'] === 'success' ? 'OK' : 'Fehler'; ?></span>
                </div>
                <div class="status-item">
                    <?php echo basename(Path::getConfigPath('config_rss.json')); ?>:
                    <span
                        class="status-indicator <?php echo ($rssConfigResult['status'] === 'success') ? 'status-green-text' : 'status-red-text'; ?>"><?php echo $rssConfigResult['status'] === 'success' ? 'OK' : 'Fehler'; ?></span>
                </div>
            </div>

            <div id="fixed-buttons-container">
                <button id="generateRss" class="button" <?php echo ($comicDataResult['status'] !== 'success' || $rssConfigResult['status'] !== 'success') ? 'disabled' : ''; ?>>
                    RSS-Feed jetzt erstellen/aktualisieren
                </button>
            </div>
        </div>

        <div id="loading-spinner" class="hidden-by-default">
            <div class="spinner"></div>
            <p id="progress-text">Generiere RSS-Feed...</p>
        </div>

        <div id="generation-results-section" class="hidden-by-default">
            <h2>Ergebnis</h2>
            <p id="overall-status-message" class="status-message"></p>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    :root {
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --status-green-text: #155724;
        --status-red-text: #721c24;
    }

    body.theme-night {
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --status-green-text: #28a745;
        --status-red-text: #dc3545;
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

    .status-list {
        margin-top: 15px;
        border: 1px solid var(--missing-grid-border-color);
        border-radius: 5px;
        padding: 10px;
        background-color: var(--missing-grid-bg-color);
    }

    .status-item {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        border-bottom: 1px dashed var(--missing-grid-border-color);
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .status-indicator {
        font-weight: bold;
    }

    .status-green-text {
        color: var(--status-green-text);
    }

    .status-red-text {
        color: var(--status-red-text);
    }

    #fixed-buttons-container {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
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

    .hidden-by-default {
        display: none;
    }
</style>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        const generateButton = document.getElementById('generateRss');
        const spinner = document.getElementById('loading-spinner');
        const resultsSection = document.getElementById('generation-results-section');
        const statusMessage = document.getElementById('overall-status-message');
        const lastRunContainer = document.getElementById('last-run-container');

        async function saveSettings() {
            await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'save_settings', csrf_token: csrfToken })
            });
        }

        function updateTimestamp() {
            const now = new Date();
            const date = now.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = now.toLocaleTimeString('de-DE');
            const newStatusText = `Letzte Ausführung am ${date} um ${time} Uhr.`;

            let pElement = lastRunContainer.querySelector('.status-message');
            if (!pElement) {
                pElement = document.createElement('p');
                pElement.className = 'status-message status-info';
                lastRunContainer.prepend(pElement);
            }
            pElement.innerHTML = newStatusText;
        }

        generateButton.addEventListener('click', async function () {
            this.disabled = true;
            spinner.style.display = 'block';
            resultsSection.style.display = 'none';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'generate_rss', csrf_token: csrfToken })
                });

                const data = await response.json();
                resultsSection.style.display = 'block';
                statusMessage.className = data.success ? 'status-message status-green' : 'status-message status-red';

                let message = data.message;
                if (data.success && data.rssUrl) {
                    message += ` <a href="${data.rssUrl}" target="_blank">Feed ansehen</a>`;
                }
                statusMessage.innerHTML = message;

                if (data.success) {
                    await saveSettings();
                    updateTimestamp();
                }

            } catch (error) {
                resultsSection.style.display = 'block';
                statusMessage.className = 'status-message status-red';
                statusMessage.innerHTML = `Ein unerwarteter Fehler ist aufgetreten: ${error.message}.`;
            } finally {
                spinner.style.display = 'none';
                this.disabled = false;
            }
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>