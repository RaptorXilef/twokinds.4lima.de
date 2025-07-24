<?php
/**
 * Dies ist die Administrationsseite zum Bearbeiten der sitemap.json Konfigurationsdatei.
 * Sie ermöglicht das Hinzufügen, Bearbeiten und Löschen von Sitemap-Einträgen
 * über eine benutzerfreundliche Oberfläche.
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
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
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
$sitemapConfigPath = __DIR__ . '/../src/components/sitemap.json';
// Basisverzeichnis für die Pfadprüfung (das Root-Verzeichnis der Website)
// Annahme: admin/sitemap_editor.php ist unter <webroot>/admin/sitemap_editor.php
// Daher ist das Webroot zwei Ebenen über dem aktuellen Skript
$webRootPath = realpath(__DIR__ . '/../..');

// Setze Parameter für den Header.
$pageTitle = 'Sitemap Editor';
$pageHeader = 'Sitemap Editor';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen

$message = '';
$messageType = ''; // 'success' or 'error'

// Funktion zum Laden der Sitemap-Konfiguration
function loadSitemapConfig(string $path): array {
    if (!file_exists($path)) {
        return ['pages' => []]; // Leeres Array, wenn Datei nicht existiert
    }
    $content = file_get_contents($path);
    $config = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren von sitemap.json: " . json_last_error_msg());
        return ['pages' => []]; // Leeres Array im Fehlerfall
    }
    return $config;
}

// Funktion zum Speichern der Sitemap-Konfiguration
function saveSitemapConfig(string $path, array $config): bool {
    $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren von Sitemap-Konfiguration: " . json_last_error_msg());
        return false;
    }
    if (file_put_contents($path, $jsonContent) === false) {
        error_log("Fehler beim Schreiben der Sitemap-Konfiguration nach " . $path);
        return false;
    }
    return true;
}

// Funktion zur Prüfung der Dateiexistenz
function checkFileExists(string $relativePath, string $basePath): bool {
    // Entferne führenden Slash für relative Pfade (außer wenn es nur ein Slash ist)
    $normalizedPath = ltrim($relativePath, '/');
    // Konstruiere den absoluten Pfad zur Datei
    $fullPath = realpath($basePath . '/' . $normalizedPath);

    // Prüfe, ob der Pfad existiert und ob er unterhalb des Webroots liegt
    if ($fullPath && str_starts_with($fullPath, $basePath)) {
        return file_exists($fullPath);
    }
    return false;
}

// Standardoptionen für Change Frequency und Priority
$changeFreqOptions = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
$priorityOptions = [];
for ($i = 10; $i >= 0; $i--) {
    $priorityOptions[] = sprintf('%.1f', $i / 10);
}

// Verarbeite POST-Anfragen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sitemap_config'])) {
    $updatedPages = [];
    if (isset($_POST['page_name']) && is_array($_POST['page_name'])) {
        foreach ($_POST['page_name'] as $index => $name) {
            $name = trim($name);
            $path = isset($_POST['page_path'][$index]) ? trim($_POST['page_path'][$index]) : '';
            // Sicherstellen, dass Priorität immer als Float behandelt wird, Default ist 0.5
            $priority = isset($_POST['page_priority'][$index]) ? (float)trim($_POST['page_priority'][$index]) : 0.5;
            $changefreq = isset($_POST['page_changefreq'][$index]) ? trim($_POST['page_changefreq'][$index]) : 'monthly';

            // Nur gültige Einträge hinzufügen (Name und Pfad dürfen nicht leer sein)
            if (!empty($name) && !empty($path)) {
                $updatedPages[] = [
                    'name' => $name,
                    'path' => $path,
                    'priority' => $priority, // Bereits als Float
                    'changefreq' => $changefreq
                ];
            }
        }
    }

    // Sortiere die aktualisierten Seiten: primär nach Priorität (absteigend), sekundär nach Name (aufsteigend)
    usort($updatedPages, function($a, $b) {
        // Priorität vergleichen (absteigend)
        $priorityComparison = $b['priority'] <=> $a['priority'];

        // Wenn Prioritäten gleich sind, nach Name sortieren (aufsteigend)
        if ($priorityComparison === 0) {
            return strnatcasecmp($a['name'], $b['name']); // Case-insensitive, natural string comparison
        }
        return $priorityComparison;
    });

    $sitemapConfig = ['pages' => $updatedPages];

    if (saveSitemapConfig($sitemapConfigPath, $sitemapConfig)) {
        $message = 'Sitemap-Konfiguration erfolgreich gespeichert!';
        $messageType = 'success';
    } else {
        $message = 'Fehler beim Speichern der Sitemap-Konfiguration.';
        $messageType = 'error';
    }
}

// Lade die aktuelle Konfiguration für die Anzeige
$sitemapConfig = loadSitemapConfig($sitemapConfigPath);

// Hier die Existenzprüfung für jede Seite hinzufügen
if (isset($sitemapConfig['pages']) && is_array($sitemapConfig['pages'])) {
    foreach ($sitemapConfig['pages'] as &$page) { // Referenz (&) verwenden, um das Array direkt zu modifizieren
        $page['exists'] = checkFileExists($page['path'], $webRootPath);
    }
    unset($page); // Referenz aufheben

    // Sortiere die geladenen Seiten: primär nach Priorität (absteigend), sekundär nach Name (aufsteigend)
    usort($sitemapConfig['pages'], function($a, $b) {
        // Priorität vergleichen (absteigend)
        $priorityComparison = $b['priority'] <=> $a['priority'];

        // Wenn Prioritäten gleich sind, nach Name sortieren (aufsteigend)
        if ($priorityComparison === 0) {
            return strnatcasecmp($a['name'], $b['name']); // Case-insensitive, natural string comparison
        }
        return $priorityComparison;
    });
}


// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<style>
    /* Grundlegende Stile für die Tabelle */
    .sitemap-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .sitemap-table th, .sitemap-table td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }
    .sitemap-table th {
        background-color: #ddead7;
    }
    /* ORIGINAL CSS FÜR GERADE ZEILEN - WIEDER AKTIVIERT */
    .sitemap-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    /* NEUE KLASSEN FÜR JAVASCRIPT GESTEUERTE STREIFEN */
    .sitemap-table .row-odd {
        /* Keine spezifische Hintergrundfarbe hier, da dies der Standard ist (weiß oder theme-abhängig) */
    }
    .sitemap-table .row-even {
        background-color: #f9f9f9; /* Standardfarbe für gerade Zeilen */
    }


    .sitemap-table th button.sort-button {
        background: none;
        border: none;
        color: inherit;
        font: inherit;
        cursor: pointer;
        padding: 0;
        margin-left: 5px;
        float: right; /* Positioniert den Button rechts im Header */
        display: flex; /* Für Icon */
        align-items: center; /* Für Icon */
        gap: 2px;
    }
    .sitemap-table th button.sort-button:hover {
        text-decoration: underline;
    }
    .sitemap-table th button.sort-button .sort-icon {
        font-size: 0.8em;
        vertical-align: middle;
    }
    .sitemap-table th button.sort-button.asc .sort-icon::before {
        content: "▲";
    }
    .sitemap-table th button.sort-button.desc .sort-icon::before {
        content: "▼";
    }
    .sitemap-table th button.sort-button:not(.asc):not(.desc) .sort-icon::before {
        content: "◆"; /* Neutraler Pfeil, wenn nicht sortiert */
        opacity: 0.5;
    }

    .sitemap-table input[type="text"],
    .sitemap-table select {
        width: 100%;
        padding: 5px;
        box-sizing: border-box;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    /* Tooltip für Input-Felder */
    .sitemap-table input[type="text"][title] {
        overflow: hidden; /* Verhindert, dass der Text überläuft */
        text-overflow: ellipsis; /* Fügt ... hinzu, wenn Text abgeschnitten wird */
        white-space: nowrap; /* Hält den Text in einer Zeile */
    }

    .sitemap-table button.remove-row {
        background-color: #f44336;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
        display: flex; /* Für SVG-Icon */
        align-items: center; /* Zentriert SVG */
        justify-content: center; /* Zentriert SVG */
        width: 30px; /* Feste Breite für das Icon */
        height: 30px; /* Feste Höhe für das Icon */
        padding: 0; /* Kein Padding, da feste Größe */
    }
    .sitemap-table button.remove-row svg {
        width: 20px; /* Größe der SVG anpassen */
        height: 20px;
        fill: currentColor; /* Verwendet die Textfarbe des Buttons */
    }
    .sitemap-table button.remove-row:hover {
        background-color: #d32f2f;
    }
    .button-container {
        margin-top: 20px;
        text-align: right;
    }
    .button-container .button {
        margin-left: 10px;
    }
    .message-box {
        margin-top: 20px;
        padding: 10px;
        border-radius: 5px;
        color: #155724; /* Standardfarbe für Erfolg */
        border: 1px solid #c3e6cb; /* Standardfarbe für Erfolg */
        background-color: #d4edda; /* Standardfarbe für Erfolg */
    }
    .message-box.error {
        color: #721c24;
        border-color: #f5c6cb;
        background-color: #f8d7da;
    }

    /* Neue Stile für Existenzprüfung */
    .status-icon {
        font-size: 1.2em;
        font-weight: bold;
        text-align: center;
        width: 100%;
        display: block; /* Zentriert das Symbol */
    }
    .status-icon.exists {
        color: #4CAF50; /* Grün */
    }
    .status-icon.not-exists {
        color: #f44336; /* Rot */
    }

    /* Dark Theme Anpassungen */
    body.theme-night .sitemap-table th {
        background-color: #48778a;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .sitemap-table td {
        border-color: #002b3c;
    }
    /* ORIGINAL DARK THEME CSS FÜR GERADE ZEILEN - WIEDER AKTIVIERT */
    body.theme-night .sitemap-table tr:nth-child(even) {
        background-color: #00334c;
    }
    /* NEUE KLASSEN FÜR JAVASCRIPT GESTEUERTE STREIFEN IM DARK THEME */
    body.theme-night .sitemap-table .row-odd {
        /* Keine spezifische Hintergrundfarbe hier */
    }
    body.theme-night .sitemap-table .row-even {
        background-color: #00334c; /* Dunkle Farbe für gerade Zeilen im Dark Theme */
    }


    body.theme-night .sitemap-table input[type="text"],
    body.theme-night .sitemap-table select {
        background-color: #2a6177;
        color: #fff;
        border-color: #002b3c;
    }
    body.theme-night .sitemap-table button.remove-row {
        background-color: #a00;
    }
    body.theme-night .sitemap-table button.remove-row:hover {
        background-color: #c00;
    }
    body.theme-night .message-box {
        color: #d4edda; /* Textfarbe für Erfolg im Dark Theme */
        border-color: #2a6177;
        background-color: #00334c;
    }
    body.theme-night .message-box.error {
        color: #f5c6cb;
        border-color: #721c24;
        background-color: #5a0000;
    }
    body.theme-night .sitemap-table th button.sort-button {
        color: #fff;
    }
</style>

<section>
    <h2 class="page-header">Sitemap Konfiguration bearbeiten</h2>

    <?php if (!empty($message)): ?>
        <div class="message-box <?php echo $messageType; ?>">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <table class="sitemap-table" id="sitemap-editor-table">
            <thead>
                <tr>
                    <th title="Existiert die Datei?">&#10003; / &#10007;</th> <th title="Dateiname der PHP, HTML oder HTM Datei">Name <button type="button" class="sort-button" data-sort-by="name"><span class="sort-icon"></span></button></th>
                    <th title="Dateipfad, relativ zum Hauptverzeichnis ./">Pfad <button type="button" class="sort-button" data-sort-by="path"><span class="sort-icon"></span></button></th>
                    <th title="Priorität (1.0=hoch; 0.5=normal; 0.1=niedrig)">Priorität <button type="button" class="sort-button" data-sort-by="priority"><span class="sort-icon"></span></button></th>
                    <th title="Änderungsfrequenz der Datei">Änderung <button type="button" class="sort-button" data-sort-by="changefreq"><span class="sort-icon"></span></button></th>
                    <th title="Aktion">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sitemapConfig['pages'])): ?>
                    <?php foreach ($sitemapConfig['pages'] as $index => $page): ?>
                        <tr class="<?php echo (($index + 1) % 2 === 0) ? 'row-even' : 'row-odd'; ?>">
                            <td class="file-status-cell">
                                <span class="status-icon <?php echo $page['exists'] ? 'exists' : 'not-exists'; ?>" data-path="<?php echo htmlspecialchars($page['path']); ?>">
                                    <?php echo $page['exists'] ? '&#10003;' : '&#10007;'; ?>
                                </span>
                            </td>
                            <td><input type="text" name="page_name[]" value="<?php echo htmlspecialchars($page['name']); ?>" title="<?php echo htmlspecialchars($page['name']); ?>"></td>
                            <td><input type="text" name="page_path[]" value="<?php echo htmlspecialchars($page['path']); ?>" title="<?php echo htmlspecialchars($page['path']); ?>"></td>
                            <td>
                                <select name="page_priority[]" title="<?php echo htmlspecialchars($page['priority']); ?>">
                                    <?php foreach ($priorityOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (isset($page['priority']) && (float)$page['priority'] == (float)$option) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="page_changefreq[]" title="<?php echo htmlspecialchars($page['changefreq']); ?>">
                                    <?php foreach ($changeFreqOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (isset($page['changefreq']) && $page['changefreq'] == $option) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="remove-row" title="Entfernen">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 41.336 41.336">
                                        <path d="M36.335 5.668h-8.167V1.5a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v4.168H5.001a2 2 0 000 4h2.001v29.168a2.5 2.5 0 002.5 2.5h22.332a2.5 2.5 0 002.5-2.5V9.668h2.001a2 2 0 000-4zM14.168 35.67a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm8 0a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm3-30.002h-9V3h9v2.668zm5 30.002a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21z" fill="currentColor"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="no-entries-row">
                        <td colspan="6" style="text-align: center;">Keine Einträge vorhanden. Fügen Sie neue hinzu.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="button-container">
            <button type="button" id="add-new-entry" class="button">Neuen Eintrag hinzufügen (+)</button>
            <button type="submit" name="save_sitemap_config" class="button">Änderungen speichern</button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#sitemap-editor-table tbody');
    const addEntryButton = document.getElementById('add-new-entry');
    const noEntriesRow = document.getElementById('no-entries-row');
    const sortButtons = document.querySelectorAll('.sort-button');

    // PHP-Variablen für JavaScript verfügbar machen
    const priorityOptions = <?php echo json_encode($priorityOptions); ?>;
    const changeFreqOptions = <?php echo json_encode($changeFreqOptions); ?>;
    // Map changeFreq options to an order index for sorting
    const changeFreqOrder = {};
    changeFreqOptions.forEach((freq, index) => {
        changeFreqOrder[freq] = index;
    });

    // Hilfsfunktion für HTML-Escaping in JavaScript
    function htmlspecialchars(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Funktion zum Hinzufügen einer neuen Zeile
    function addRow(page = {name: '', path: './', priority: '0.5', changefreq: 'monthly', exists: false}) {
        // Wenn die "Keine Einträge vorhanden"-Zeile existiert, entfernen
        if (noEntriesRow && noEntriesRow.parentNode === tableBody) {
            noEntriesRow.remove();
        }

        const newRow = document.createElement('tr');

        let priorityOptionsHtml = '';
        priorityOptions.forEach(option => {
            const selected = (parseFloat(page.priority) === parseFloat(option)) ? 'selected' : '';
            priorityOptionsHtml += `<option value="${htmlspecialchars(option)}" ${selected}>${htmlspecialchars(option)}</option>`;
        });

        let changeFreqOptionsHtml = '';
        changeFreqOptions.forEach(option => {
            const selected = (page.changefreq === option) ? 'selected' : '';
            changeFreqOptionsHtml += `<option value="${htmlspecialchars(option)}" ${selected}>${htmlspecialchars(option)}</option>`;
        });

        // Bestimme die Klassen und Symbole für den Existenzstatus
        const statusClass = page.exists ? 'exists' : 'not-exists';
        const statusSymbol = page.exists ? '&#10003;' : '&#10007;';

        newRow.innerHTML = `
            <td class="file-status-cell">
                <span class="status-icon ${statusClass}" data-path="${htmlspecialchars(page.path)}">
                    ${statusSymbol}
                </span>
            </td>
            <td><input type="text" name="page_name[]" value="${htmlspecialchars(page.name)}" title="${htmlspecialchars(page.name)}"></td>
            <td><input type="text" name="page_path[]" value="${htmlspecialchars(page.path)}" title="${htmlspecialchars(page.path)}"></td>
            <td>
                <select name="page_priority[]" title="${htmlspecialchars(page.priority)}">
                    ${priorityOptionsHtml}
                </select>
            </td>
            <td>
                <select name="page_changefreq[]" title="${htmlspecialchars(page.changefreq)}">
                    ${changeFreqOptionsHtml}
                </select>
            </td>
            <td>
                <button type="button" class="remove-row" title="Entfernen">
                    <svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 41.336 41.336">
                        <path d="M36.335 5.668h-8.167V1.5a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v4.168H5.001a2 2 0 000 4h2.001v29.168a2.5 2.5 0 002.5 2.5h22.332a2.5 2.5 0 002.5-2.5V9.668h2.001a2 2 0 000-4zM14.168 35.67a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm8 0a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21zm3-30.002h-9V3h9v2.668zm5 30.002a1.5 1.5 0 01-3 0v-21a1.5 1.5 0 013 0v21z" fill="currentColor"></path>
                    </svg>
                </button>
            </td>
        `;
        tableBody.appendChild(newRow);
        updateRowStriping();
    }

    tableBody.addEventListener('click', function(event) {
        if (event.target.closest('.remove-row')) { // closest() verwenden, da Klick auch auf SVG-Path gehen könnte
            event.target.closest('tr').remove();
            if (tableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.id = 'no-entries-row';
                emptyRow.innerHTML = '<td colspan="6" style="text-align: center;">Keine Einträge vorhanden. Fügen Sie neue hinzu.</td>';
                tableBody.appendChild(emptyRow);
            }
            updateRowStriping();
        }
    });

    function updateRowStriping() {
        const rows = tableBody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            if (row.id === 'no-entries-row') {
                row.classList.remove('row-odd', 'row-even');
                return;
            }
            row.classList.remove('row-odd', 'row-even');
            if (index % 2 === 0) {
                row.classList.add('row-odd');
            } else {
                row.classList.add('row-even');
            }
        });
    }


    let currentSortColumn = null;
    let currentSortDirection = 'asc';

    function sortTable(column) {
        const rows = Array.from(tableBody.querySelectorAll('tr:not(#no-entries-row)'));

        if (currentSortColumn === column) {
            currentSortDirection = (currentSortDirection === 'asc') ? 'desc' : 'asc';
        } else {
            currentSortColumn = column;
            if (column === 'priority' || column === 'exists') { // Standard: Existierende und hohe Prio zuerst
                currentSortDirection = 'desc';
            } else if (column === 'changefreq') {
                currentSortDirection = 'asc';
            } else {
                currentSortDirection = 'asc';
            }
        }

        sortButtons.forEach(button => {
            button.classList.remove('asc', 'desc');
        });

        const currentButton = document.querySelector(`.sort-button[data-sort-by="${column}"]`);
        if (currentButton) {
            currentButton.classList.add(currentSortDirection);
        }

        rows.sort((rowA, rowB) => {
            let primaryComparisonResult = 0;
            let valA, valB;

            switch (column) {
                case 'exists':
                    const existsA = rowA.querySelector('.status-icon').classList.contains('exists');
                    const existsB = rowB.querySelector('.status-icon').classList.contains('exists');
                    primaryComparisonResult = (currentSortDirection === 'asc') ? (existsA - existsB) : (existsB - existsA);
                    break;
                case 'name':
                    valA = rowA.querySelector('input[name="page_name[]"]').value;
                    valB = rowB.querySelector('input[name="page_name[]"]').value;
                    primaryComparisonResult = (currentSortDirection === 'asc') ? valA.localeCompare(valB, undefined, {sensitivity: 'base', numeric: true}) : valB.localeCompare(valA, undefined, {sensitivity: 'base', numeric: true});
                    break;
                case 'path':
                    valA = rowA.querySelector('input[name="page_path[]"]').value;
                    valB = rowB.querySelector('input[name="page_path[]"]').value;
                    if (valA === './' && valB !== './') primaryComparisonResult = (currentSortDirection === 'asc') ? -1 : 1;
                    else if (valA !== './' && valB === './') primaryComparisonResult = (currentSortDirection === 'asc') ? 1 : -1;
                    else primaryComparisonResult = (currentSortDirection === 'asc') ? valA.localeCompare(valB, undefined, {sensitivity: 'base', numeric: true}) : valB.localeCompare(valA, undefined, {sensitivity: 'base', numeric: true});
                    break;
                case 'priority':
                    valA = parseFloat(rowA.querySelector('select[name="page_priority[]"]').value);
                    valB = parseFloat(rowB.querySelector('select[name="page_priority[]"]').value);
                    primaryComparisonResult = (currentSortDirection === 'asc') ? valA - valB : valB - valA;
                    break;
                case 'changefreq':
                    valA = rowA.querySelector('select[name="page_changefreq[]"]').value;
                    valB = rowB.querySelector('select[name="page_changefreq[]"]').value;
                    const orderA = changeFreqOrder[valA];
                    const orderB = changeFreqOrder[valB];
                    primaryComparisonResult = (currentSortDirection === 'asc') ? orderA - orderB : orderB - orderA;
                    break;
                default:
                    primaryComparisonResult = 0;
            }

            // Wenn primäre Sortierung gleich ist, dann nach Name sortieren (alphabetisch aufsteigend)
            if (primaryComparisonResult === 0) {
                const nameA = rowA.querySelector('input[name="page_name[]"]').value;
                const nameB = rowB.querySelector('input[name="page_name[]"]').value;
                return nameA.localeCompare(nameB, undefined, {sensitivity: 'base', numeric: true});
            }
            return primaryComparisonResult;
        });

        tableBody.innerHTML = '';
        rows.forEach(row => tableBody.appendChild(row));
        updateRowStriping();
    }

    // Event Listener für Sortier-Buttons
    sortButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sortBy = this.dataset.sortBy;
            sortTable(sortBy);
        });
    });

    // Initialisierung: PHP sortiert bereits nach Priorität (desc) und Name (asc).
    const initialSortButton = document.querySelector('.sort-button[data-sort-by="priority"]');
    if (initialSortButton) {
        initialSortButton.classList.add('desc');
        currentSortColumn = 'priority';
        currentSortDirection = 'desc';
    }

    updateRowStriping();

    // Event Listener für "Neuen Eintrag hinzufügen" Button
    addEntryButton.addEventListener('click', function() {
        addRow();
    });
});
</script>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
}
?>