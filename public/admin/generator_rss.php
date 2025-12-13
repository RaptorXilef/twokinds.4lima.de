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
 *
 * @since 2.0.0 - 4.0.0
 *    ARCHITEKTUR & DATEN
 *    - Vollständige Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Anpassung an versionierte `comic_var.json` (Schema v2) und Bereinigung redundanter Logik.
 *
 *    LOGIK & FEATURES
 *    - RSS-Datum basiert nun auf dem tatsächlichen Änderungsdatum der Bilddatei (filemtime).
 *
 *    UI & DESIGN
 *    - Vollständige Anpassung des Designs an den Admin-Standard.
 *
 * @since 5.0.0
 * - Komplettes Refactoring auf Admin-Standard (SCSS, User-Config, Layout).
 * - Fix: Robustes Error-Handling (PHP/JS) und Pfad-Korrektur für rss.xml.
 * - Fix: XML-Struktur an Original-Format angepasst (Links auf /comic/, exakte Description).
 * - Fix: Daten (Titel, Transkript) werden nun korrekt aus der comic_var.json bezogen.
 * - Fix: HTML-Nesting-Fehler in Description behoben (doppelte P-Tags entfernt).
 * - Workflow: Link zum Sitemap-Editor in Erfolgsmeldung hinzugefügt.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> generator_rss).
 * - fix(UI): Fallback-Anzeige für fehlenden Zeitstempel.
 * - fix(XML): Wiederherstellung der ursprünglichen XML-Generierungslogik (SimpleXML) mit Bild-Einbettung in Description.
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
    // Flache Struktur für den Rückgabewert (Konsistent mit anderen Editoren)
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
    $userSettings = $data['users'][$username]['generator_rss'] ?? [];

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

    $currentData = $data['users'][$username]['generator_rss'] ?? [];
    $data['users'][$username]['generator_rss'] = array_replace_recursive($currentData, $newSettings);

    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// --- RSS GENERIERUNG (Logik wiederhergestellt) ---
function generateRssFeed(): array
{
    $comicVarPath = Path::getDataPath('comic_var.json');
    $publicDir = defined('DIRECTORY_PUBLIC') ? DIRECTORY_PUBLIC : dirname(dirname(__DIR__));
    $rssFilePath = $publicDir . DIRECTORY_SEPARATOR . 'rss.xml';

    $baseUrl = defined('DIRECTORY_PUBLIC_URL') ? DIRECTORY_PUBLIC_URL : 'https://twokinds.4lima.de';
    $baseUrl = rtrim($baseUrl, '/');

    if (!file_exists($comicVarPath)) {
        throw new Exception('comic_var.json nicht gefunden.');
    }

    $content = file_get_contents($comicVarPath);
    $comicData = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON Fehler: ' . json_last_error_msg());
    }

    // Support für v1 und v2 Schema
    $comics = (isset($comicData['schema_version']) && $comicData['schema_version'] >= 2) ? ($comicData['comics'] ?? []) : $comicData;

    if (empty($comics)) {
        return ['success' => false, 'message' => 'Keine Comics in der Datenbank gefunden.'];
    }

    // Sortieren (neueste zuerst)
    krsort($comics);

    // XML Aufbau mit SimpleXML
    $xmlString = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"></rss>';
    $xml = new SimpleXMLElement($xmlString);

    $channel = $xml->addChild('channel');
    $channel->addChild('title', 'Twokinds auf Deutsch');
    $channel->addChild('link', $baseUrl);
    $channel->addChild('description', 'Die deutsche Übersetzung des Webcomics Twokinds.');
    $channel->addChild('language', 'de-de');
    $channel->addChild('lastBuildDate', date(DATE_RSS));
    $channel->addChild('generator', 'Twokinds Admin Panel Generator');

    // Atom Self Link
    $atomLink = $channel->addChild('atom:link', '', 'http://www.w3.org/2005/Atom');
    $atomLink->addAttribute('href', $baseUrl . '/rss.xml');
    $atomLink->addAttribute('rel', 'self');
    $atomLink->addAttribute('type', 'application/rss+xml');

    $count = 0;
    $limit = 50;

    $lowResPath = defined('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES') ? DIRECTORY_PUBLIC_IMG_COMIC_LOWRES : $publicDir . '/assets/images/comic/lowres';
    $lowResUrl = defined('DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL') ? DIRECTORY_PUBLIC_IMG_COMIC_LOWRES_URL : $baseUrl . '/assets/images/comic/lowres';

    foreach ($comics as $id => $comicDataRaw) {
        if ($count >= $limit) {
            break;
        }

        // Sicherheits-Wrapper für v1/v2 Datenstruktur
        $comic = is_array($comicDataRaw) ? $comicDataRaw : ['name' => $comicDataRaw, 'transcript' => ''];

        // Bild finden
        $imageExtensions = ['webp', 'jpg', 'jpeg', 'png'];
        $imageFile = null;
        $extFound = '';

        foreach ($imageExtensions as $ext) {
            if (file_exists($lowResPath . DIRECTORY_SEPARATOR . $id . '.' . $ext)) {
                $imageFile = $lowResPath . DIRECTORY_SEPARATOR . $id . '.' . $ext;
                $extFound = $ext;
                break;
            }
        }

        if ($imageFile) {
            $item = $channel->addChild('item');

            $titleText = !empty($comic['name']) ? $comic['name'] : "Seite $id";
            $item->addChild('title', htmlspecialchars($titleText));

            // Link Format: /comic/ID.php
            $link = $baseUrl . "/comic/$id.php";
            $item->addChild('link', $link);
            $item->addChild('guid', $link);

            // BESCHREIBUNG (HTML mit Bild)
            $imgSrc = "$lowResUrl/$id.$extFound";

            // Teil 1: Bild im P-Tag
            $descContent = "<p><img src=\"$imgSrc\" alt=\"$titleText\" style=\"max-width: 100%; height: auto;\" /></p>";

            // Teil 2: Text (Transkript oder Titel)
            if (!empty($comic['transcript'])) {
                $descContent .= $comic['transcript'];
            } else {
                $descContent .= "<p>$titleText</p>";
            }

            // Sauber in XML einfügen (encoded)
            $node = dom_import_simplexml($item);
            $no = $node->ownerDocument;
            $node->appendChild($no->createElement('description', htmlspecialchars($descContent)));

            // Datum
            $date = filemtime($imageFile);
            $item->addChild('pubDate', date('r', $date));

            $count++;
        }
    }

    // Speichern via DOMDocument für Pretty Print
    $dom = new DOMDocument("1.0");
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());

    if (!is_writable(dirname($rssFilePath))) {
        throw new Exception("Keine Schreibrechte im Verzeichnis: " . dirname($rssFilePath));
    }

    if ($dom->save($rssFilePath)) {
        return [
            'success' => true,
            'message' => "RSS-Feed erfolgreich generiert ($count Einträge).",
            'count' => $count,
            'rssUrl' => $baseUrl . '/rss.xml'
        ];
    } else {
        throw new Exception('Fehler beim Schreiben der rss.xml.');
    }
}

// --- LOGIK ---
// Einstellungen laden (Flaches Array)
$rssSettings = loadGeneratorSettings($configPath, $currentUser);

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_rss') {
    ini_set('display_errors', '0');

    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        $result = generateRssFeed();

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

$pageTitle = 'Adminbereich - RSS Generator';
$pageHeader = 'RSS Generator';
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="generator-container">
        <!-- HEADER -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if ($rssSettings['last_run_timestamp']) : ?>
                    <p class="status-message status-info">Letzter Lauf am
                        <?php echo date('d.m.Y \u\m H:i:s', $rssSettings['last_run_timestamp']); ?> Uhr
                        (<?php echo $rssSettings['entries_count']; ?> Einträge).
                    </p>
                <?php else : ?>
                    <p class="status-message status-orange">Noch keine Generierung durchgeführt.</p>
                <?php endif; ?>
            </div>
            <h2>RSS Generator</h2>
            <p>
                Generiert die <code>rss.xml</code> für News-Reader und Bots.
                Der Feed enthält die letzten 50 Comics inkl. Vorschaubild und verlinkt direkt auf die entsprechenden Seiten.
            </p>
        </div>

        <!-- ACTIONS -->
        <div class="generator-actions">
            <button id="generate-btn" class="button button-green">
                <i class="fas fa-rss"></i> RSS-Feed generieren
            </button>
        </div>

        <!-- LOG CONSOLE -->
        <div id="log-container" class="log-console">
            <p class="log-info"><span class="log-time">[System]</span> Bereit zum Generieren.</p>
        </div>

        <!-- SUCCESS NOTIFICATION -->
        <div id="success-notification" class="notification-box hidden-by-default">
            <h4><i class="fas fa-check-circle"></i> Feed erstellt</h4>
            <p>Die Datei <code>rss.xml</code> wurde erfolgreich aktualisiert.</p>
            <div class="next-steps-actions">
                <!-- Option 1: Sitemap Editor -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_sitemap' . ($dateiendungPHP ?? '.php'); ?>" class="button button-green">
                    <i class="fas fa-sitemap"></i> Zur Sitemap
                </a>

                <!-- Option 2: Feed ansehen -->
                <a href="<?php echo DIRECTORY_PUBLIC_URL . '/rss.xml'; ?>" target="_blank" class="button button-orange">
                    <i class="fas fa-rss-square"></i> Feed ansehen
                </a>

                <!-- Option 3: Dashboard -->
                <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" class="button button-blue">
                    <i class="fas fa-home"></i> Zum Dashboard
                </a>
            </div>
        </div>

    </div>
</article>

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

        async function generateFeed() {
            generateButton.disabled = true;
            successNotification.style.display = 'none';
            logContainer.innerHTML = '';

            addLogMessage('Starte Generierung...', 'info');

            const formData = new FormData();
            formData.append('action', 'generate_rss');
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
                    const errorMsg = responseText.replace(/<[^>]*>/g, '').substring(0, 200).replace(/\s+/g, ' ').trim();
                    throw new Error(`Ungültige Server-Antwort: "${errorMsg}"`);
                }

                if (data.success) {
                    addLogMessage('Datenbank geladen.', 'success');
                    addLogMessage(`Verarbeite ${data.count} Comics...`, 'info');
                    addLogMessage(data.message, 'success');

                    const now = new Date();
                    lastRunContainer.innerHTML = `<p class="status-message status-info">Letzter Lauf am ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr (${data.count} Einträge).</p>`;

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

        generateButton.addEventListener('click', generateFeed);
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
