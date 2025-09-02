<?php
/**
 * Dies ist die Administrationsseite zum Bearbeiten der sitemap.json Konfigurationsdatei.
 * Sie ermöglicht das Hinzufügen, Bearbeiten und Löschen von Sitemap-Einträgen
 * über eine benutzerfreundliche Oberfläche.
 *
 * Zusätzlich werden fehlende PHP-Dateien aus dem 'comic/'-Verzeichnis automatisch hinzugefügt
 * und die Gesamt-Sitemap wird gespeichert.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: data_editor_sitemap.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in data_editor_sitemap.php gestartet.");

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();

// NEU: Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
require_once __DIR__ . '/../src/components/security_check.php';

if ($debugMode)
    error_log("DEBUG: Session gestartet in data_editor_sitemap.php.");

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httpholy"]
        );
    }

    // Zerstöre die Session.
    session_destroy();

    // Weiterleitung zur Login-Seite (index.php im Admin-Bereich).
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von data_editor_sitemap.php.");
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in data_editor_sitemap.php angemeldet.");

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$sitemapJsonPath = __DIR__ . '/../src/config/sitemap.json';
// Pfad zum Comic-Verzeichnis ist von Hauptverzeichnis/admin/ -> Hauptverzeichnis/comic/
$webRootPath = realpath(__DIR__ . '/../'); // Der tatsächliche Webroot
$comicDirPath = $webRootPath . '/comic/'; // Absoluter Pfad zum Comic-Verzeichnis
if ($debugMode) {
    error_log("DEBUG: Pfade definiert: sitemapJsonPath=" . $sitemapJsonPath . ", comicDirPath=" . $comicDirPath);
}

// Konstante für die Anzahl der Elemente pro Seite für die Comic-Tabelle
if (!defined('COMIC_PAGES_PER_PAGE')) {
    define('COMIC_PAGES_PER_PAGE', 50);
    if ($debugMode)
        error_log("DEBUG: COMIC_PAGES_PER_PAGE definiert: " . COMIC_PAGES_PER_PAGE);
}

// Setze Parameter für den Header.
$pageTitle = 'Sitemap Editor';
$pageHeader = 'Sitemap Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen
if ($debugMode) {
    error_log("DEBUG: Seiten-Titel: " . $pageTitle);
    error_log("DEBUG: Robots-Content: " . $robotsContent);
}

$message = '';
$messageType = ''; // 'success' or 'error'

/**
 * Lädt Sitemap-Daten aus einer JSON-Datei und stellt sicher, dass alle notwendigen Schlüssel vorhanden sind.
 * Wenn 'loc' fehlt, wird es aus 'path' und 'name' zusammengesetzt.
 * Wenn 'name' oder 'path' fehlen, werden sie aus 'loc' abgeleitet.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Die dekodierten Daten als assoziatives Array oder ein leeres Array im Fehlerfall.
 */
function loadSitemapData(string $path, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: loadSitemapData() aufgerufen für: " . basename($path));
    if (!file_exists($path) || filesize($path) === 0) {
        if ($debugMode)
            error_log("DEBUG: Sitemap-JSON-Datei nicht gefunden: " . $path);
        return ['pages' => []];
    }
    $content = file_get_contents($path);
    if ($content === false) {
        error_log("FEHLER: loadSitemapData: Fehler beim Lesen des Inhalts von sitemap.json: " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Lesen des Inhalts von: " . $path);
        return ['pages' => []];
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("FEHLER: loadSitemapData: Fehler beim Dekodieren von sitemap.json: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren von sitemap.json: " . json_last_error_msg());
        return ['pages' => []];
    }
    if ($debugMode)
        error_log("DEBUG: Sitemap-Daten erfolgreich geladen und dekodiert.");

    $pages = isset($data['pages']) && is_array($data['pages']) ? $data['pages'] : [];
    $sanitizedPages = [];

    foreach ($pages as $page) {
        // Standardwerte für alle Felder
        $loc = isset($page['loc']) ? (string) $page['loc'] : '';
        $name = isset($page['name']) ? (string) $page['name'] : '';
        $path = isset($page['path']) ? (string) $page['path'] : './';
        $priority = isset($page['priority']) ? (float) $page['priority'] : 0.5;
        $changefreq = isset($page['changefreq']) ? (string) $page['changefreq'] : 'weekly';

        // Logik zur Konsolidierung/Ableitung der Felder
        if (empty($loc)) {
            if ($debugMode)
                error_log("DEBUG: 'loc' ist leer für einen Eintrag. Versuche Ableitung.");
            // Wenn 'loc' fehlt, aber 'name' vorhanden ist, 'loc' aus 'path' und 'name' zusammensetzen
            if (!empty($name)) {
                $normalizedPath = rtrim($path, '/\\');
                if ($normalizedPath === '.') {
                    $normalizedPath = '';
                } else {
                    $normalizedPath .= '/';
                }
                $loc = $normalizedPath . $name;
                if ($debugMode)
                    error_log("DEBUG: 'loc' aus 'path' und 'name' abgeleitet: " . $loc);
            } else {
                // Wenn weder 'loc' noch 'name' vorhanden sind, Eintrag überspringen
                error_log("WARNUNG: loadSitemapData: Eintrag mit unzureichenden Daten übersprungen (weder 'loc' noch 'name' vorhanden): " . print_r($page, true));
                if ($debugMode)
                    error_log("DEBUG: Eintrag übersprungen: Weder 'loc' noch 'name' vorhanden.");
                continue;
            }
        } else { // 'loc' ist vorhanden
            if ($debugMode)
                error_log("DEBUG: 'loc' ist vorhanden: " . $loc);
            // Wenn 'name' fehlt, aus 'loc' ableiten
            if (empty($name)) {
                $name = basename($loc);
                if ($debugMode)
                    error_log("DEBUG: 'name' aus 'loc' abgeleitet: " . $name);
            }
            // Wenn 'path' fehlt oder nicht konsistent ist, aus 'loc' ableiten
            // Nur ableiten, wenn der aktuelle Pfad nicht schon der erwartete Basispfad für Comic-Seiten ist
            if ($path !== './comic/') { // Vermeide Überschreiben von explizitem './comic/'
                $derivedPath = dirname($loc);
                if ($derivedPath === '.') {
                    $path = './';
                } else if ($derivedPath === '/') { // Für Root-Pfade auf manchen Systemen
                    $path = './';
                } else {
                    $path = $derivedPath . '/';
                }
                if ($debugMode)
                    error_log("DEBUG: 'path' aus 'loc' abgeleitet: " . $path);
            }
        }

        // Sicherstellen, dass 'loc' und 'name' nicht leer sind, bevor hinzugefügt wird
        if (!empty($loc) && !empty($name)) {
            $sanitizedPage = [
                'loc' => $loc,
                'name' => $name,
                'path' => $path,
                'priority' => $priority,
                'changefreq' => $changefreq,
            ];
            $sanitizedPages[] = $sanitizedPage;
            if ($debugMode)
                error_log("DEBUG: Bereinigte Seite hinzugefügt: " . $loc);
        } else {
            error_log("WARNUNG: loadSitemapData: Eintrag nach Bereinigung immer noch unvollständig (loc oder name leer): " . print_r($sanitizedPage, true));
            if ($debugMode)
                error_log("DEBUG: Eintrag nach Bereinigung immer noch unvollständig.");
        }
    }
    if ($debugMode)
        error_log("DEBUG: " . count($sanitizedPages) . " Seiten nach Bereinigung.");
    return ['pages' => $sanitizedPages];
}

/**
 * Speichert Sitemap-Daten in die JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @param bool $debugMode Debug-Modus Flag.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveSitemapData(string $path, array $data, bool $debugMode): bool
{
    if ($debugMode)
        error_log("DEBUG: saveSitemapData() aufgerufen für: " . basename($path));
    if (isset($data['pages']) && is_array($data['pages'])) {
        usort($data['pages'], function ($a, $b) {
            // Sicherstellen, dass 'loc' existiert, bevor strcmp aufgerufen wird
            $locA = isset($a['loc']) ? $a['loc'] : '';
            $locB = isset($b['loc']) ? $b['loc'] : '';
            return strcmp($locA, $locB);
        });
        if ($debugMode)
            error_log("DEBUG: Seiten nach 'loc' sortiert.");
    }

    // Sicherstellen, dass nur die relevanten Felder für die JSON-Ausgabe enthalten sind
    $outputPages = [];
    foreach ($data['pages'] as $page) {
        $outputPages[] = [
            'loc' => $page['loc'],
            'name' => $page['name'], // 'name' Feld beibehalten
            'path' => $page['path'], // 'path' Feld beibehalten
            'priority' => $page['priority'],
            'changefreq' => $page['changefreq'],
        ];
    }
    $data['pages'] = $outputPages; // Überschreibe mit den bereinigten Daten
    if ($debugMode)
        error_log("DEBUG: Daten für Speicherung bereinigt.");

    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        error_log("FEHLER: saveSitemapData: Fehler beim Kodieren von Sitemap-Daten: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Kodieren von Sitemap-Daten: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) === false) {
        error_log("FEHLER: saveSitemapData: Fehler beim Schreiben der Sitemap-Daten nach " . $path);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Schreiben der Sitemap-Daten nach " . $path);
        return false;
    }
    if ($debugMode)
        error_log("DEBUG: Sitemap-Daten erfolgreich gespeichert.");
    return true;
}

/**
 * Scannt das Comic-Verzeichnis nach PHP-Dateien im YYYYMMDD.php Format.
 * @param string $dirPath Der Pfad zum Comic-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste von Dateinamen (z.B. '20250724.php'), alphabetisch sortiert.
 */
function scanComicDirectory(string $dirPath, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: scanComicDirectory() aufgerufen für: " . $dirPath);
    $comicFiles = [];
    if (!is_dir($dirPath)) {
        error_log("WARNUNG: scanComicDirectory: Comic-Verzeichnis nicht gefunden: " . $dirPath);
        if ($debugMode)
            error_log("DEBUG: Comic-Verzeichnis nicht gefunden: " . $dirPath);
        return [];
    }
    $files = scandir($dirPath);
    if ($files === false) {
        if ($debugMode)
            error_log("DEBUG: scandir() fehlgeschlagen für " . $dirPath);
        return [];
    }
    foreach ($files as $file) {
        // Ignoriere . und ..
        if ($file === '.' || $file === '..') {
            continue;
        }
        // Ignoriere comic/index.php
        if ($file === 'index.php') {
            if ($debugMode)
                error_log("DEBUG: comic/index.php übersprungen.");
            continue;
        }
        // Prüfe auf YYYYMMDD.php Format
        if (preg_match('/^\d{8}\.php$/', $file)) {
            $comicFiles[] = $file;
            if ($debugMode)
                error_log("DEBUG: Comic-Datei gefunden: " . $file);
        } else {
            if ($debugMode)
                error_log("DEBUG: Datei übersprungen (kein YYYYMMDD.php Format): " . $file);
        }
    }
    sort($comicFiles); // Alphabetisch sortieren
    if ($debugMode)
        error_log("DEBUG: " . count($comicFiles) . " Comic-Dateien im Verzeichnis gefunden und sortiert.");
    return $comicFiles;
}

// Verarbeite POST-Anfragen zum Speichern (AJAX-Handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    if ($debugMode)
        error_log("DEBUG: POST-Anfrage mit application/json Content-Type erkannt.");
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Dekodieren der JSON-Daten: ' . json_last_error_msg()]);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren der empfangenen JSON-Daten: " . json_last_error_msg());
        exit;
    }
    if ($debugMode)
        error_log("DEBUG: JSON-Daten erfolgreich empfangen und dekodiert.");

    $allPagesToSave = [];

    // Verarbeite Daten aus dem Frontend
    if (isset($requestData['pages']) && is_array($requestData['pages'])) {
        foreach ($requestData['pages'] as $page) {
            // Alle Felder kommen jetzt vom Frontend
            $loc = isset($page['loc']) ? trim($page['loc']) : '';
            $name = isset($page['name']) ? trim($page['name']) : '';
            $path = isset($page['path']) ? trim($page['path']) : '';
            $priority = isset($page['priority']) ? (float) $page['priority'] : 0.5;
            $changefreq = isset($page['changefreq']) ? trim($page['changefreq']) : 'weekly';

            // Validierung: loc und name dürfen nicht leer sein
            if (empty($loc) || empty($name)) {
                error_log("WARNUNG: Speichern: Eintrag mit leerem 'loc' oder 'name' übersprungen: " . print_r($page, true));
                if ($debugMode)
                    error_log("DEBUG: Eintrag mit leerem 'loc' oder 'name' übersprungen beim Speichern.");
                continue; // Zeile ignorieren, wenn loc oder name leer ist
            }

            $allPagesToSave[] = [
                'loc' => $loc,
                'name' => $name,
                'path' => $path,
                'priority' => $priority,
                'changefreq' => $changefreq,
            ];
            if ($debugMode)
                error_log("DEBUG: Seite für Speicherung vorbereitet: " . $loc);
        }
    }
    if ($debugMode)
        error_log("DEBUG: " . count($allPagesToSave) . " Seiten zum Speichern gesammelt.");

    if (saveSitemapData($sitemapJsonPath, ['pages' => $allPagesToSave], $debugMode)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Sitemap-Daten erfolgreich gespeichert!']);
        if ($debugMode)
            error_log("DEBUG: Sitemap-Daten erfolgreich gespeichert (AJAX-Antwort).");
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Speichern der Sitemap-Daten.']);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Speichern der Sitemap-Daten (AJAX-Antwort).");
        exit;
    }
}

// Lade bestehende Sitemap-Daten
$sitemapData = loadSitemapData($sitemapJsonPath, $debugMode);
$existingPages = $sitemapData['pages'];
if ($debugMode)
    error_log("DEBUG: " . count($existingPages) . " bestehende Seiten aus Sitemap geladen.");

$generalPages = [];
$comicPages = [];

// Separiere bestehende Einträge in General und Comic basierend auf dem 'path'-Feld
foreach ($existingPages as $page) {
    // Sicherstellen, dass 'path' existiert und vergleichen, Standard ist './'
    $pagePath = isset($page['path']) ? $page['path'] : './';

    if ($pagePath === './comic/') {
        // Verwende 'loc' als Schlüssel für einfachen Zugriff und um Duplikate zu vermeiden
        $comicPages[$page['loc']] = $page;
        if ($debugMode)
            error_log("DEBUG: Seite als Comic-Seite erkannt: " . $page['loc']);
    } else {
        $generalPages[] = $page;
        if ($debugMode)
            error_log("DEBUG: Seite als allgemeine Seite erkannt: " . $page['loc']);
    }
}
if ($debugMode) {
    error_log("DEBUG: " . count($generalPages) . " allgemeine Seiten und " . count($comicPages) . " Comic-Seiten separiert.");
}

// Scanne das Comic-Verzeichnis nach neuen PHP-Dateien
$foundComicFiles = scanComicDirectory($comicDirPath, $debugMode);
if ($debugMode)
    error_log("DEBUG: " . count($foundComicFiles) . " Comic-Dateien im Verzeichnis gefunden.");

// Füge fehlende Comic-Dateien hinzu oder aktualisiere bestehende
foreach ($foundComicFiles as $filename) {
    $loc = './comic/' . $filename;
    // Wenn der Eintrag noch nicht in comicPages ist, füge ihn mit Standardwerten hinzu
    if (!isset($comicPages[$loc])) {
        $comicPages[$loc] = [
            'loc' => $loc, // Vollständiger Pfad
            'name' => $filename, // Name hinzufügen
            'path' => './comic/',
            'priority' => 0.8,
            'changefreq' => 'never',
        ];
        if ($debugMode)
            error_log("DEBUG: Neue Comic-Seite aus Verzeichnis hinzugefügt: " . $loc);
    }
    // Wenn er schon existiert, bleiben die manuell gesetzten Werte bestehen (Überschreiben durch manuelle Einträge).
    // Es ist hier kein 'else' nötig, da die vorhandenen Einträge bereits in $comicPages sind.
}

// Sortiere comicPages alphabetisch nach 'loc' (was dem Dateinamen entspricht, da path gleich ist)
ksort($comicPages);
if ($debugMode)
    error_log("DEBUG: Comic-Seiten nach 'loc' sortiert.");

// --- Paginierungslogik für Comic-Tabelle ---
$comicCurrentPage = isset($_GET['comic_page']) ? (int) $_GET['comic_page'] : 1;
if ($comicCurrentPage < 1)
    $comicCurrentPage = 1;
if ($debugMode)
    error_log("DEBUG: Aktuelle Comic-Seite (Paginierung): " . $comicCurrentPage);

$totalComicItems = count($comicPages);
$totalComicPages = ceil($totalComicItems / COMIC_PAGES_PER_PAGE);
if ($debugMode) {
    error_log("DEBUG: Gesamtanzahl Comic-Items: " . $totalComicItems);
    error_log("DEBUG: Gesamtanzahl Comic-Seiten (Paginierung): " . $totalComicPages);
}

// Sicherstellen, dass die aktuelle Seite nicht außerhalb des Bereichs liegt
if ($totalComicPages > 0 && $comicCurrentPage > $totalComicPages) {
    $comicCurrentPage = $totalComicPages;
    if ($debugMode)
        error_log("DEBUG: Aktuelle Comic-Seite angepasst auf max. Seite: " . $comicCurrentPage);
} elseif ($totalComicItems === 0) { // Wenn keine Items vorhanden sind, gibt es auch nur 1 leere Seite
    $comicCurrentPage = 1;
    $totalComicPages = 1;
    if ($debugMode)
        error_log("DEBUG: Keine Comic-Items, Paginierung auf Seite 1 gesetzt.");
}

$comicOffset = ($comicCurrentPage - 1) * COMIC_PAGES_PER_PAGE;
// array_slice behält hier die Keys, damit wir im Loop den ursprünglichen 'loc' Schlüssel verwenden können
$paginatedComicPages = array_slice($comicPages, $comicOffset, COMIC_PAGES_PER_PAGE, true);
if ($debugMode)
    error_log("DEBUG: " . count($paginatedComicPages) . " Comic-Seiten für aktuelle Paginierungsseite.");


// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in data_editor_sitemap.php eingebunden.");
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<!-- Font Awesome für Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    /* Allgemeine Layout-Anpassungen (minimal, falls nicht in main.css) */
    .admin-container {
        padding: 20px;
        max-width: 1200px;
        margin: 20px auto;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        background-color: #f9f9f9;
        /* Standardwert, wird von main.css überschrieben, wenn dort definiert */
    }

    body.theme-night .admin-container {
        background-color: #00425c;
        /* Von main_dark.css überschrieben */
        color: #fff;
    }

    /* H1 Style für den Admin-Container */
    .admin-container h1 {
        font-size: 2em;
        /* Explizite Schriftgröße */
        margin-bottom: 20px;
        /* Expliziter unterer Rand */
        font-weight: bold;
        /* Sicherstellen, dass es fett ist */
        color: #333;
        /* Standardfarbe */
    }

    body.theme-night .admin-container h1 {
        color: #fff;
        /* Dunkles Theme Farbe */
    }

    /* Message Box */
    #message-box {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
        display: none;
        /* Standardmäßig ausgeblendet */
    }

    #message-box.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    #message-box.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    body.theme-night #message-box.success {
        background-color: #218838;
        color: #fff;
        border-color: #1c7430;
    }

    body.theme-night #message-box.error {
        background-color: #c82333;
        color: #fff;
        border-color: #bd2130;
    }

    /* Collapsible Sections */
    .collapsible-section {
        margin-bottom: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    body.theme-night .collapsible-section {
        background-color: #00425c;
    }

    .collapsible-header {
        cursor: pointer;
        padding: 15px 20px;
        background-color: #f2f2f2;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.5em;
        font-weight: bold;
        color: #333;
    }

    body.theme-night .collapsible-header {
        background-color: #005a7e;
        color: #fff;
        border-bottom-color: #007bb5;
    }

    .collapsible-header i {
        transition: transform 0.3s ease;
        margin-left: 10px;
    }

    .collapsible-section.expanded .collapsible-header i {
        transform: rotate(0deg);
    }

    .collapsible-section:not(.expanded) .collapsible-header i {
        transform: rotate(-90deg);
    }

    .collapsible-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        padding: 0 20px;
        display: block;
        /* Sicherstellen, dass es nicht 'display: none' ist, wenn es von max-height gesteuert wird */
    }

    .collapsible-section.expanded .collapsible-content {
        max-height: 4800px;
        /* Adjust as needed for content */
        padding-top: 20px;
        padding-bottom: 20px;
        display: block !important;
        /* Explizit sicherstellen, dass es sichtbar ist */
    }

    .collapsible-section:not(.expanded) {
        border-radius: 8px;
    }

    .collapsible-section:not(.expanded) .collapsible-header {
        border-radius: 8px;
        border-bottom: none;
    }

    .collapsible-section.expanded .collapsible-header {
        border-radius: 8px 8px 0 0;
    }

    /* Table Styles (Minimal, rely on main.css for general) */
    .sitemap-table-container,
    .comic-table-container {
        overflow-x: auto;
    }

    .sitemap-table,
    .comic-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        /* Borders and background are largely defined in main.css for tables.
           We'll add specific overrides or ensure default behavior. */
    }

    /* Specific overrides for editable fields */
    .sitemap-table td .editable-field,
    .comic-table td .editable-field {
        width: 100%;
        padding: 5px;
        border: 1px solid #eee;
        /* Default light border */
        border-radius: 3px;
        box-sizing: border-box;
        background-color: transparent;
        cursor: pointer;
        min-height: 30px;
        display: block;
        color: inherit;
        /* Inherit text color from parent */
    }

    body.theme-night .sitemap-table td .editable-field,
    body.theme-night .comic-table td .editable-field {
        border-color: #005a7e;
        /* Dark theme border */
    }

    .sitemap-table td .editable-field:hover,
    .comic-table td .editable-field:hover {
        border-color: #ccc;
        /* Hover border */
    }

    body.theme-night .sitemap-table td .editable-field:hover,
    body.theme-night .comic-table td .editable-field:hover {
        border-color: #007bb5;
        /* Dark theme hover border */
    }

    .sitemap-table td .editable-field.editing,
    .comic-table td .editable-field.editing {
        border-color: #007bff;
        /* Editing border */
        background-color: #fff;
        /* Editing background */
        cursor: text;
    }

    body.theme-night .sitemap-table td .editable-field.editing,
    body.theme-night .comic-table td .editable-field.editing {
        background-color: #005a7e;
        /* Dark theme editing background */
        border-color: #007bff;
    }

    /* Button Group */
    .button-group {
        text-align: right;
        margin-top: 20px;
    }

    /* Buttons (rely heavily on main.css for general button styles) */
    /* Add specific icon spacing */
    button i.fas {
        margin-right: 5px;
    }

    /* Paginierung Styles */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        gap: 5px;
        flex-wrap: wrap;
        /* NEU: Ermöglicht Umbruch der Buttons */
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #007bff;
        background-color: #fff;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .pagination a:hover {
        background-color: #e9ecef;
        color: #0056b3;
    }

    .pagination span.current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        font-weight: bold;
        cursor: default;
    }

    body.theme-night .pagination a,
    body.theme-night .pagination span {
        border-color: #005a7e;
        color: #7bbdff;
        background-color: #00425c;
    }

    body.theme-night .pagination a:hover {
        background-color: #006690;
        color: #a0d4ff;
    }

    body.theme-night .pagination span.current-page {
        background-color: #2a6177;
        color: white;
        border-color: #2a6177;
    }

    /* Hint text for path format */
    .path-hint {
        font-size: 0.9em;
        color: #666;
        margin-top: -10px;
        /* Adjust to position correctly */
        margin-bottom: 10px;
        text-align: left;
    }

    body.theme-night .path-hint {
        color: #bbb;
    }

    /* Styles for select-field to make it work as a native dropdown */
    .editable-field.select-field {
        position: relative;
        padding: 0;
        display: flex;
        align-items: center;
        /* Remove ::after custom arrow if it was here */
    }

    .editable-field.select-field .changefreq-select {
        width: 100%;
        height: 100%;
        cursor: pointer;
        border: 1px solid #eee;
        /* Default light border */
        border-radius: 3px;
        font-size: inherit;
        color: inherit;
        padding: 5px;
        box-sizing: border-box;
        /* Re-enable native dropdown arrow */
        -webkit-appearance: menulist;
        -moz-appearance: menulist;
        appearance: menulist;
        background-color: #fff;
        /* Default light theme background */
    }

    body.theme-night .editable-field.select-field .changefreq-select {
        background-color: #005a7e;
        /* Dark theme background */
        border-color: #007bb5;
        /* Dark theme border */
        color: #fff;
        /* Dark theme text color */
    }

    /* Remove .select-display-text and its styles */
    /* Remove .editable-field.select-field::after styles */
</style>

<div class="admin-container">
    <h1><?php echo htmlspecialchars($pageHeader); ?></h1>
    <div id="message-box" class="message-box"></div>

    <section class="collapsible-section expanded" id="general-sitemap-section">
        <div class="collapsible-header">
            <h2>Allgemeine Sitemap-Seiten</h2>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapsible-content">
            <p class="path-hint">Pfade sollten relativ zum Hauptverzeichnis beginnen, z.B.
                <code>./meine-seite.php</code> oder <code>./ordner/datei.html</code>. Ein fehlendes <code>./</code> wird
                automatisch ergänzt.
            </p>
            <div class="sitemap-table-container">
                <table class="sitemap-table" id="sitemap-table">
                    <thead>
                        <tr>
                            <th title="Hacken = Datei ist vorhanden; Kreuz = Datei fehlt">&#10003; / &#10007;</th>
                            <th title="Dateipfad, relativ zum Hauptverzeichnis ./">Pfad/Name (Loc)</th>
                            <th title="Priorität (1.0=hoch; 0.5=normal; 0.1=niedrig)">Priorität</th>
                            <th title="Änderungsfrequenz der Datei">Änderungsfrequenz</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($generalPages)): ?>
                            <tr class="no-data-row">
                                <td colspan="5" style="text-align: center;">Keine allgemeinen Sitemap-Einträge vorhanden.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($generalPages as $index => $page): ?>
                                <tr data-original-loc="<?php echo htmlspecialchars($page['loc']); ?>"
                                    data-path="<?php echo htmlspecialchars($page['path']); ?>"
                                    data-name="<?php echo htmlspecialchars($page['name']); ?>">
                                    <td>
                                        <?php
                                        // Prüfe, ob die Datei existiert
                                        // $webRootPath ist bereits der absolute Pfad zum Hauptverzeichnis
                                        $fileToCheck = $webRootPath . '/' . ltrim($page['loc'], './');
                                        if ($debugMode)
                                            error_log("DEBUG: Prüfe Existenz von Datei: " . $fileToCheck);

                                        if (file_exists($fileToCheck) && is_file($fileToCheck)) {
                                            echo '<i class="fas fa-check-circle" style="color: green;"></i>';
                                            if ($debugMode)
                                                error_log("DEBUG: Datei existiert: " . $fileToCheck);
                                        } else {
                                            echo '<i class="fas fa-times-circle" style="color: red;"></i>';
                                            if ($debugMode)
                                                error_log("DEBUG: Datei fehlt: " . $fileToCheck);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="editable-field" data-field="loc" contenteditable="true">
                                            <?php echo htmlspecialchars($page['loc']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="editable-field" data-field="priority" contenteditable="true">
                                            <?php echo htmlspecialchars($page['priority']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="editable-field select-field">
                                            <select class="changefreq-select" data-field="changefreq">
                                                <option value="always" <?php echo ($page['changefreq'] == 'always') ? 'selected' : ''; ?>>always</option>
                                                <option value="hourly" <?php echo ($page['changefreq'] == 'hourly') ? 'selected' : ''; ?>>hourly</option>
                                                <option value="daily" <?php echo ($page['changefreq'] == 'daily') ? 'selected' : ''; ?>>daily</option>
                                                <option value="weekly" <?php echo ($page['changefreq'] == 'weekly') ? 'selected' : ''; ?>>weekly</option>
                                                <option value="monthly" <?php echo ($page['changefreq'] == 'monthly') ? 'selected' : ''; ?>>monthly</option>
                                                <option value="yearly" <?php echo ($page['changefreq'] == 'yearly') ? 'selected' : ''; ?>>yearly</option>
                                                <option value="never" <?php echo ($page['changefreq'] == 'never') ? 'selected' : ''; ?>>never</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="button delete-row-btn"><i class="fas fa-trash-alt"></i> Löschen</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button class="button add-row-btn"><i class="fas fa-plus-circle"></i> Zeile hinzufügen</button>
        </div>
    </section>

    <section class="collapsible-section expanded" id="comic-sitemap-section">
        <div class="collapsible-header">
            <h2>Comic-Sitemap-Seiten</h2>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapsible-content">
            <div class="comic-table-container">
                <table class="comic-table" id="comic-table">
                    <thead>
                        <tr>
                            <th title="Dateipfad, relativ zum Hauptverzeichnis ./comic/">Pfad/Name (Loc)</th>
                            <th title="Priorität (1.0=hoch; 0.5=normal; 0.1=niedrig)">Priorität</th>
                            <th title="Änderungsfrequenz der Datei">Änderungsfrequenz</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paginatedComicPages)): ?>
                            <tr class="no-data-row">
                                <td colspan="4" style="text-align: center;">Keine Comic-Sitemap-Einträge vorhanden.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginatedComicPages as $loc => $page): ?>
                                <?php
                                // Zeige nur den Dateinamen im Feld an
                                $filename = basename($page['loc']);
                                ?>
                                <tr data-original-loc="<?php echo htmlspecialchars($loc); ?>" data-path="./comic/"
                                    data-name="<?php echo htmlspecialchars($filename); ?>">
                                    <td>
                                        <div class="editable-field" data-field="loc" contenteditable="true">
                                            <?php echo htmlspecialchars($filename); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="editable-field" data-field="priority" contenteditable="true">
                                            <?php echo htmlspecialchars($page['priority']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="editable-field select-field">
                                            <select class="changefreq-select" data-field="changefreq">
                                                <option value="always" <?php echo ($page['changefreq'] == 'always') ? 'selected' : ''; ?>>always</option>
                                                <option value="hourly" <?php echo ($page['changefreq'] == 'hourly') ? 'selected' : ''; ?>>hourly</option>
                                                <option value="daily" <?php echo ($page['changefreq'] == 'daily') ? 'selected' : ''; ?>>daily</option>
                                                <option value="weekly" <?php echo ($page['changefreq'] == 'weekly') ? 'selected' : ''; ?>>weekly</option>
                                                <option value="monthly" <?php echo ($page['changefreq'] == 'monthly') ? 'selected' : ''; ?>>monthly</option>
                                                <option value="yearly" <?php echo ($page['changefreq'] == 'yearly') ? 'selected' : ''; ?>>yearly</option>
                                                <option value="never" <?php echo ($page['changefreq'] == 'never') ? 'selected' : ''; ?>>never</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="button delete-row-btn"><i class="fas fa-trash-alt"></i> Löschen</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginierung für Comic-Tabelle -->
            <div class="pagination">
                <?php if ($totalComicPages > 1): ?>
                    <?php if ($comicCurrentPage > 1): ?>
                        <a href="?comic_page=<?php echo $comicCurrentPage - 1; ?>">&laquo; Vorherige</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalComicPages; $i++): ?>
                        <a href="?comic_page=<?php echo $i; ?>"
                            class="<?php echo ($i == $comicCurrentPage) ? 'current-page' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($comicCurrentPage < $totalComicPages): ?>
                        <a href="?comic_page=<?php echo $comicCurrentPage + 1; ?>">Nächste &raquo;</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="button-group">
        <button id="save-all-btn"><i class="fas fa-save"></i> Änderungen speichern</button>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        const messageBox = $('#message-box');

        function showMessage(message, type) {
            messageBox.removeClass('success error').addClass(type).text(message).fadeIn();
            setTimeout(() => {
                messageBox.fadeOut(() => messageBox.text(''));
            }, 5000);
        }

        // Event Listener für collapsible sections
        document.querySelectorAll('.collapsible-header').forEach(header => {
            const section = header.closest('.collapsible-section');
            const icon = header.querySelector('i');

            // Initial icon state
            if (section.classList.contains('expanded')) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }

            header.addEventListener('click', function () {
                section.classList.toggle('expanded');
                if (section.classList.contains('expanded')) {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                } else {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
            });
        });

        // --- Globale Datenhaltung für alle Sitemap-Einträge ---
        // Initialisierung der Daten aus PHP
        const initialGeneralPages = <?php echo json_encode($generalPages, JSON_UNESCAPED_SLASHES); ?>;
        // Achtung: $comicPages ist ein assoziatives Array (PHP), json_encode macht es zu einem Objekt.
        // Für JS-Array-Methoden besser zu einem numerisch indizierten Array konvertieren.
        const initialComicPages = <?php echo json_encode(array_values($comicPages), JSON_UNESCAPED_SLASHES); ?>;

        let allSitemapPages = [...initialGeneralPages, ...initialComicPages];
        // Map für schnellen Zugriff und Aktualisierung nach 'loc'
        const sitemapMap = new Map();
        allSitemapPages.forEach(page => sitemapMap.set(page.loc, page));

        // Funktion zur Normalisierung des Pfad-Inputs (fügt './' hinzu, falls fehlt)
        function normalizePathInput(value) {
            let processedValue = value.trim();
            // Wenn es mit einem Laufwerksbuchstaben (z.B. C:/) beginnt, als absoluten Pfad annehmen und nicht ändern
            if (/^[a-zA-Z]:[\\\/]/.test(processedValue)) {
                return processedValue;
            }
            // Wenn es mit '/' beginnt, in './' umwandeln
            if (processedValue.startsWith('/')) {
                processedValue = '.' + processedValue;
            }
            // Wenn es nicht mit './' beginnt, './' voranstellen
            else if (!processedValue.startsWith('./')) {
                processedValue = './' + processedValue;
            }
            return processedValue;
        }

        // Event Delegation für bearbeitbare Felder (beide Tabellen)
        $(document).on('click', '.editable-field:not(.select-field)', function () {
            if (!$(this).hasClass('editing')) {
                $(this).addClass('editing').focus();
                $(this).data('original-value', $(this).text());
            }
        });

        // Spezielle Logik für 'loc' Felder in der allgemeinen Sitemap-Tabelle (inkl. Normalisierung und Map-Update)
        $(document).on('blur', '#sitemap-table .editable-field[data-field="loc"]', function () {
            const $this = $(this);
            const $row = $this.closest('tr');
            const oldLoc = $row.data('original-loc');
            const newLocRaw = $this.text().trim();
            const newLoc = normalizePathInput(newLocRaw);

            if (newLoc !== oldLoc) {
                // Angezeigten Text aktualisieren
                $this.text(newLoc);

                // Daten im Map aktualisieren
                if (sitemapMap.has(oldLoc)) {
                    const pageData = sitemapMap.get(oldLoc);
                    sitemapMap.delete(oldLoc); // Alten Eintrag entfernen

                    pageData.loc = newLoc; // 'loc' Eigenschaft aktualisieren
                    pageData.name = newLoc.substring(newLoc.lastIndexOf('/') + 1); // 'name' ableiten
                    let path = newLoc.substring(0, newLoc.lastIndexOf('/') + 1);
                    if (path === '') path = './';
                    pageData.path = path; // 'path' ableiten

                    sitemapMap.set(newLoc, pageData); // Neuen Eintrag mit neuem Schlüssel hinzufügen
                    $row.data('original-loc', newLoc); // data-Attribut der Zeile aktualisieren
                    $row.data('name', pageData.name); // data-name aktualisieren
                    $row.data('path', pageData.path); // data-path aktualisieren
                }
            }
            $this.removeClass('editing');
        });

        // Logik für 'loc' Felder in der Comic-Sitemap-Tabelle (nur Dateiname, kein './' Präfix)
        $(document).on('blur', '#comic-table .editable-field[data-field="loc"]', function () {
            const $this = $(this);
            const $row = $this.closest('tr');
            const oldLoc = $row.data('original-loc'); // Dies ist der volle Pfad ./comic/YYYYMMDD.php
            const newFilename = $this.text().trim(); // Der Benutzer bearbeitet nur den Dateinamen

            // Den vollständigen neuen Loc-Pfad zusammensetzen
            const newLoc = './comic/' + newFilename;

            if (newLoc !== oldLoc) {
                // Angezeigten Text aktualisieren (sollte schon der neue Dateiname sein)
                $this.text(newFilename);

                // Daten im Map aktualisieren
                if (sitemapMap.has(oldLoc)) {
                    const pageData = sitemapMap.get(oldLoc);
                    sitemapMap.delete(oldLoc); // Alten Eintrag entfernen

                    pageData.loc = newLoc; // 'loc' Eigenschaft aktualisieren
                    pageData.name = newFilename; // 'name' ist der Dateiname
                    pageData.path = './comic/'; // 'path' bleibt fest

                    sitemapMap.set(newLoc, pageData); // Neuen Eintrag mit neuem Schlüssel hinzufügen
                    $row.data('original-loc', newLoc); // data-Attribut der Zeile aktualisieren
                    $row.data('name', pageData.name); // data-name aktualisieren
                }
            }
            $this.removeClass('editing');
        });


        // Für andere bearbeitbare Felder (nicht 'loc' und nicht Select)
        $(document).on('blur', '.editable-field:not(.select-field):not([data-field="loc"])', function () {
            const $this = $(this);
            const $row = $this.closest('tr');
            const loc = $row.data('original-loc'); // Schlüssel für den Map-Eintrag
            const field = $this.data('field');
            let value = $this.text().trim();

            if (field === 'priority') {
                value = parseFloat(value) || 0.5;
                $this.text(value); // Sicherstellen, dass der angezeigte Wert numerisch ist
            }

            if (sitemapMap.has(loc)) {
                sitemapMap.get(loc)[field] = value;
            }
            $this.removeClass('editing');
        });

        // Event Delegation für Select-Felder (beide Tabellen)
        // Das 'change'-Event des nativen Select-Elements wird direkt abgefangen
        $(document).on('change', '.editable-field.select-field .changefreq-select', function () {
            const $selectChanged = $(this);
            const $divParent = $selectChanged.closest('.editable-field.select-field');
            const $row = $selectChanged.closest('tr');
            const loc = $row.data('original-loc');
            const field = $selectChanged.data('field'); // data-field ist jetzt direkt auf dem select

            const value = $selectChanged.val();

            // Update the displayed text in the select (which is now directly visible)
            // No need to update a separate span, as the select itself is visible

            if (sitemapMap.has(loc)) {
                sitemapMap.get(loc)[field] = value;
            }
        });


        // Zeile zur allgemeinen Sitemap-Tabelle hinzufügen
        $('.add-row-btn').on('click', function () {
            const tableBody = $('#sitemap-table tbody');
            const noDataRow = tableBody.find('.no-data-row');
            if (noDataRow.length) {
                noDataRow.remove();
            }
            // Temporäre eindeutige ID für neue Zeile generieren
            const newTempLoc = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const newPage = {
                loc: newTempLoc, // Temporärer loc
                name: '',
                path: './',
                priority: 0.5,
                changefreq: 'weekly'
            };
            sitemapMap.set(newPage.loc, newPage); // Zum Map hinzufügen
            allSitemapPages = Array.from(sitemapMap.values()); // Array neu aufbauen

            const newRow = `
            <tr data-original-loc="${newPage.loc}" data-path="${newPage.path}" data-name="${newPage.name}">
                <td><i class="fas fa-question-circle" style="color: gray;"></i></td>
                <td><div class="editable-field" data-field="loc" contenteditable="true"></div></td>
                <td><div class="editable-field" data-field="priority" contenteditable="true">${newPage.priority}</div></td>
                <td>
                    <div class="editable-field select-field">
                        <select class="changefreq-select" data-field="changefreq">
                            <option value="always">always</option>
                            <option value="hourly">hourly</option>
                            <option value="daily">daily</option>
                            <option value="weekly" selected>weekly</option>
                            <option value="monthly">monthly</option>
                            <option value="yearly">yearly</option>
                            <option value="never">never</option>
                        </select>
                    </div>
                </td>
                <td>
                    <button class="button delete-row-btn"><i class="fas fa-trash-alt"></i> Löschen</button>
                </td>
            </tr>
        `;
            tableBody.append(newRow);
            // Fokus auf das neue 'loc'-Feld setzen
            tableBody.find('tr:last .editable-field[data-field="loc"]').click();
        });

        // Zeile aus einer der Tabellen löschen
        $(document).on('click', '.delete-row-btn', function () {
            const row = $(this).closest('tr');
            const locToDelete = row.data('original-loc');

            sitemapMap.delete(locToDelete); // Aus dem Daten-Speicher entfernen
            allSitemapPages = Array.from(sitemapMap.values()); // Array neu aufbauen

            row.remove(); // Aus dem DOM entfernen

            // Prüfen, ob die Tabelle leer ist und ggf. 'no-data-row' wieder hinzufügen
            if ($('#sitemap-table tbody tr').length === 0) {
                $('#sitemap-table tbody').append('<tr class="no-data-row"><td colspan="5" style="text-align: center;">Keine allgemeinen Sitemap-Einträge vorhanden.</td></tr>');
            }
            if ($('#comic-table tbody tr').length === 0) {
                $('#comic-table tbody').append('<tr class="no-data-row"><td colspan="4" style="text-align: center;">Keine Comic-Sitemap-Einträge vorhanden.</td></tr>');
            }
        });

        // Alle Änderungen speichern
        $('#save-all-btn').on('click', function () {
            // Filtere temporäre Einträge, die nicht bearbeitet wurden (loc ist immer noch 'temp_...')
            const pagesToSave = Array.from(sitemapMap.values()).filter(page => !page.loc.startsWith('temp_') || page.name !== '');

            $.ajax({
                url: window.location.href, // An dasselbe PHP-Skript senden
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ pages: pagesToSave }), // Alle gesammelten Daten senden
                success: function (response) {
                    if (response.status === 'success') {
                        showMessage(response.message, 'success');
                        // Seite neu laden, um gespeicherte Änderungen und neue temporäre IDs zu reflektieren
                        window.location.reload();
                    } else {
                        showMessage(response.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    showMessage('Ein Fehler ist aufgetreten: ' + error, 'error');
                }
            });
        });

    });
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in data_editor_sitemap.php eingebunden.");
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>