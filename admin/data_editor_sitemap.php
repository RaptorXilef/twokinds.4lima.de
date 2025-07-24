<?php
/**
 * Dies ist die Administrationsseite zum Bearbeiten der sitemap.json Konfigurationsdatei.
 * Sie ermöglicht das Hinzufügen, Bearbeiten und Löschen von Sitemap-Einträgen
 * über eine benutzerfreundliche Oberfläche.
 *
 * Zusätzlich werden fehlende PHP-Dateien aus dem 'comic/'-Verzeichnis automatisch hinzugefügt
 * und die Gesamt-Sitemap wird gespeichert.
 */

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
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
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$sitemapJsonPath = __DIR__ . '/../src/components/sitemap.json';
// Pfad zum Comic-Verzeichnis ist von Hauptverzeichnis/admin/ -> Hauptverzeichnis/comic/
$webRootPath = realpath(__DIR__ . '/../'); // Der tatsächliche Webroot
$comicDirPath = $webRootPath . '/comic/'; // Absoluter Pfad zum Comic-Verzeichnis

// Konstante für die Anzahl der Elemente pro Seite für die Comic-Tabelle
if (!defined('COMIC_PAGES_PER_PAGE')) {
    define('COMIC_PAGES_PER_PAGE', 50);
}

// Setze Parameter für den Header.
$pageTitle = 'Sitemap Editor';
$pageHeader = 'Sitemap Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen

$message = '';
$messageType = ''; // 'success' or 'error'

/**
 * Lädt Sitemap-Daten aus einer JSON-Datei und stellt sicher, dass alle notwendigen Schlüssel vorhanden sind.
 * Wenn 'loc' fehlt, wird es aus 'path' und 'name' zusammengesetzt.
 * @param string $path Der Pfad zur JSON-Datei.
 * @return array Die dekodierten Daten als assoziatives Array oder ein leeres Array im Fehlerfall.
 */
function loadSitemapData(string $path): array
{
    if (!file_exists($path)) {
        return ['pages' => []];
    }
    $content = file_get_contents($path);

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("FEHLER: loadSitemapData: Fehler beim Dekodieren von sitemap.json: " . json_last_error_msg());
        return ['pages' => []];
    }

    $pages = isset($data['pages']) && is_array($data['pages']) ? $data['pages'] : [];
    $sanitizedPages = [];

    foreach ($pages as $page) {
        // Sicherstellen, dass alle erwarteten Schlüssel vorhanden sind, mit Standardwerten
        $loc = isset($page['loc']) ? (string) $page['loc'] : '';
        $path = isset($page['path']) ? (string) $page['path'] : './'; // Standardpfad
        $priority = isset($page['priority']) ? (float) $page['priority'] : 0.5;
        $changefreq = isset($page['changefreq']) ? (string) $page['changefreq'] : 'weekly';

        // NEU: Wenn 'loc' fehlt, aber 'name' und 'path' vorhanden sind, 'loc' zusammensetzen
        if (empty($loc) && isset($page['name']) && !empty($page['name'])) {
            $normalizedPath = rtrim($path, '/\\');
            if ($normalizedPath === '.') {
                $normalizedPath = ''; // Wenn nur '.', dann ist der Pfad direkt im Webroot
            } else {
                $normalizedPath .= '/';
            }
            $loc = $normalizedPath . $page['name'];
        }

        $sanitizedPage = [
            'loc' => $loc,
            'path' => $path,
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];

        // Nur hinzufügen, wenn 'loc' nicht leer ist
        if (!empty($sanitizedPage['loc'])) {
            $sanitizedPages[] = $sanitizedPage;
        } else {
            // Dies sollte jetzt seltener passieren, da 'loc' zusammengesetzt wird
            error_log("WARNUNG: loadSitemapData: Eintrag mit leerem 'loc' übersprungen: " . print_r($page, true));
        }
    }
    return ['pages' => $sanitizedPages];
}

/**
 * Speichert Sitemap-Daten in die JSON-Datei.
 * @param string $path Der Pfad zur JSON-Datei.
 * @param array $data Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveSitemapData(string $path, array $data): bool
{
    if (isset($data['pages']) && is_array($data['pages'])) {
        usort($data['pages'], function ($a, $b) {
            // Sicherstellen, dass 'loc' existiert, bevor strcmp aufgerufen wird
            $locA = isset($a['loc']) ? $a['loc'] : '';
            $locB = isset($b['loc']) ? $b['loc'] : '';
            return strcmp($locA, $locB);
        });
    }

    $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        error_log("FEHLER: saveSitemapData: Fehler beim Kodieren von Sitemap-Daten: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) === false) {
        error_log("FEHLER: saveSitemapData: Fehler beim Schreiben der Sitemap-Daten nach " . $path);
        return false;
    }
    return true;
}

/**
 * Scannt das Comic-Verzeichnis nach PHP-Dateien im YYYYMMDD.php Format.
 * @param string $dirPath Der Pfad zum Comic-Verzeichnis.
 * @return array Eine Liste von Dateinamen (z.B. '20250724.php'), alphabetisch sortiert.
 */
function scanComicDirectory(string $dirPath): array
{
    $comicFiles = [];
    if (!is_dir($dirPath)) {
        error_log("WARNUNG: scanComicDirectory: Comic-Verzeichnis nicht gefunden: " . $dirPath);
        return [];
    }
    $files = scandir($dirPath);
    foreach ($files as $file) {
        // Ignoriere . und ..
        if ($file === '.' || $file === '..') {
            continue;
        }
        // Ignoriere comic/index.php
        if ($file === 'index.php') {
            continue;
        }
        // Prüfe auf YYYYMMDD.php Format
        if (preg_match('/^\d{8}\.php$/', $file)) {
            $comicFiles[] = $file;
        }
    }
    sort($comicFiles); // Alphabetisch sortieren
    return $comicFiles;
}

// Verarbeite POST-Anfragen zum Speichern (AJAX-Handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Dekodieren der JSON-Daten: ' . json_last_error_msg()]);
        exit;
    }

    $allPagesToSave = [];

    // Verarbeite Daten aus dem Frontend
    if (isset($requestData['pages']) && is_array($requestData['pages'])) {
        foreach ($requestData['pages'] as $page) {
            // "loc" ist der zentrale Identifier
            $loc = isset($page['loc']) ? trim($page['loc']) : '';
            $path = isset($page['path']) ? trim($page['path']) : ''; // Pfad wird aus Frontend mitgeliefert
            $priority = isset($page['priority']) ? (float) $page['priority'] : 0.5;
            $changefreq = isset($page['changefreq']) ? trim($page['changefreq']) : 'weekly';

            // Validierung: loc darf nicht leer sein
            if (empty($loc)) {
                error_log("WARNUNG: Speichern: Eintrag mit leerem 'loc' übersprungen: " . print_r($page, true));
                continue; // Zeile ignorieren, wenn loc leer ist
            }

            // Für Comic-Seiten, stelle sicher, dass der vollständige 'loc' korrekt ist,
            // auch wenn nur der Dateiname im Frontend angezeigt/bearbeitet wurde.
            // Die Frontend-Logik sollte sicherstellen, dass path für Comic-Seiten korrekt gesetzt ist.
            if ($path === './comic/' && !str_starts_with($loc, './comic/')) {
                // Wenn loc nur der Dateiname ist, prepend den comic-Pfad
                $loc = './comic/' . basename($loc); // Sicherstellen, dass nur der Dateiname verwendet wird
            }

            $allPagesToSave[] = [
                'loc' => $loc,
                'path' => $path,
                'priority' => $priority,
                'changefreq' => $changefreq,
            ];
        }
    }

    if (saveSitemapData($sitemapJsonPath, ['pages' => $allPagesToSave])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Sitemap-Daten erfolgreich gespeichert!']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Speichern der Sitemap-Daten.']);
        exit;
    }
}

// Lade bestehende Sitemap-Daten
$sitemapData = loadSitemapData($sitemapJsonPath);
$existingPages = $sitemapData['pages'];

$generalPages = [];
$comicPages = [];

// Separiere bestehende Einträge in General und Comic basierend auf dem 'path'-Feld
foreach ($existingPages as $page) {
    // Sicherstellen, dass 'path' existiert und vergleichen, Standard ist './'
    $pagePath = isset($page['path']) ? $page['path'] : './';

    if ($pagePath === './comic/') {
        // Verwende 'loc' als Schlüssel für einfachen Zugriff und um Duplikate zu vermeiden
        $comicPages[$page['loc']] = $page;
    } else {
        $generalPages[] = $page;
    }
}

// Scanne das Comic-Verzeichnis nach neuen PHP-Dateien
$foundComicFiles = scanComicDirectory($comicDirPath);

// Füge fehlende Comic-Dateien hinzu oder aktualisiere bestehende
foreach ($foundComicFiles as $filename) {
    $loc = './comic/' . $filename;
    // Wenn der Eintrag noch nicht in comicPages ist, füge ihn mit Standardwerten hinzu
    if (!isset($comicPages[$loc])) {
        $comicPages[$loc] = [
            'loc' => $loc, // Vollständiger Pfad
            'path' => './comic/',
            'priority' => 0.8,
            'changefreq' => 'never',
        ];
    }
    // Wenn er schon existiert, bleiben die manuell gesetzten Werte bestehen (Überschreiben durch manuelle Einträge).
    // Es ist hier kein 'else' nötig, da die vorhandenen Einträge bereits in $comicPages sind.
}

// Sortiere comicPages alphabetisch nach 'loc' (was dem Dateinamen entspricht, da path gleich ist)
// Dies wird beibehalten, da das Frontend keine eigene Sortierung mehr hat
ksort($comicPages);

// --- Paginierungslogik für Comic-Tabelle ---
$comicCurrentPage = isset($_GET['comic_page']) ? (int) $_GET['comic_page'] : 1;
if ($comicCurrentPage < 1)
    $comicCurrentPage = 1;

$totalComicItems = count($comicPages);
$totalComicPages = ceil($totalComicItems / COMIC_PAGES_PER_PAGE);

// Sicherstellen, dass die aktuelle Seite nicht außerhalb des Bereichs liegt
if ($totalComicPages > 0 && $comicCurrentPage > $totalComicPages) {
    $comicCurrentPage = $totalComicPages;
} elseif ($totalComicItems === 0) { // Wenn keine Items vorhanden sind, gibt es auch nur 1 leere Seite
    $comicCurrentPage = 1;
    $totalComicPages = 1;
}

$comicOffset = ($comicCurrentPage - 1) * COMIC_PAGES_PER_PAGE;
// array_slice behält hier die Keys, damit wir im Loop den ursprünglichen 'loc' Schlüssel verwenden können
$paginatedComicPages = array_slice($comicPages, $comicOffset, COMIC_PAGES_PER_PAGE, true);


// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
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
    }

    .collapsible-section.expanded .collapsible-content {
        max-height: 4800px;
        /* Adjust as needed for content */
        padding-top: 20px;
        padding-bottom: 20px;
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
            <div class="sitemap-table-container">
                <table class="sitemap-table" id="sitemap-table">
                    <thead>
                        <tr>
                            <th>Vorhanden</th>
                            <th>Loc (Pfad)</th>
                            <th>Priorität</th>
                            <th>Änderungsfrequenz</th>
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
                                    data-path="<?php echo htmlspecialchars($page['path']); ?>">
                                    <td>
                                        <?php
                                        // Prüfe, ob die Datei existiert
                                        // $webRootPath ist bereits der absolute Pfad zum Hauptverzeichnis
                                        $fileToCheck = $webRootPath . '/' . ltrim($page['loc'], './');

                                        if (file_exists($fileToCheck) && is_file($fileToCheck)) {
                                            echo '<i class="fas fa-check-circle" style="color: green;"></i>';
                                        } else {
                                            echo '<i class="fas fa-times-circle" style="color: red;"></i>';
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
                                        <div class="editable-field select-field" data-field="changefreq"
                                            contenteditable="false">
                                            <?php echo htmlspecialchars($page['changefreq']); ?>
                                            <select style="display:none;">
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
                            <th>Loc (Pfad)</th> <!-- Spaltenname zurückgesetzt -->
                            <th>Priorität</th>
                            <th>Änderungsfrequenz</th>
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
                                <tr data-original-loc="<?php echo htmlspecialchars($loc); ?>" data-path="./comic/">
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
                                        <div class="editable-field select-field" data-field="changefreq"
                                            contenteditable="false">
                                            <?php echo htmlspecialchars($page['changefreq']); ?>
                                            <select style="display:none;">
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

        // Event Delegation for editable fields (both tables)
        $(document).on('click', '.editable-field:not(.select-field)', function () {
            if (!$(this).hasClass('editing')) {
                $(this).addClass('editing').focus();
                // Store original value to revert if needed (not implemented in save logic, but good practice)
                $(this).data('original-value', $(this).text());
            }
        });

        $(document).on('blur', '.editable-field:not(.select-field)', function () {
            $(this).removeClass('editing');
        });

        // Event Delegation for select fields (both tables)
        $(document).on('click', '.editable-field.select-field', function () {
            const $div = $(this);
            const $select = $div.find('select');

            // Hide div text, show select
            $div.text('');
            $select.show().focus();
            $div.addClass('editing');

            $select.off('change').on('change', function () {
                $div.text($(this).val());
                $select.hide();
                $div.removeClass('editing');
            });

            $select.off('blur').on('blur', function () {
                // If blurred without changing, restore original text or current selected value
                if ($div.text() === '') { // If div is empty because select was shown
                    $div.text($(this).val());
                }
                $select.hide();
                $div.removeClass('editing');
            });
        });


        // Add row to general sitemap table
        $('.add-row-btn').on('click', function () {
            const tableBody = $('#sitemap-table tbody');
            const noDataRow = tableBody.find('.no-data-row');
            if (noDataRow.length) {
                noDataRow.remove();
            }
            const newRow = `
            <tr data-original-loc="" data-path="./">
                <td><i class="fas fa-question-circle" style="color: gray;"></i></td>
                <td><div class="editable-field" data-field="loc" contenteditable="true"></div></td>
                <td><div class="editable-field" data-field="priority" contenteditable="true">0.5</div></td>
                <td>
                    <div class="editable-field select-field" data-field="changefreq" contenteditable="false">weekly
                        <select style="display:none;">
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
            // Focus the new 'loc' field
            tableBody.find('tr:last .editable-field[data-field="loc"]').click();
        });

        // Delete row from either table
        $(document).on('click', '.delete-row-btn', function () {
            const row = $(this).closest('tr');
            row.remove();
            // Check if the table is empty and re-add no-data-row if necessary
            if ($('#sitemap-table tbody tr').length === 0) {
                $('#sitemap-table tbody').append('<tr class="no-data-row"><td colspan="5" style="text-align: center;">Keine allgemeinen Sitemap-Einträge vorhanden.</td></tr>');
            }
            if ($('#comic-table tbody tr').length === 0) {
                $('#comic-table tbody').append('<tr class="no-data-row"><td colspan="4" style="text-align: center;">Keine Comic-Sitemap-Einträge vorhanden.</td></tr>');
            }
        });

        // Save all changes
        $('#save-all-btn').on('click', function () {
            const allPages = [];

            // Collect data from General Sitemap Table
            $('#sitemap-table tbody tr').each(function () {
                if ($(this).hasClass('no-data-row')) return;

                const loc = $(this).find('[data-field="loc"]').text().trim();
                const path = $(this).data('path') || './'; // Standardpfad für allgemeine Seiten

                if (loc) { // Only add if loc is not empty
                    allPages.push({
                        loc: loc,
                        path: path,
                        priority: parseFloat($(this).find('[data-field="priority"]').text()) || 0.5,
                        changefreq: $(this).find('[data-field="changefreq"] select').val() || $(this).find('[data-field="changefreq"]').text().trim() || 'weekly'
                    });
                }
            });

            // Collect data from Comic Sitemap Table
            $('#comic-table tbody tr').each(function () {
                if ($(this).hasClass('no-data-row')) return;

                // Für Comic-Seiten ist das angezeigte Feld der Dateiname (z.B. 20250724.php)
                // Der Pfad ist fest './comic/'
                const filename = $(this).find('[data-field="loc"]').text().trim();
                const path = './comic/';
                const loc = path + filename; // Baue den vollständigen loc-Pfad zusammen

                if (filename) { // Only add if filename is not empty
                    allPages.push({
                        loc: loc,
                        path: path,
                        priority: parseFloat($(this).find('[data-field="priority"]').text()) || 0.8,
                        changefreq: $(this).find('[data-field="changefreq"] select').val() || $(this).find('[data-field="changefreq"]').text().trim() || 'never'
                    });
                }
            });

            $.ajax({
                url: window.location.href, // Send to the same PHP script
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ pages: allPages }),
                success: function (response) {
                    if (response.status === 'success') {
                        showMessage(response.message, 'success');
                        // Optional: Seite neu laden, um die aktualisierten Daten anzuzeigen
                        // window.location.reload();
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
} else {
    die('Fehler: Footer-Datei nicht gefunden. Pfad: ' . htmlspecialchars($footerPath));
}
?>