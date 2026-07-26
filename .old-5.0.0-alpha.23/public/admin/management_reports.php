<?php

/**
 * @description Administrationsseite zur Verwaltung der Fehlermeldungen (Reports).
 * Zeigt Meldungen an, filtert sie und erlaubt Aktionen (Schließen, Spam, Öffnen).
 * Verwendet flock() für alle Dateioperationen.
 *
 * @file      ROOT/public/admin/management_reports.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 4.0.0
 *  - Initiale Erstellung
 *  - Korrektur der JS-Pfade.
 *  - Anpassung des Detail-Modals zur Anzeige von HTML-Transkripten und Text-Diffs.
 *
 * @since 5.0.0
 * - refactor(Page): Inline-CSS entfernt, Layout auf Admin-Tabellen-Standards (SCSS) umgestellt, PHP-Paginierung
 *    hinzugefügt.
 * - feat(UI): Paginierung-Info und konfigurierbare Textkürzung (TRUNCATE_REPORT_DESCRIPTION) hinzugefügt.
 * - refactor(Config): Nutzung spezifischer Konstanten (ENTRIES_PER_PAGE_REPORT, TRUNCATE_REPORT_DESCRIPTION).
 * - feat(UI): Info-Feld (Zeitstempel & Beschreibung) und Paginierung-Info hinzugefügt.
 * - feat(UX): Erfolgsmeldungen blenden sich nun automatisch nach 5 Sekunden aus.
 * - refactor(CSS): Alle verbleibenden Inline-Styles durch SCSS-Klassen ersetzt.
 * - refactor(CSS): Bereinigung verbliebener Inline-Styles im JavaScript.
 * - refactor(Core): Einführung von strict_types=1.
 * - refactor(Config): Umstellung auf zentrale 'admin/config_generator_settings.json'.
 * - fix(Config): Speicherstruktur korrigiert (users -> username -> reports_manager).
 * - fix(Logic): loadReportSettings gibt nun flaches Array zurück (Konsistenz).
 *
 * - refactor(Logic): moveReport auf PHP 8.3 match-Expression umgestellt.
 * - feat(UI): Bild-Abgleich (Lowres DE vs. Original EN) im Detail-Modal integriert.
 * - feat(Telemetrie): Anzeige von Client-Zustandsdaten im Admin-Interface.
 * - fix(Security): CSRF-Validierung optimiert.
 *
 * Variablen-Index:
 * - string $reportsFilePath: Pfad zur aktiven JSON-Datenbank.
 * - array $displayedReports: Die für die aktuelle Seite gefilterten Datensätze.
 * - int $itemsPerPage: Anzahl der Einträge pro Paginierungsschritt.
 * - object Path/Url: Core-Helfer für Pfad- und URL-Auflösung.
 */

declare(strict_types=1);

// === 1. ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === 2. KONSTANTEN & VARIABLEN ===
$pageHeader = 'Fehlermeldungen verwalten';
$reportsFilePath = Path::getDataPath('comic_reports.json');
$archiveFilePath = Path::getDataPath('comic_reports_archive.json');
// Config Path auf admin/ Ordner aktualisiert
$settingsFilePath = Path::getConfigPath('admin/config_generator_settings.json');
$currentUser = $_SESSION['admin_username'] ?? 'default';

$message = ''; // Für Statusmeldungen
$messageType = 'info';

// Paginierung Konstante nutzen (falls nicht definiert, Fallback)
$itemsPerPage = defined('ENTRIES_PER_PAGE_REPORT') ? ENTRIES_PER_PAGE_REPORT : 50;

// Konfiguration für Textkürzung (Standard: False = Alles anzeigen, wenn nicht definiert)
if (!defined('TRUNCATE_REPORT_DESCRIPTION')) {
    define('TRUNCATE_REPORT_DESCRIPTION', false);
}

// === 3. HELFERFUNKTIONEN (mit flock) ===

/**
 * Liest die Generator-Settings für den Zeitstempel (User-spezifisch).
 */
function loadReportSettings(string $filePath, string $username): array
{
    // Flache Defaults
    $defaults = ['last_run_timestamp' => null];
    if (!file_exists($filePath)) {
        // Falls Datei noch gar nicht existiert, erstellen wir sie leer
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode(['users' => []], JSON_PRETTY_PRINT));
        return $defaults;
    }

    $content = file_get_contents($filePath);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        return $defaults;
    }

    // Spezifische Einstellungen für den User laden
    $userSettings = $data['users'][$username]['reports_manager'] ?? [];

    // Fallback Logik: Wenn Zeitstempel leer, versuche filemtime der reports.json
    if (empty($userSettings['last_run_timestamp'])) {
        $reportsJsonPath = Path::getDataPath('comic_reports.json');
        if (file_exists($reportsJsonPath)) {
            $userSettings['last_run_timestamp'] = filemtime($reportsJsonPath);
        }
    }

    // Merge mit Defaults
    return array_replace_recursive($defaults, $userSettings);
}

/**
 * Aktualisiert den Zeitstempel für den User (wird beim Verschieben aufgerufen).
 */
function updateReportTimestamp(string $filePath, string $username): void
{
    // Wir müssen die ganze Datei lesen, um die Struktur zu erhalten
    $data = [];
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data = $decoded;
        }
    }

    if (!isset($data['users'])) {
        $data['users'] = [];
    }
    if (!isset($data['users'][$username])) {
        $data['users'][$username] = [];
    }

    // Zeitstempel setzen
    $data['users'][$username]['reports_manager']['last_run_timestamp'] = time();
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/**
 * Liest eine JSON-Datei sicher (mit Sperre).
 */
function loadJsonWithLock(string $path, bool $debugMode): array
{
    if (!file_exists($path)) {
        return [];
    }
    $handle = fopen($path, 'r');
    if (!$handle) {
        return [];
    }

    flock($handle, LOCK_SH);
    rewind($handle); // Sicherstellen, dass am Anfang gestartet wird
    $content = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    $data = json_decode((string)$content, true);
    return json_last_error() === JSON_ERROR_NONE && is_array($data) ? $data : [];
}

/**
 * Schreibt eine JSON-Datei sicher (mit exklusiver Sperre).
 */
function saveJsonWithLock(string $path, array $data, bool $debugMode): bool
{
    $handle = fopen($path, 'c+');
    if (!$handle) {
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    return true;
}

/**
 * Verschiebt/Ändert einen Report-Status.
 */
function moveReport(string $reportId, string $action, string $reportsPath, string $archivePath, string $settingsPath, string $username, bool $debugMode): bool
{
    $handleReports = fopen($reportsPath, 'c+');
    $handleArchive = fopen($archivePath, 'c+');

    if (!$handleReports || !$handleArchive) {
        return false;
    }

    flock($handleReports, LOCK_EX);
    flock($handleArchive, LOCK_EX);

    // Sicherstellen, dass wir am Anfang der Datei lesen
    rewind($handleReports);
    rewind($handleArchive);

    $reports = json_decode(stream_get_contents($handleReports) ?: '[]', true) ?: [];
    $archive = json_decode(stream_get_contents($handleArchive) ?: '[]', true) ?: [];

    $reportData = null;
    $reportFound = false;

    // 1. Suche in aktiven Reports
    foreach ($reports as $key => $report) {
        if (strval($report['id'] ?? '') === $reportId) {
            $reportData = $report;
            unset($reports[$key]);
            $reportFound = true;
            break;
        }
    }

    // 2. Suche im Archiv (nur wenn bei Reports nicht gefunden)
    if (!$reportFound) {
        foreach ($archive as $key => $report) {
            if (strval($report['id'] ?? '') === $reportId) {
                $reportData = $report;
                unset($archive[$key]);
                $reportFound = true;
                break;
            }
        }
    }

    // 3. Status-Logik via match (Modern & Sicher)
    $newStatus = match ($action) {
        'close'  => 'closed',
        'spam'   => 'spam',
        'reopen' => 'open',
        default  => null,
    };

    // Fail Fast: Wenn kein Report gefunden oder Aktion ungültig
    if (!$reportFound || $newStatus === null) {
        flock($handleReports, LOCK_UN);
        fclose($handleReports);
        flock($handleArchive, LOCK_UN);
        fclose($handleArchive);
        return false;
    }

    // 4. Daten aktualisieren und verteilen
    $reportData['status'] = $newStatus;
    if ($newStatus === 'closed') {
        $archive[] = $reportData;
    } else {
        $reports[] = $reportData;
    }

    // 5. Persistenz
    ftruncate($handleReports, 0);
    rewind($handleReports);
    fwrite($handleReports, json_encode(array_values($reports), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    ftruncate($handleArchive, 0);
    rewind($handleArchive);
    fwrite($handleArchive, json_encode(array_values($archive), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    updateReportTimestamp($settingsPath, $username);

    flock($handleReports, LOCK_UN);
    fclose($handleReports);
    flock($handleArchive, LOCK_UN);
    fclose($handleArchive);

    return true;
}

// === 4. POST-AKTIONEN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $success = moveReport($_POST['report_id'], $_POST['action'], $reportsFilePath, $archiveFilePath, $settingsFilePath, $currentUser, $debugMode);
        $queryParams = $_GET;
        $queryParams['message'] = $success ? "Aktion erfolgreich." : "Fehler bei Aktion.";
        $queryParams['messageType'] = $success ? 'green' : 'red';
        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?' . http_build_query($queryParams));
        exit;
    }

    $message = 'CSRF-Token-Fehler.';
    $messageType = 'red';
}

// === 5. GET-PARAMETER & FILTERUNG ===
$filterStatus = $_GET['status'] ?? 'open';
$filterComicId = $_GET['comic_id'] ?? '';
$filterSubmitter = $_GET['submitter'] ?? '';
$currentPage = max(1, intval($_GET['page'] ?? 1));

if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
    $messageType = htmlspecialchars($_GET['messageType'] ?? 'info');
}

$isArchive = ($filterStatus === 'closed');
$allReports = loadJsonWithLock(($isArchive ? $archiveFilePath : $reportsFilePath), $debugMode);
$reportSettings = loadReportSettings($settingsFilePath, $currentUser);

$reports = array_filter($allReports, function ($r) use ($filterStatus, $filterComicId, $filterSubmitter) {
    if (($r['status'] ?? 'open') !== $filterStatus) {
        return false;
    }
    if (!empty($filterComicId) && !str_contains(strval($r['comic_id'] ?? ''), $filterComicId)) {
        return false;
    }
    return empty($filterSubmitter) || !(stripos($r['submitter_name'] ?? '', $filterSubmitter) === false);
});

usort($reports, fn($a, $b) => $isArchive
    ? strtotime($b['date'] ?? '0') <=> strtotime($a['date'] ?? '0')
    : strtotime($a['date'] ?? '0') <=> strtotime($b['date'] ?? '0'));

$totalItems = count($reports);
$totalPages = (int)ceil($totalItems / $itemsPerPage);
$currentPage = $totalPages > 0 ? min($currentPage, $totalPages) : 1;
$displayedReports = array_slice($reports, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);

function getPageLink($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

require_once Path::getPartialTemplatePath('header.php');
?>

<div class="content-section">
    <div id="settings-and-actions-container">
        <div id="last-run-container">
            <?php if (!empty($reportSettings['last_run_timestamp'])) : ?>
                <p class="status-message status-info">Letzte Änderung: <?php echo date('d.m.Y H:i:s', $reportSettings['last_run_timestamp']); ?></p>
            <?php endif; ?>
        </div>
        <h2>Fehlermeldungen verwalten</h2>
    </div>

    <?php if ($message) : ?>
        <div id="main-status-message" class="status-message status-<?php echo $messageType; ?> visible"><p><?php echo $message; ?></p></div>
    <?php endif; ?>

    <form method="GET" class="admin-form filter-form">
        <fieldset>
            <legend>Filter</legend>
            <div class="filter-controls">
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Offen</option>
                        <option value="spam" <?php echo $filterStatus === 'spam' ? 'selected' : ''; ?>>Spam</option>
                        <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Archiv</option>
                    </select>
                </div>
                <div><label>Comic-ID</label><input type="text" name="comic_id" value="<?php echo htmlspecialchars($filterComicId); ?>"></div>
                <div><label>Einsender</label><input type="text" name="submitter" value="<?php echo htmlspecialchars($filterSubmitter); ?>"></div>
                <button type="submit" class="button">Filtern</button>
            </div>
        </fieldset>
    </form>

    <div class="sitemap-table-container">
        <table class="admin-table data-table reports-table" id="reports-table">
            <thead>
                <tr><th>Datum</th><th>Comic-ID</th><th>Typ</th><th>Einsender</th><th>Vorschau</th><th>Aktionen</th></tr>
            </thead>
            <tbody>
                <?php if (empty($displayedReports)) : ?>
                    <tr><td colspan="6" class="text-center">Keine Meldungen gefunden.</td></tr>
                <?php else : ?>
                    <?php foreach ($displayedReports as $report) : ?>
                        <?php
                        $desc = htmlspecialchars((string)($report['description'] ?? ''));
                        $shortDesc = mb_strlen($desc) > 60 ? mb_substr($desc, 0, 60) . '...' : $desc;
                        ?>
                        <tr data-report-id="<?php echo htmlspecialchars((string)$report['id']); ?>"
                            data-comic-id="<?php echo htmlspecialchars((string)$report['comic_id']); ?>"
                            data-date="<?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($report['date']))); ?>"
                            data-type="<?php echo htmlspecialchars((string)$report['report_type']); ?>"
                            data-submitter="<?php echo htmlspecialchars((string)$report['submitter_name']); ?>"
                            data-full-description="<?php echo $desc; ?>"
                            data-suggestion="<?php echo htmlspecialchars((string)($report['transcript_suggestion'] ?? '')); ?>"
                            data-original="<?php echo htmlspecialchars((string)($report['transcript_original'] ?? '')); ?>"
                            data-debug-info="<?php echo htmlspecialchars((string)($report['debug_info'] ?? 'N/A')); ?>">

                            <td><?php echo date('d.m.y H:i', strtotime($report['date'])); ?></td>
                            <td><a href="<?php echo Url::getComicPageUrl($report['comic_id'] . $dateiendungPHP); ?>" target="_blank"><?php echo htmlspecialchars((string)$report['comic_id']); ?></a></td>
                            <td><?php echo htmlspecialchars((string)$report['report_type']); ?></td>
                            <td><?php echo htmlspecialchars((string)$report['submitter_name']); ?></td>
                            <td><?php echo $shortDesc ?: '<em>Inhalt vorhanden</em>'; ?></td>
                            <td class="actions-cell">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <button type="button" class="button detail-button" title="Details"><i class="fas fa-search-plus"></i></button>
                                    <?php if ($filterStatus === 'open') : ?>
                                        <button type="submit" name="action" value="close" class="button button-green"><i class="fas fa-check"></i></button>
                                        <button type="submit" name="action" value="spam" class="button button-orange"><i class="fas fa-ban"></i></button>
                                    <?php else : ?>
                                        <button type="submit" name="action" value="reopen" class="button button-blue"><i class="fas fa-undo"></i></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1) : ?>
        <div class="pagination">
            <?php if ($currentPage > 1) : ?>
                <a href="<?php echo getPageLink(1); ?>">&laquo;</a>
                <a href="<?php echo getPageLink($currentPage - 1); ?>">&lsaquo;</a>
            <?php endif; ?>
            <?php
            $start = max(1, $currentPage - 4);
            $end = min($totalPages, $currentPage + 4);
            for ($i = $start; $i <= $end; $i++) : ?>
                <a href="<?php echo getPageLink($i); ?>" class="<?php echo $i === $currentPage ? 'current-page' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages) : ?>
                <a href="<?php echo getPageLink($currentPage + 1); ?>">&rsaquo;</a>
                <a href="<?php echo getPageLink($totalPages); ?>">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div id="report-detail-modal" class="modal admin-modal hidden-by-default" role="dialog" aria-modal="true">
    <div class="modal-overlay" data-action="close-detail-modal"></div>
    <div class="modal-content report-detail-modal-content modal-advanced-layout">
        <div class="modal-header-wrapper">
            <h2>Ticket: <span id="detail-comic-id"></span></h2>
            <button class="modal-close" data-action="close-detail-modal">&times;</button>
        </div>
        <div class="modal-scroll-content">
            <div class="admin-form">
                <h3>Bild-Abgleich</h3>
                <div class="report-images-container form-group">
                    <div>
                        <label>Aktuelles Bild (DE)</label>
                        <img id="detail-img-lowres" src="" alt="DE Preview" loading="lazy">
                    </div>
                    <div>
                        <label>Original (EN)</label>
                        <img id="detail-img-original" src="" alt="EN Preview" loading="lazy">
                    </div>
                </div>

                <div class="report-meta-grid">
                    <div><strong>Einsender:</strong> <span id="detail-submitter"></span></div>
                    <div><strong>Datum:</strong> <span id="detail-date"></span></div>
                    <div><strong>Typ:</strong> <span id="detail-type"></span></div>
                </div>

                <h3>Fehlerbeschreibung</h3>
                <div class="report-text-box"><p id="detail-description"></p></div>

                <h3>System-Telemetrie (Client-Zustand)</h3>
                <div class="form-group">
                    <textarea id="detail-debug-info" class="summernote-code-look" readonly rows="8"></textarea>
                </div>

                <div id="detail-transcript-section">
                    <h3>Transkript Diff</h3>
                    <div id="detail-diff-viewer" class="report-text-box diff-box"></div>
                    <h3>Gerenderter HTML-Vorschlag</h3>
                    <div id="detail-suggestion-html" class="report-text-box"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer-actions">
            <button type="button" class="button" data-action="close-detail-modal">Schließen</button>
        </div>
    </div>
</div>

<script src="<?php echo Url::getAdminJsUrl('jsdiff.min.js'); ?>" nonce="<?php echo $nonce; ?>"></script>
<script nonce="<?php echo $nonce; ?>">
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('report-detail-modal');
    const statusMsg = document.getElementById('main-status-message');
    if (statusMsg) {
        setTimeout(() => { statusMsg.style.display = 'none'; }, 5000);
    }

    document.getElementById('reports-table').addEventListener('click', function(e) {
        const btn = e.target.closest('.detail-button');
        if (!btn) { return; }

        const data = btn.closest('tr').dataset;
        const debug = data.debugInfo;

        document.getElementById('detail-comic-id').textContent = data.comicId;
        document.getElementById('detail-submitter').textContent = data.submitter;
        document.getElementById('detail-date').textContent = data.date;
        document.getElementById('detail-type').textContent = data.type;
        document.getElementById('detail-description').textContent = data.fullDescription;
        document.getElementById('detail-debug-info').value = debug;

        // Bilder setzen
        document.getElementById('detail-img-lowres').src = `../../assets/images/comic/lowres/${data.comicId}.webp`;
        const urlMatch = debug.match(/Original:\s+(https?:\/\/[^\n]+)/);
        document.getElementById('detail-img-original').src = urlMatch ? urlMatch[1] : 'https://placehold.co/600x400/dc3545/ffffff?text=Link+nicht+ermittelbar';

        // Diff-Logik
        if (typeof Diff !== 'undefined') {
            const clean = (h) => {
                const d = document.createElement('div');
                d.innerHTML = h;
                return (d.textContent || d.innerText || '').trim();
            };
            const diff = Diff.diffWords(clean(data.original), clean(data.suggestion));
            const viewer = document.getElementById('detail-diff-viewer');
            viewer.innerHTML = '';
            diff.forEach(part => {
                const el = document.createElement(part.added ? 'ins' : (part.removed ? 'del' : 'span'));
                el.textContent = part.value;
                viewer.appendChild(el);
            });
        }

        document.getElementById('detail-suggestion-html').innerHTML = data.suggestion;
        modal.classList.remove('hidden-by-default');
        modal.style.display = 'flex';
    });

    document.querySelectorAll('[data-action="close-detail-modal"]').forEach(el => {
        el.addEventListener('click', () => { modal.style.display = 'none'; });
    });
});
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
