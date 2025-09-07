<?php
/**
 * Adminseite zum Generieren des RSS-Feeds für die Comic-Webseite.
 * V2.1: Design an den Admin-Standard angepasst und Speicherfunktion integriert.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/src/components/admin_init.php';

// Pfade
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$comicVarJsonPath = __DIR__ . '/../src/config/comic_var.json';
$rssConfigJsonPath = __DIR__ . '/../src/config/rss_config.json';
$rssOutputPath = __DIR__ . '/../rss.xml';
$settingsFilePath = __DIR__ . '/../src/config/generator_settings.json';

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'generator_thumbnail' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_run_timestamp' => null],
        'generator_socialmedia' => ['last_used_format' => 'webp', 'last_used_quality' => 90, 'last_used_lossless' => false, 'last_used_resize_mode' => 'crop', 'last_run_timestamp' => null],
        'build_image_cache' => ['last_run_type' => null, 'last_run_timestamp' => null],
        'generator_comic' => ['last_run_timestamp' => null],
        'upload_image' => ['last_run_timestamp' => null],
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

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}


// Funktion zum Laden einer JSON-Datei
function loadJsonFile($filePath, $debugMode)
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

// Überprüfe, ob der RSS-Generierungs-Request gesendet wurde (AJAX-Call)
if (isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'generate_rss':
            try {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $appRootAbsPath = str_replace('\\', '/', dirname(dirname(__FILE__)));
                $documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
                $subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);
                if (!empty($subfolderPath) && $subfolderPath !== '/') {
                    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
                } elseif (empty($subfolderPath)) {
                    $subfolderPath = '/';
                }
                $baseUrl = $protocol . $host . $subfolderPath;

                $comicDataResult = loadJsonFile($comicVarJsonPath, $debugMode);
                $rssConfigResult = loadJsonFile($rssConfigJsonPath, $debugMode);

                if ($comicDataResult['status'] !== 'success') {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Fehler: comic_var.json konnte nicht geladen werden.']);
                    exit;
                }

                $rssConfig = ['max_items' => 10, 'feed_title' => 'Twokinds in deutsch - Comic-Feed', 'feed_description' => 'Der offizielle RSS-Feed für die neuesten deutschen Übersetzungen von Twokinds.', 'homepage_url' => $baseUrl];
                if ($rssConfigResult['status'] === 'success' && is_array($rssConfigResult['data'])) {
                    $rssConfig = array_merge($rssConfig, $rssConfigResult['data']);
                }

                $comicData = $comicDataResult['data'];
                $maxItems = $rssConfig['max_items'];
                $comicFiles = glob(__DIR__ . '/../comic/*.php');
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
                                    if (file_exists(__DIR__ . '/../assets/comic_lowres/' . $imageFileName)) {
                                        $comicLink = htmlspecialchars($baseUrl . 'comic/' . basename($filePath));
                                        $imageUrl = htmlspecialchars($baseUrl . 'assets/comic_lowres/' . $imageFileName);
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

                if (file_put_contents($rssOutputPath, $xml->asXML()) !== false) {
                    $response = ['success' => true, 'message' => 'RSS-Feed erfolgreich erstellt/aktualisiert.', 'rssUrl' => $baseUrl . 'rss.xml'];
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
            $currentSettings = loadGeneratorSettings($settingsFilePath, $debugMode);
            $currentSettings['generator_rss']['last_run_timestamp'] = time();
            if (saveGeneratorSettings($settingsFilePath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}
ob_end_flush();

$settings = loadGeneratorSettings($settingsFilePath, $debugMode);
$rssSettings = $settings['generator_rss'];
$comicDataResult = loadJsonFile($comicVarJsonPath, $debugMode);
$rssConfigResult = loadJsonFile($rssConfigJsonPath, $debugMode);

$pageTitle = 'Adminbereich - RSS Generator';
$pageHeader = 'RSS Feed Generator';
$siteDescription = 'Seite zum erstellen des RSS-Feeds.';
$robotsContent = 'noindex, nofollow';

include $headerPath;
?>

<article>
    <div class="admin-form-container">
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
                    <div
                        class="status-item <?php echo ($comicDataResult['status'] === 'success') ? 'status-green-text' : 'status-red-text'; ?>">
                        <?php echo htmlspecialchars(basename($comicVarJsonPath)); ?>:
                        <span
                            class="status-indicator"><?php echo $comicDataResult['status'] === 'success' ? 'OK' : 'Fehler'; ?></span>
                    </div>
                    <div
                        class="status-item <?php echo ($rssConfigResult['status'] === 'success') ? 'status-green-text' : 'status-red-text'; ?>">
                        <?php echo htmlspecialchars(basename($rssConfigJsonPath)); ?>:
                        <span
                            class="status-indicator"><?php echo $rssConfigResult['status'] === 'success' ? 'OK' : 'Fehler'; ?></span>
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
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    /* CSS-Variablen für Light- und Dark-Mode */
    :root {
        /* Light Mode Defaults */
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --default-text-color: #333;
        --status-green-text: #155724;
        --status-red-text: #721c24;
    }

    body.theme-night {
        /* Dark Mode Overrides */
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --default-text-color: #f0f0f0;
        --status-green-text: #28a745;
        --status-red-text: #dc3545;
    }

    /* Allgemeine Container- und Text-Stile */
    .admin-form-container {
        max-width: 825px;
        margin: 20px auto;
        padding: 20px;
        border: 1px solid rgba(221, 221, 221, 0.2);
        border-radius: 8px;
        background-color: rgba(240, 240, 240, 0.2);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        color: var(--default-text-color);
    }

    body.theme-night .admin-form-container {
        background-color: rgba(30, 30, 30, 0.2);
        border-color: rgba(80, 80, 80, 0.15);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .content-section {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }

    body.theme-night .content-section {
        border-bottom: 1px dashed #555;
    }

    .content-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .content-section h2,
    .content-section h3 {
        margin-bottom: 10px;
    }

    /* Statusmeldungen */
    .message {
        margin-bottom: 15px;
        font-weight: bold;
    }

    .status-message {
        padding: 8px 12px;
        border-radius: 5px;
        margin-top: 10px;
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

    body.theme-night .status-green {
        background-color: rgba(60, 118, 61, 0.3);
        border-color: rgba(214, 233, 198, 0.3);
        color: var(--status-green-text);
    }

    body.theme-night .status-red {
        background-color: rgba(169, 68, 66, 0.3);
        border-color: rgba(235, 204, 209, 0.3);
        color: var(--status-red-text);
    }

    body.theme-night .status-orange {
        background-color: rgba(138, 109, 59, 0.3);
        border-color: rgba(250, 235, 204, 0.3);
        color: #ffeeba;
    }

    /* Farbige Texte für Statusanzeigen */
    .status-green-text {
        color: var(--status-green-text);
    }

    .status-red-text {
        color: var(--status-red-text);
    }

    /* Button-Stile */
    .status-red-button,
    .status-green-button {
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
        border: 1px solid transparent;
        display: block;
        /* Stellt sicher, dass der Button Platz einnimmt */
        width: fit-content;
        /* Passt die Breite an den Inhalt an */
        margin-top: 10px;
        /* Abstand nach oben */
    }

    .status-red-button {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-green-button {
        background-color: #28a745;
        border-color: #28a745;
    }

    .status-green-button:hover {
        background-color: #218838;
    }

    /* Container für schwebende Buttons */
    .fixed-buttons-container {
        z-index: 1000;
        display: flex;
        gap: 10px;
        margin-top: 20px;
        margin-bottom: 20px;
        justify-content: flex-end;
    }

    /* Status-Liste (das "sichtbare Feld") */
    .status-list {
        margin-top: 10px;
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid var(--missing-grid-border-color);
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
    }

    .status-item {
        padding: 4px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed var(--missing-grid-border-color);
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .status-indicator {
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .fixed-buttons-container {
            flex-direction: column;
            gap: 5px;
            align-items: flex-end;
        }
    }

    /* Allgemeine Container- und Text-Stile */
    .admin-form-container {
        max-width: 825px;
        margin: 20px auto;
        padding: 20px;
        border: 1px solid rgba(221, 221, 221, 0.2);
        border-radius: 8px;
        background-color: rgba(240, 240, 240, 0.2);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        color: var(--default-text-color);
    }

    body.theme-night .admin-form-container {
        background-color: rgba(30, 30, 30, 0.2);
        border-color: rgba(80, 80, 80, 0.15);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .content-section {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }

    body.theme-night .content-section {
        border-bottom: 1px dashed #555;
    }

    .content-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .content-section h2,
    .content-section h3 {
        margin-bottom: 10px;
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

    /* Status-Liste (das "sichtbare Feld") */
    .status-list {
        margin-top: 10px;
        margin-bottom: 15px;
        padding: 10px;
        border: 1px solid var(--missing-grid-border-color);
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
    }

    .status-item {
        padding: 4px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed var(--missing-grid-border-color);
    }

    .status-item:last-child {
        border-bottom: none;
    }

    .status-indicator {
        font-weight: bold;
    }

    .status-green-text {
        color: #155724;
    }

    body.theme-night .status-green-text {
        color: #28a745;
    }

    .status-red-text {
        color: #721c24;
    }

    body.theme-night .status-red-text {
        color: #dc3545;
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

<?php include $footerPath; ?>