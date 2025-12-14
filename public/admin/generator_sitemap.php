<?php

/**
 * Adminseite zum Generieren der sitemap.xml für Suchmaschinen.
 * Kombiniert statische Seiten (aus sitemap.json) und Comic-Seiten (aus comic_var.json).
 *
 * @file      ROOT/public/admin/generator_sitemap.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 2.0.0 - 4.0.0
 *    ARCHITEKTUR & DATEN
 *    - Vollständige Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Anpassung an das v2-Schema der `comic_var.json` und Entfernung redundanter Logik.
 *    - Dynamische Bestimmung von Web-Pfaden (`PUBLIC_COMIC_PATH`).
 *
 *    LOGIK & FEATURES
 *    - AJAX-basierte Generierung und dynamisches Hinzufügen aller Comic-Seiten.
 *    - Speicherung der letzten Ausführung.
 *
 *    UI & DESIGN
 *    - Vollständige Überarbeitung mit modernem UI.
 *
 * @since 5.0.0
 * - Komplettes Refactoring auf Admin-Standard (SCSS, User-Config, Layout).
 * - Fix: URL-Pfadbereinigung (kein /./ mehr) und ISO-8601 Datumsformat (mit Uhrzeit).
 * - Fix: Aggressive Pfad-Bereinigung (Regex) gegen "/./" Fehler.
 * - refactor(Config): Harmonisierung der Settings-Struktur (Flaches Array statt Verschachtelung).
 * - refactor(Config): Nutzung von 'admin/config_generator_settings.json'.
 * - fix(Logic): Korrekter Zugriff auf Benutzereinstellungen.
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
    // FIX: Flache Struktur für Defaults, konsistent mit anderen Editoren
    $defaults = [
        'last_run_timestamp' => null,
        'entries_count' => 0
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
    $userSettings = $data['users'][$username]['generator_sitemap'] ?? [];

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

    $currentData = $data['users'][$username]['generator_sitemap'] ?? [];
    $data['users'][$username]['generator_sitemap'] = array_replace_recursive($currentData, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// --- SITEMAP GENERIERUNG ---
function generateSitemap(): array
{
    $sitemapJsonPath = Path::getDataPath('sitemap.json');
    $comicVarPath = Path::getDataPath('comic_var.json');

    // Fallback falls Methode fehlt
    $publicDir = defined('DIRECTORY_PUBLIC') ? DIRECTORY_PUBLIC : dirname(dirname(__DIR__));
    $sitemapFilePath = $publicDir . DIRECTORY_SEPARATOR . 'sitemap.xml';

    $baseUrl = defined('DIRECTORY_PUBLIC_URL') ? DIRECTORY_PUBLIC_URL : 'https://twokinds.4lima.de';
    $baseUrl = rtrim($baseUrl, '/');

    // Daten laden
    if (!file_exists($sitemapJsonPath)) {
        throw new Exception('sitemap.json nicht gefunden.');
    }
    if (!file_exists($comicVarPath)) {
        throw new Exception('comic_var.json nicht gefunden.');
    }

    $staticData = json_decode(file_get_contents($sitemapJsonPath), true);
    $comicData = json_decode(file_get_contents($comicVarPath), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON Fehler beim Laden der Daten.');
    }

    // XML Aufbau
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    $urlset = $dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
    $urlset->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $urlset->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
    $dom->appendChild($urlset);

    $count = 0;

    // 1. Statische Seiten verarbeiten
    if (isset($staticData['pages']) && is_array($staticData['pages'])) {
        foreach ($staticData['pages'] as $page) {
            $loc = $page['loc'] ?? '';
            if (empty($loc)) {
                continue;
            }

            // FIX v5.0.2: Aggressive Bereinigung von "./" oder "/" am Anfang mit Regex
            $loc = preg_replace('/^(\.\/|\/)+/', '', $loc);

            $urlNode = $dom->createElement('url');
            $urlNode->appendChild($dom->createElement('loc', $baseUrl . '/' . $loc));

            if (isset($page['lastmod']) && !empty($page['lastmod'])) {
                // Wenn Datum manuell in JSON steht, übernehmen wir es
                $urlNode->appendChild($dom->createElement('lastmod', $page['lastmod']));
            } else {
                // FIX: ISO 8601 (mit Uhrzeit)
                $urlNode->appendChild($dom->createElement('lastmod', date('c')));
            }

            if (isset($page['changefreq'])) {
                $urlNode->appendChild($dom->createElement('changefreq', $page['changefreq']));
            }

            if (isset($page['priority'])) {
                $urlNode->appendChild($dom->createElement('priority', (string)$page['priority']));
            }

            $urlset->appendChild($urlNode);
            $count++;
        }
    }

    // 2. Comic Seiten verarbeiten
    // Support für v1 (direktes Array) und v2 (unter 'comics' Key)
    $comics = (isset($comicData['schema_version']) && $comicData['schema_version'] >= 2) ? ($comicData['comics'] ?? []) : $comicData;

    ksort($comics);

    $comicChangefreq = 'monthly';
    $comicPriority = '0.8';

    $comicPhpDir = defined('DIRECTORY_PUBLIC_COMIC') ? DIRECTORY_PUBLIC_COMIC : $publicDir . '/comic';

    foreach ($comics as $id => $data) {
        $urlNode = $dom->createElement('url');
        // URL Struktur: /comic/ID.php
        $urlNode->appendChild($dom->createElement('loc', $baseUrl . "/comic/$id.php"));

        $phpFile = $comicPhpDir . DIRECTORY_SEPARATOR . $id . '.php';
        if (file_exists($phpFile)) {
            // FIX: ISO 8601 Format 'c' verwenden (YYYY-MM-DDThh:mm:ss+ZO)
            $lastmod = date('c', filemtime($phpFile));
        } else {
            $lastmod = date('c');
        }

        $urlNode->appendChild($dom->createElement('lastmod', $lastmod));
        $urlNode->appendChild($dom->createElement('changefreq', $comicChangefreq));
        $urlNode->appendChild($dom->createElement('priority', $comicPriority));

        $urlset->appendChild($urlNode);
        $count++;
    }

    // Speichern
    if (!is_writable(dirname($sitemapFilePath))) {
        throw new Exception("Keine Schreibrechte im Verzeichnis: " . dirname($sitemapFilePath));
    }

    if ($dom->save($sitemapFilePath)) {
        return [
            'success' => true,
            'message' => "Sitemap erfolgreich generiert ($count URLs).",
            'count' => $count,
            'sitemapUrl' => $baseUrl . '/sitemap.xml'
        ];
    } else {
        throw new Exception('Fehler beim Schreiben der sitemap.xml.');
    }
}

// --- LOGIK ---
// FIX: Variable direkt nutzen, da loadGeneratorSettings nun flach zurückgibt
$sitemapSettings = loadGeneratorSettings($configPath, $currentUser);

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_sitemap') {
    ini_set('display_errors', '0');

    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        $result = generateSitemap();

        if ($result['success']) {
            $newSettings = [
                'last_run_timestamp' => time(),
                'entries_count' => $result['count']
            ];
            saveGeneratorSettings($configPath, $currentUser, $newSettings);
            $result['timestamp'] = $newSettings['last_run_timestamp'];
        }

        echo json_encode($result);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Server-Fehler: ' . $e->getMessage()]);
    }
    exit;
}

$pageTitle = 'Adminbereich - Sitemap Generator';
$pageHeader = 'Sitemap Generator';
require_once Path::getPartialTemplatePath('header.php');
?>

    <div class="generator-container">
        <!-- HEADER -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($sitemapSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">Letzter Lauf am
                        <?php echo date('d.m.Y \u\m H:i:s', $sitemapSettings['last_run_timestamp']); ?> Uhr
                        (<?php echo $sitemapSettings['entries_count']; ?> URLs).
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Generierung durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>Sitemap Generator</h2>
            <p>
                Erstellt die <code>sitemap.xml</code> für Suchmaschinen (Google, Bing).
                Sie enthält alle statischen Seiten (konfiguriert in <code>sitemap.json</code>) und alle verfügbaren Comic-Seiten.
            </p>
        </div>

        <!-- ACTIONS -->
        <div class="generator-actions">
            <button id="generate-btn" class="button button-green">
                <i class="fas fa-sitemap"></i> Sitemap erstellen
            </button>
        </div>

        <!-- LOG CONSOLE -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit zum Generieren.</p>
        </div>

        <!-- SUCCESS NOTIFICATION -->
        <div id="success-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Sitemap erstellt</h4>
            <p>Die Datei <code>sitemap.xml</code> wurde erfolgreich aktualisiert.</p>
            <div class="next-steps-actions">
                <a href="<?php echo DIRECTORY_PUBLIC_URL . '/sitemap.xml'; ?>" target="_blank" class="button button-orange">
                    <i class="fas fa-external-link-alt"></i> Sitemap ansehen
                </a>
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" class="button button-blue">
                    <i class="fas fa-home"></i> Zum Dashboard
                </a>
            </div>
        </div>

    </div>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';

        const generateButton = document.getElementById('generate-btn');
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

        async function generateSitemap() {
            generateButton.disabled = true;
            successNotification.style.display = 'none';
            logContainer.innerHTML = '';

            addLogMessage('Starte Generierung...', 'info');

            const formData = new FormData();
            formData.append('action', 'generate_sitemap');
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                let data;

                if (!response.ok) {
                    throw new Error(`HTTP Fehler ${response.status}: ${response.statusText}`);
                }

                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error("Raw Response:", responseText);
                    const errorMsg = responseText.replace(/<[^>]*>/g, '').substring(0, 200).trim();
                    throw new Error(`Ungültige Antwort: "${errorMsg}"`);
                }

                if (data.success) {
                    addLogMessage('Datenbanken geladen (sitemap.json + comic_var.json).', 'success');
                    addLogMessage(`Verarbeite ${data.count} URLs...`, 'info');
                    addLogMessage(data.message, 'success');

                    const now = new Date();
                    lastRunContainer.innerHTML = `<p class="status-message status-info">Letzter Lauf am ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr (${data.count} URLs).</p>`;

                    successNotification.style.display = 'block';
                    successNotification.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    addLogMessage(`Fehler: ${data.message}`, 'error');
                }

            } catch (error) {
                console.error(error);
                addLogMessage(`Kritischer Fehler: ${error.message}`, 'error');
            } finally {
                generateButton.disabled = false;
            }
        }

        generateButton.addEventListener('click', generateSitemap);
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
