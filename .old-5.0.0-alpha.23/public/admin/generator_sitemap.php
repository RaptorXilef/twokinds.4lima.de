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
 * - fix(Logic): Dubletten-Prüfung hinzugefügt, um doppelte URLs in der Sitemap zu verhindern.
 * - docs(Header): Variablen- und Funktionsdokumentation hinzugefügt.
 * - feat(SEO): Unterstützung für Clean URLs via PHP_EXTENTION Konstante.
 * - fix(SEO): Speziallogik für die Startseite (index.php -> /) hinzugefügt.
 * - docs(Header): Umfassende Variablen- und Funktionsdokumentation ergänzt.
 */

/*
 * Zusammenfassung der in dieser Datei verwendeten Variablen und Funktionen:
 *
 * Konstanten:
 * PHP_EXTENTION    (string): Enthält '.php' oder '' (leer), gesteuert durch PHP_BOOLEN in der Main-Config.
 *
 * Variablen:
 * $debugMode       (bool):   Schaltet detaillierte Fehlermeldungen ein/aus.
 * $configPath      (string): Pfad zur zentralen Einstellungs-Datei (JSON).
 * $currentUser     (string): Name des aktuell angemeldeten Administrators.
 * $sitemapSettings (array):  Geladene Einstellungen für diesen Generator (Zeitstempel, etc.).
 *
 * Funktionen:
 * loadGeneratorSettings: Lädt Zeitstempel und URL-Zähler aus der Config.
 * saveGeneratorSettings: Schreibt Generierungs-Metadaten zurück in die Config.
 * generateSitemap:       Hauptfunktion; erstellt XML-Struktur, bereinigt URLs und speichert Datei.
 *
 * saveGeneratorSettings(string $filePath, string $username, array $newSettings): bool
 * Speichert die aktuellen Statistiken nach einem erfolgreichen Lauf.
 *
 * generateSitemap(): array
 * Kernlogik zur Erstellung der sitemap.xml. Lädt Daten, prüft auf Eindeutigkeit
 * und schreibt die physikalische XML-Datei.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONFIGURATION ===
$configPath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

// Sicherstellen, dass die Erweiterungs-Konstante existiert
if (!defined('PHP_EXTENTION')) {
    define('PHP_EXTENTION', '.php');
}

// --- Einstellungsverwaltung ---
function loadGeneratorSettings(string $filePath, string $username): array
{
    $defaults = [
        'last_run_timestamp' => null,
        'entries_count' => 0,
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
    if (!file_exists($sitemapJsonPath) || !file_exists($comicVarPath)) {
        throw new Exception('Datenquellen fehlen.');
    }

    $staticData = json_decode(file_get_contents($sitemapJsonPath), true);
    $comicData = json_decode(file_get_contents($comicVarPath), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON-Formatfehler.');
    }

    // XML Aufbau
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    $urlset = $dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
    $urlset->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $urlset->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
    $dom->appendChild($urlset);

    $count = 0;
    $addedUrls = [];

    // 1. Statische Seiten verarbeiten
    if (isset($staticData['pages']) && is_array($staticData['pages'])) {
        foreach ($staticData['pages'] as $page) {
            $loc = $page['loc'] ?? '';
            if (empty($loc)) {
                continue;
            }

            $cleanLoc = preg_replace('/^(\.\/|\/)+/', '', $loc);
            $baseName = str_replace('.php', '', $cleanLoc);

            // Optimierte Index-Logik für Clean URLs
            if (PHP_EXTENTION === '') {
                if ($baseName === 'index') {
                    $baseName = ''; // Haupt-Index wird zu /
                } else {
                    // Entfernt '/index' am Ende von Pfaden, z.B. charaktere/index -> charaktere/
                    $baseName = preg_replace('/\/index$/', '/', $baseName);
                }
            }

            $fullUrl = $baseUrl . '/' . $baseName . PHP_EXTENTION;

            if (in_array($fullUrl, $addedUrls)) {
                continue;
            }

            $urlNode = $dom->createElement('url');
            $urlNode->appendChild($dom->createElement('loc', $fullUrl));
            $urlNode->appendChild($dom->createElement('lastmod', $page['lastmod'] ?? date('c')));

            if (isset($page['changefreq'])) {
                $urlNode->appendChild($dom->createElement('changefreq', $page['changefreq']));
            }

            if (isset($page['priority'])) {
                $urlNode->appendChild($dom->createElement('priority', (string)$page['priority']));
            }

            $urlset->appendChild($urlNode);
            $addedUrls[] = $fullUrl;
            $count++;
        }
    }

    // 2. Comic Seiten verarbeiten
    // Support für v1 (direktes Array) und v2 (unter 'comics' Key)
    $comics = isset($comicData['schema_version']) && $comicData['schema_version'] >= 2 ? ($comicData['comics'] ?? []) : $comicData;
    ksort($comics);

    $comicChangefreq = 'monthly';
    $comicPriority = '0.8';
    $comicPhpDir = defined('DIRECTORY_PUBLIC_COMIC') ? DIRECTORY_PUBLIC_COMIC : $publicDir . '/comic';

    foreach ($comics as $id => $data) {
        $fullUrl = $baseUrl . "/comic/" . $id . PHP_EXTENTION;

        if (in_array($fullUrl, $addedUrls)) {
            continue;
        }

        $urlNode = $dom->createElement('url');
        // URL Struktur: /comic/ID.php
        $urlNode->appendChild($dom->createElement('loc', $fullUrl));

        $phpFile = $comicPhpDir . DIRECTORY_SEPARATOR . $id . '.php';
        $lastmod = file_exists($phpFile) ? date('c', filemtime($phpFile)) : date('c');

        $urlNode->appendChild($dom->createElement('lastmod', $lastmod));
        $urlNode->appendChild($dom->createElement('changefreq', $comicChangefreq));
        $urlNode->appendChild($dom->createElement('priority', $comicPriority));

        $urlset->appendChild($urlNode);
        $addedUrls[] = $fullUrl;
        $count++;
    }

    // Speichern
    if (!is_writable(dirname($sitemapFilePath))) {
        throw new Exception("Schreibzugriff verweigert.");
    }

    if ($dom->save($sitemapFilePath)) {
        return [
            'success' => true,
            'message' => "Sitemap erstellt ($count URLs). Modus: " . (PHP_EXTENTION === '' ? 'Clean URLs' : 'PHP-Standard'),
            'count' => $count,
            'sitemapUrl' => $baseUrl . '/sitemap.xml',
        ];
    }

    throw new Exception('XML-Datei konnte nicht gespeichert werden.');
}

// --- LOGIK ---
$sitemapSettings = loadGeneratorSettings($configPath, $currentUser);

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_sitemap') {
    verify_csrf_token();
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        $result = generateSitemap();
        if ($result['success']) {
            $newSettings = [
                'last_run_timestamp' => time(),
                'entries_count' => $result['count'],
            ];
            saveGeneratorSettings($configPath, $currentUser, $newSettings);
            $result['timestamp'] = $newSettings['last_run_timestamp'];
        }
        echo json_encode($result);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Fehler: ' . $e->getMessage()]);
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
                <p class="status-message status-info">Letzte Sitemap-Erstellung:
                    <?php echo date('d.m.Y \u\m H:i:s', $sitemapSettings['last_run_timestamp']); ?> Uhr
                    (<?php echo $sitemapSettings['entries_count']; ?> URLs).
                </p>
            <?php else : ?>
                <p class="status-message status-orange">Noch keine Sitemap generiert.</p>
            <?php endif; ?>
        </div>
        <h2>Sitemap Generator (SEO Optimiert)</h2>
        <p>
            Aktueller Modus: <strong><?php echo PHP_EXTENTION === '' ? 'Clean URLs (ohne Endungen)' : 'Standard URLs (mit .php)'; ?></strong>
            <br>Verzeichnisse werden im Clean-Modus automatisch auf den Trailing Slash (/) statt /index umgeleitet.
        </p>
    </div>

    <!-- ACTIONS -->
    <div class="generator-actions">
        <button id="generate-btn" class="button button-green">
            <i class="fas fa-sitemap"></i> Jetzt sitemap.xml generieren
        </button>
    </div>

    <!-- LOG CONSOLE -->
    <div id="log-container" class="log-console">
        <p class="log-info"><span class="log-time">[System]</span> Bereit zur Verarbeitung.</p>
    </div>

    <!-- SUCCESS NOTIFICATION -->
    <div id="success-notification" class="notification-box hidden-by-default">
        <h4><i class="fas fa-check-circle"></i> Fertig!</h4>
        <p>Die <code>sitemap.xml</code> wurde erfolgreich aktualisiert.</p>
        <div class="next-steps-actions">
            <a href="<?php echo DIRECTORY_PUBLIC_URL . '/sitemap.xml'; ?>" target="_blank" class="button button-orange">Sitemap Ansehen</a>
            <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" class="button button-blue">Dashboard</a>
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

        generateButton.addEventListener('click', async () => {
            generateButton.disabled = true;
            successNotification.style.display = 'none';
            logContainer.innerHTML = '';
            addLogMessage('Bereite Daten vor...', 'info');

            const formData = new FormData();
            formData.append('action', 'generate_sitemap');
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    addLogMessage(data.message, 'success');
                    const now = new Date();
                    lastRunContainer.innerHTML = `<p class="status-message status-info">Letzte Sitemap-Erstellung: ${now.toLocaleDateString()} um ${now.toLocaleTimeString()} Uhr (${data.count} URLs).</p>`;
                    successNotification.style.display = 'block';
                    successNotification.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                } else {
                    addLogMessage(data.message, 'error');
                }
            } catch (error) {
                addLogMessage(`Fehler: ${error.message}`, 'error');
            } finally {
                generateButton.disabled = false;
            }
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
