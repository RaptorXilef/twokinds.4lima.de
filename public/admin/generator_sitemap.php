<?php
/**
 * Administrationsseite zum Generieren der Sitemap.xml.
 *
 * @file      ROOT/public/admin/generate_sitemap.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.0.0 Vollständig überarbeitet mit modernem UI, Speicherung der letzten Ausführung und AJAX-basierter Generierung.
 * @since     2.1.0 Anpassung an v2-Schema der comic_var.json und dynamisches Hinzufügen aller Comic-Seiten.
 * @since     2.2.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     3.0.0 Vollständige Umstellung auf neueste Konstanten-Struktur und Entfernung redundanter Logik.
 * @since     3.0.1 Die KONSTANTE PUBLIC_COMIC_PATH wird jetzt verwendet, um den Web-Pfad zu den Comic-Seiten dynamisch zu bestimmen. * @since     4.0.0 Vollständige Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- HILFSFUNKTIONEN ---
function loadGeneratorSettings(string $filePath, bool $debugMode): array
{
    $defaults = [
        'generator_sitemap' => ['last_run_timestamp' => null]
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
    if (!isset($settings['generator_sitemap']))
        $settings['generator_sitemap'] = $defaults['generator_sitemap'];
    return $settings;
}

function saveGeneratorSettings(string $filePath, array $settings, bool $debugMode): bool
{
    $jsonContent = json_encode($settings, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

/**
 * Generiert die Sitemap.xml.
 */
function generateSitemap(bool $debugMode): array
{
    $sitemapJsonPath = Path::getDataPath('sitemap.json');
    $comicVarJsonPath = Path::getDataPath('comic_var.json');
    $sitemapJsonFile = basename($sitemapJsonPath);

    if (!file_exists($sitemapJsonPath)) {
        return ['success' => false, 'message' => 'Fehler: Konfigurationsdatei ' . $sitemapJsonFile . ' nicht gefunden.'];
    }

    $configContent = file_get_contents($sitemapJsonPath);
    $sitemapConfig = json_decode($configContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Fehler beim Dekodieren von ' . $sitemapJsonFile . ': ' . json_last_error_msg()];
    }
    if (!isset($sitemapConfig['pages']) || !is_array($sitemapConfig['pages'])) {
        return ['success' => false, 'message' => 'Fehler: Ungültiges Format in ' . $sitemapJsonFile . '.'];
    }

    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->appendChild($urlset);

    // Statische Seiten aus sitemap.json hinzufügen
    foreach ($sitemapConfig['pages'] as $page) {
        if (!isset($page['loc']))
            continue;
        $filePath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($page['loc'], './');
        if (!is_file($filePath))
            continue;

        $url = $xml->createElement('url');
        $locValue = DIRECTORY_PUBLIC_URL . '/' . ltrim($page['loc'], './');
        $url->appendChild($xml->createElement('loc', htmlspecialchars($locValue)));
        $url->appendChild($xml->createElement('lastmod', date('Y-m-d\TH:i:sP', filemtime($filePath))));
        if (isset($page['changefreq']))
            $url->appendChild($xml->createElement('changefreq', htmlspecialchars($page['changefreq'])));
        if (isset($page['priority']))
            $url->appendChild($xml->createElement('priority', htmlspecialchars(sprintf('%.1f', $page['priority']))));
        $urlset->appendChild($url);
    }

    // Dynamisch Comic-Seiten hinzufügen
    if (file_exists($comicVarJsonPath)) {
        $comicJsonContent = file_get_contents($comicVarJsonPath);
        $decodedData = json_decode($comicJsonContent, true);
        $comicData = (is_array($decodedData) && isset($decodedData['schema_version']) && $decodedData['schema_version'] >= 2) ? ($decodedData['comics'] ?? []) : ($decodedData ?? []);

        foreach (array_keys($comicData) as $comicId) {
            $comicFileName = $comicId . '.php';
            $comicFilePath = DIRECTORY_PUBLIC_COMIC . DIRECTORY_SEPARATOR . $comicFileName;
            if (file_exists($comicFilePath)) {
                $url = $xml->createElement('url');
                $locValue = Url::getComicPageUrl($comicFileName);
                $url->appendChild($xml->createElement('loc', htmlspecialchars($locValue)));
                $url->appendChild($xml->createElement('lastmod', date('Y-m-d\TH:i:sP', filemtime($comicFilePath))));
                $url->appendChild($xml->createElement('changefreq', 'weekly'));
                $url->appendChild($xml->createElement('priority', '0.7'));
                $urlset->appendChild($url);
            }
        }
    }

    $sitemapOutputPath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'sitemap.xml';
    if ($xml->save($sitemapOutputPath) !== false) {
        return ['success' => true, 'message' => 'Sitemap.xml erfolgreich generiert.', 'sitemapUrl' => DIRECTORY_PUBLIC_URL . '/sitemap.xml'];
    } else {
        return ['success' => false, 'message' => 'Fehler beim Speichern der sitemap.xml.'];
    }
}

// --- AJAX-Handler ---
if (isset($_POST['action'])) {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'generate_sitemap':
            $response = generateSitemap($debugMode);
            break;
        case 'save_settings':
            $generatorSettingsJsonPath = Path::getConfigPath('config_generator_settings.json');
            $currentSettings = loadGeneratorSettings($generatorSettingsJsonPath, $debugMode);
            $currentSettings['generator_sitemap']['last_run_timestamp'] = time();
            if (saveGeneratorSettings($generatorSettingsJsonPath, $currentSettings, $debugMode)) {
                $response['success'] = true;
            }
            break;
    }
    echo json_encode($response);
    exit;
}

$settings = loadGeneratorSettings(Path::getConfigPath('config_generator_settings.json'), $debugMode);
$sitemapSettings = $settings['generator_sitemap'];
$pageTitle = 'Adminbereich - Sitemap Generator';
$pageHeader = 'Sitemap Generator';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="content-section">
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($sitemapSettings['last_run_timestamp']): ?>
                    <p class="status-message status-info">Letzte Generierung am
                        <?php echo date('d.m.Y \u\m H:i:s', $sitemapSettings['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Sitemap Generator</h2>
            <p>Dieses Tool erstellt die <code>sitemap.xml</code>-Datei basierend auf der Konfiguration in
                <code><?php echo basename(Path::getDataPath('sitemap.json')); ?></code> und fügt automatisch alle
                Comic-Seiten aus
                <code><?php echo basename(Path::getDataPath('comic_var.json')); ?></code> hinzu.
                Die Sitemap wird im Hauptverzeichnis der Webseite abgelegt.
            </p>

            <div id="fixed-buttons-container">
                <button type="button" id="generate-sitemap-button" class="button">Sitemap generieren /
                    aktualisieren</button>
            </div>
        </div>

        <div id="loading-spinner" class="hidden-by-default">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Sitemap...</p>
        </div>

        <div id="generation-results-section" class="hidden-by-default">
            <h2>Ergebnis</h2>
            <p id="overall-status-message" class="status-message"></p>
        </div>
    </div>
</article>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
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
        const generateButton = document.getElementById('generate-sitemap-button');
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
            const newStatusText = `Letzte Generierung am ${date} um ${time} Uhr.`;

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
                    body: new URLSearchParams({ action: 'generate_sitemap', csrf_token: csrfToken })
                });

                const data = await response.json();
                resultsSection.style.display = 'block';
                statusMessage.className = data.success ? 'status-message status-green' : 'status-message status-red';

                let message = data.message;
                if (data.success && data.sitemapUrl) {
                    message += ` <a href="${data.sitemapUrl}" target="_blank">Sitemap ansehen</a>`;
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