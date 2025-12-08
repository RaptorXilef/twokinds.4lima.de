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
 *  - refactor(Page): Inline-CSS entfernt, Layout auf Admin-Tabellen-Standards (SCSS) umgestellt, PHP-Paginierung
 *     hinzugefügt.
 *  - feat(UI): Paginierung-Info und konfigurierbare Textkürzung (TRUNCATE_REPORT_DESCRIPTION) hinzugefügt.
 *  - refactor(Config): Nutzung spezifischer Konstanten (ENTRIES_PER_PAGE_REPORT, TRUNCATE_REPORT_DESCRIPTION).
 *  - feat(UI): Info-Feld (Zeitstempel & Beschreibung) und Paginierung-Info hinzugefügt.
 *  - feat(UX): Erfolgsmeldungen blenden sich nun automatisch nach 5 Sekunden aus.
 *  - refactor(CSS): Alle verbleibenden Inline-Styles durch SCSS-Klassen ersetzt.
 *  - refactor(CSS): Bereinigung verbliebener Inline-Styles im JavaScript.
 */

// === 1. ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === 2. KONSTANTEN & VARIABLEN ===
$pageHeader = 'Fehlermeldungen verwalten';
$reportsFilePath = Path::getDataPath('comic_reports.json');
$archiveFilePath = Path::getDataPath('comic_reports_archive.json');
$settingsFilePath = Path::getConfigPath('config_generator_settings.json'); // NEU: Für Zeitstempel

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
 * Liest die Generator-Settings für den Zeitstempel.
 */
function loadReportSettings(string $filePath): array
{
    $defaults = ['reports_manager' => ['last_run_timestamp' => null]];
    if (!file_exists($filePath)) {
        return $defaults;
    }
    $content = file_get_contents($filePath);
    $settings = json_decode($content, true);
    // Sicherstellen, dass der Key existiert
    if (!isset($settings['reports_manager'])) {
        $settings['reports_manager'] = ['last_run_timestamp' => filemtime(Path::getDataPath('comic_reports.json'))];
    }
    return (json_last_error() === JSON_ERROR_NONE && is_array($settings)) ? $settings : $defaults;
}

/**
 * Aktualisiert den Zeitstempel (wird beim Verschieben aufgerufen).
 */
function updateReportTimestamp(string $filePath): void
{
    $settings = loadReportSettings($filePath);
    $settings['reports_manager']['last_run_timestamp'] = time();
    file_put_contents($filePath, json_encode($settings, JSON_PRETTY_PRINT));
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

    // Shared Lock versuchen
    if (flock($handle, LOCK_SH)) {
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
    } else {
        // Fallback
        if ($debugMode) {
            error_log("loadJsonWithLock: Konnte keinen Lock erhalten, lese trotzdem: $path");
        }
        $content = stream_get_contents($handle);
    }
    fclose($handle);

    if (empty($content)) {
        return [];
    }

    $data = json_decode($content, true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($data)) ? $data : [];
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

    if (flock($handle, LOCK_EX)) {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
    } else {
        fclose($handle);
        return false;
    }
    fclose($handle);
    return true;
}

/**
 * Verschiebt/Ändert einen Report-Status.
 */
function moveReport(string $reportId, string $action, string $reportsPath, string $archivePath, string $settingsPath, bool $debugMode): bool
{
    $handleReports = fopen($reportsPath, 'c+');
    $handleArchive = fopen($archivePath, 'c+');

    if (!$handleReports || !$handleArchive) {
        if ($handleReports) {
            fclose($handleReports);
        }
        if ($handleArchive) {
            fclose($handleArchive);
        }
        return false;
    }

    if (!flock($handleReports, LOCK_EX) || !flock($handleArchive, LOCK_EX)) {
        if ($handleReports) {
            flock($handleReports, LOCK_UN);
            fclose($handleReports);
        }
        if ($handleArchive) {
            flock($handleArchive, LOCK_UN);
            fclose($handleArchive);
        }
        return false;
    }

    // Daten einlesen
    rewind($handleReports);
    $reports = json_decode(stream_get_contents($handleReports), true) ?? [];
    rewind($handleArchive);
    $archive = json_decode(stream_get_contents($handleArchive), true) ?? [];

    $reportFound = false;
    $reportData = null;
    $targetList = null;
    $newStatus = '';

    // Suchen in Reports
    foreach ($reports as $key => $report) {
        if (isset($report['id']) && strval($report['id']) === strval($reportId)) {
            $reportData = $report;
            if ($action === 'close') {
                $newStatus = 'closed';
                $targetList = 'archive';
            } elseif ($action === 'spam') {
                $newStatus = 'spam';
                $targetList = 'reports';
            } elseif ($action === 'reopen') {
                $newStatus = 'open';
                $targetList = 'reports';
            }
            unset($reports[$key]);
            $reportFound = true;
            break;
        }
    }

    // Suchen in Archiv (falls nicht gefunden)
    if (!$reportFound) {
        foreach ($archive as $key => $report) {
            if (isset($report['id']) && strval($report['id']) === strval($reportId)) {
                $reportData = $report;
                if ($action === 'reopen') {
                    $newStatus = 'open';
                    $targetList = 'reports';
                }
                unset($archive[$key]);
                $reportFound = true;
                break;
            }
        }
    }

    $success = false;
    if ($reportFound && $newStatus) {
        $reportData['status'] = $newStatus;

        if (($targetList === 'archive') || ($targetList === null && $action === 'close')) {
            $archive[] = $reportData;
        } else {
            $reports[] = $reportData;
        }

        $reports = array_values($reports);
        $archive = array_values($archive);

        ftruncate($handleReports, 0);
        rewind($handleReports);
        fwrite($handleReports, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        ftruncate($handleArchive, 0);
        rewind($handleArchive);
        fwrite($handleArchive, json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $success = true;

        // NEU: Zeitstempel aktualisieren bei Änderung
        updateReportTimestamp($settingsPath);
    }

    flock($handleReports, LOCK_UN);
    fclose($handleReports);
    flock($handleArchive, LOCK_UN);
    fclose($handleArchive);
    return $success;
}

// === 4. POST-AKTIONEN VERARBEITEN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'CSRF-Token-Fehler.';
        $messageType = 'red';
    } else {
        $action = $_POST['action'];
        $success = moveReport($_POST['report_id'], $action, $reportsFilePath, $archiveFilePath, $settingsFilePath, $debugMode);

        $queryParams = $_GET;
        $queryParams['message'] = $success ? "Report erfolgreich ($action)." : "Aktion ($action) fehlgeschlagen.";
        $queryParams['messageType'] = $success ? 'green' : 'red';
        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?' . http_build_query($queryParams));
        exit;
    }
}

// === 5. GET-PARAMETER VERARBEITEN ===
$filterStatus = $_GET['status'] ?? 'open';
$filterComicId = $_GET['comic_id'] ?? '';
$filterSubmitter = $_GET['submitter'] ?? '';
$currentPage = max(1, intval($_GET['page'] ?? 1));

if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
    $messageType = htmlspecialchars($_GET['messageType'] ?? 'info');
}

// === 6. DATEN LADEN & FILTERN ===
$isArchive = ($filterStatus === 'closed');
$sourcePath = $isArchive ? $archiveFilePath : $reportsFilePath;
$allReports = loadJsonWithLock($sourcePath, $debugMode);
$reportSettings = loadReportSettings($settingsFilePath);

// Filtern
$reports = array_filter($allReports, function ($r) use ($filterStatus, $filterComicId, $filterSubmitter) {
    $rStatus = $r['status'] ?? 'open';
    if ($filterStatus === 'closed' && $rStatus !== 'closed') {
        return false;
    }
    if ($filterStatus === 'open' && $rStatus !== 'open') {
        return false;
    }
    if ($filterStatus === 'spam' && $rStatus !== 'spam') {
        return false;
    }

    if (!empty($filterComicId) && !str_contains(strval($r['comic_id'] ?? ''), $filterComicId)) {
        return false;
    }
    if (!empty($filterSubmitter) && stripos($r['submitter_name'] ?? '', $filterSubmitter) === false) {
        return false;
    }

    return true;
});

// Sortieren
if ($isArchive) {
    usort($reports, fn($a, $b) => strtotime($b['date'] ?? 0) <=> strtotime($a['date'] ?? 0));
} else {
    usort($reports, fn($a, $b) => strtotime($a['date'] ?? 0) <=> strtotime($b['date'] ?? 0));
}

// Paginierung
$totalItems = count($reports);
$totalPages = ceil($totalItems / $itemsPerPage);
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}
if ($totalPages == 0) {
    $currentPage = 1;
}

$displayedReports = array_slice($reports, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);

function getPageLink($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// === 7. SEITE RENDERN ===
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <div class="content-section">
        <!-- NEU: Info-Box oben (ähnlich Comic Editor) -->
        <div id="settings-and-actions-container">
            <div id="last-run-container">
                <?php if (!empty($reportSettings['reports_manager']['last_run_timestamp'])) : ?>
                    <p class="status-message status-info">Letzte Änderung am
                        <?php echo date('d.m.Y \u\m H:i:s', $reportSettings['reports_manager']['last_run_timestamp']); ?> Uhr.
                    </p>
                <?php endif; ?>
            </div>
            <h2>Fehlermeldungen verwalten</h2>
            <p>Übersicht aller gemeldeten Fehler. Bearbeite offene Meldungen, markiere sie als erledigt oder Spam, oder durchsuche das Archiv.</p>
        </div>

        <?php if ($message) : ?>
            <!-- FIX: Klasse "visible" statt inline style="display:block" -->
            <div id="main-status-message" class="status-message status-<?php echo $messageType; ?> visible">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>

        <form action="<?php echo basename($_SERVER['PHP_SELF']); ?>" method="GET" class="admin-form filter-form">
            <fieldset>
                <legend>Filter</legend>
                <div class="filter-controls">
                    <div>
                        <label for="filter-status">Status</label>
                        <select id="filter-status" name="status">
                            <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Offen (Älteste zuerst)</option>
                            <option value="spam" <?php echo $filterStatus === 'spam' ? 'selected' : ''; ?>>Spam (Älteste zuerst)</option>
                            <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>Archiv (Neueste zuerst)</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-comic-id">Comic-ID</label>
                        <input type="text" id="filter-comic-id" name="comic_id" value="<?php echo htmlspecialchars($filterComicId); ?>">
                    </div>
                    <div>
                        <label for="filter-submitter">Einsender</label>
                        <input type="text" id="filter-submitter" name="submitter" value="<?php echo htmlspecialchars($filterSubmitter); ?>">
                    </div>
                    <button type="submit" class="button">Filtern</button>
                </div>
            </fieldset>
        </form>

        <!-- FIX: Pagination Info Container statt inline styles -->
        <div class="pagination-info-container">
            <small>Zeigt <?php echo $itemsPerPage; ?> Einträge pro Seite.</small>
        </div>

        <div class="sitemap-table-container">
            <table class="admin-table data-table reports-table" id="reports-table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Comic-ID</th>
                        <th>Typ</th>
                        <th>Einsender</th>
                        <th>Beschreibung</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($displayedReports)) : ?>
                        <tr>
                            <!-- FIX: Klasse "text-center" statt inline style -->
                            <td colspan="6" class="text-center">Keine Meldungen für die aktuellen Filter gefunden.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($displayedReports as $report) : ?>
                            <?php
                            $reportId = htmlspecialchars($report['id'] ?? '');
                            $comicId = htmlspecialchars($report['comic_id'] ?? 'Unbekannt');
                            $comicLink = Url::getComicPageUrl($comicId . $dateiendungPHP);
                            $dateRaw = $report['date'] ?? 'now';
                            $date = htmlspecialchars(date('d.m.Y H:i', strtotime($dateRaw)));
                            $type = htmlspecialchars($report['report_type'] ?? 'k.A.');
                            $submitter = htmlspecialchars($report['submitter_name'] ?? 'k.A.');
                            $description = htmlspecialchars($report['description'] ?? '');
                            $suggestion = htmlspecialchars($report['transcript_suggestion'] ?? '');
                            $original = htmlspecialchars($report['transcript_original'] ?? '');
                            $status = $report['status'] ?? 'open';

                            // --- NEU: TEXT-KÜRZUNG LOGIK (via Konstante) ---
                            if (TRUNCATE_REPORT_DESCRIPTION) {
                                $shortDesc = mb_strlen($description) > 75 ? mb_substr($description, 0, 75) . '...' : $description;
                            } else {
                                // Volltext anzeigen
                                $shortDesc = $description;
                            }

                            if (empty($shortDesc) && !empty($suggestion)) {
                                $shortDesc = '<i>(Nur Transkript-Vorschlag)</i>';
                            }
                            ?>
                            <tr data-report-id="<?php echo $reportId; ?>" data-comic-id="<?php echo $comicId; ?>"
                                data-date="<?php echo $date; ?>" data-type="<?php echo $type; ?>"
                                data-submitter="<?php echo $submitter; ?>" data-status="<?php echo htmlspecialchars($status); ?>"
                                data-full-description="<?php echo $description; ?>" data-suggestion="<?php echo $suggestion; ?>"
                                data-original="<?php echo $original; ?>">

                                <td><?php echo $date; ?></td>
                                <td><a href="<?php echo $comicLink; ?>" target="_blank"><?php echo $comicId; ?></a></td>
                                <td><?php echo $type; ?></td>
                                <td><?php echo $submitter; ?></td>
                                <td><?php echo $shortDesc; ?></td>
                                <td class="actions-cell">
                                    <form action="<?php echo basename($_SERVER['PHP_SELF']); ?>?<?php echo http_build_query($_GET); ?>" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="report_id" value="<?php echo $reportId; ?>">

                                        <button type="button" class="button detail-button" title="Details anzeigen">
                                            <i class="fas fa-info-circle"></i>
                                        </button>

                                        <?php if ($status === 'open') : ?>
                                            <button type="submit" name="action" value="close" class="button button-green" title="Als erledigt markieren">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="submit" name="action" value="spam" class="button button-orange" title="Als Spam markieren">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php elseif ($status === 'spam') : ?>
                                            <button type="submit" name="action" value="close" class="button button-green" title="Als erledigt markieren">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="submit" name="action" value="reopen" class="button button-blue" title="Wieder öffnen">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php elseif ($status === 'closed') : ?>
                                            <button type="submit" name="action" value="reopen" class="button button-blue" title="Wieder öffnen">
                                                <i class="fas fa-undo"></i>
                                            </button>
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
                $startPage = max(1, $currentPage - 4);
                $endPage = min($totalPages, $currentPage + 4);

                if ($startPage > 1) : ?>
                    <a href="<?php echo getPageLink(1); ?>">1</a>
                    <?php if ($startPage > 2) :
                        ?><span>...</span><?php
                    endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++) : ?>
                    <?php if ($i === $currentPage) : ?>
                        <span class="current-page"><?php echo $i; ?></span>
                    <?php else : ?>
                        <a href="<?php echo getPageLink($i); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages) : ?>
                    <?php if ($endPage < $totalPages - 1) :
                        ?><span>...</span><?php
                    endif; ?>
                    <a href="<?php echo getPageLink($totalPages); ?>"><?php echo $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages) : ?>
                    <a href="<?php echo getPageLink($currentPage + 1); ?>">&rsaquo;</a>
                    <a href="<?php echo getPageLink($totalPages); ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</article>

<!-- FIX: .hidden-by-default statt style="display:none" -->
<div id="report-detail-modal" class="modal admin-modal hidden-by-default" role="dialog" aria-modal="true" aria-labelledby="detail-modal-title">
    <div class="modal-overlay" data-action="close-detail-modal"></div>
    <div class="modal-content report-detail-modal-content modal-advanced-layout">
        <div class="modal-header-wrapper">
            <h2 id="detail-modal-title">Ticket-Details</h2>
            <button class="modal-close" data-action="close-detail-modal" aria-label="Schließen">&times;</button>
        </div>
        <div class="modal-scroll-content">
            <div id="report-detail-content" class="admin-form">
                <!-- FIX: .report-meta-grid statt inline style -->
                <div class="report-meta-grid">
                    <div><strong>Comic-ID:</strong> <a id="detail-comic-link" href="#" target="_blank"><span id="detail-comic-id"></span></a></div>
                    <div><strong>Datum:</strong> <span id="detail-date"></span></div>
                    <div><strong>Einsender:</strong> <span id="detail-submitter"></span></div>
                    <div><strong>Typ:</strong> <span id="detail-type"></span></div>
                </div>

                <h3>Beschreibung</h3>
                <div id="detail-description-container" class="report-text-box">
                    <p id="detail-description"></p>
                </div>

                <div id="detail-transcript-section">
                    <h3>Transkript-Vorschlag (Text-Diff)</h3>
                    <div id="detail-diff-viewer" class="report-text-box diff-box">
                        <p class="loading-text">Diff wird generiert...</p>
                    </div>
                    <h3>Vorschlag (Gerenderte HTML-Ansicht)</h3>
                    <!-- FIX: .mb-15 statt inline style -->
                    <div id="detail-suggestion-html" class="report-text-box mb-15"></div>
                    <h3>Vorschlag (HTML-Quellcode)</h3>
                    <div id="detail-suggestion-code-container">
                        <textarea id="detail-suggestion-code" class="summernote-code-look" readonly></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer-actions">
            <div class="modal-buttons">
                <button type="button" class="button" data-action="close-detail-modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<?php
$jsDiffUrl = Url::getAdminJsUrl('jsdiff.min.js');
$adminReportsJsUrl = Url::getAdminJsUrl('reports.min.js');
?>
<script src="<?php echo htmlspecialchars($jsDiffUrl); ?>" nonce="<?php echo htmlspecialchars($nonce); ?>"></script>
<script src="<?php echo htmlspecialchars($adminReportsJsUrl); ?>" nonce="<?php echo htmlspecialchars($nonce); ?>"></script>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. Auto-Hide für PHP-Statusmeldungen ---
        const mainStatusMsg = document.getElementById('main-status-message');
        if (mainStatusMsg) {
            setTimeout(() => {
                mainStatusMsg.style.transition = "opacity 0.5s ease";
                mainStatusMsg.style.opacity = "0";
                setTimeout(() => {
                    mainStatusMsg.style.display = "none";
                }, 500); // Warten bis Fade-Out fertig ist
            }, 5000); // 5 Sekunden anzeigen
        }

        // --- 2. Bestehende Tabellen-Logik ---
        const table = document.getElementById('reports-table');
        if (table) {
            table.addEventListener('click', function (e) {
                const detailButton = e.target.closest('.detail-button');
                if (!detailButton) return;

                setTimeout(() => {
                    const row = detailButton.closest('tr');
                    if (!row) return;

                    const data = row.dataset;
                    const suggestion = data.suggestion || '';
                    const original = data.original || '';
                    const description = data.fullDescription || 'Keine Beschreibung.';
                    const comicId = data.comicId || 'Unbekannt';
                    const comicLink = document.getElementById('detail-comic-link');

                    document.getElementById('detail-comic-id').textContent = comicId;
                    if (comicLink) {
                        const linkInCell = row.querySelector('td:nth-child(2) a');
                        if (linkInCell) comicLink.href = linkInCell.href;
                        else comicLink.href = '#';
                    }
                    document.getElementById('detail-date').textContent = data.date || 'k.A.';
                    document.getElementById('detail-submitter').textContent = data.submitter || 'k.A.';
                    document.getElementById('detail-type').textContent = data.type || 'k.A.';
                    document.getElementById('detail-description').textContent = description;

                    const htmlDisplay = document.getElementById('detail-suggestion-html');
                    const codeDisplay = document.getElementById('detail-suggestion-code');

                    if (htmlDisplay) htmlDisplay.innerHTML = suggestion || '<em>Kein Vorschlag (HTML).</em>';
                    if (codeDisplay) codeDisplay.value = suggestion || 'Kein Vorschlag (Code).';

                    const diffViewer = document.getElementById('detail-diff-viewer');
                    if (diffViewer) {
                        try {
                            const convertHtmlToText = (html) => {
                                if (html && html.trim().startsWith('<')) {
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = html;
                                    tempDiv.querySelectorAll('p').forEach(p => p.after(document.createTextNode('\n')));
                                    tempDiv.querySelectorAll('br').forEach(br => br.after(document.createTextNode('\n')));
                                    return (tempDiv.textContent || tempDiv.innerText || '').trim();
                                }
                                return (html || '').trim();
                            };

                            const originalText = convertHtmlToText(original);
                            const suggestionText = convertHtmlToText(suggestion);

                            if (typeof Diff !== 'undefined') {
                                const diff = Diff.diffWords(originalText, suggestionText);
                                diffViewer.innerHTML = '';

                                if (!diff || diff.length === 0 || (diff.length === 1 && !diff[0].added && !diff[0].removed)) {
                                    // FIX: Klasse statt style
                                    diffViewer.innerHTML = '<p class="diff-msg-empty">Keine Änderungen am Textinhalt gefunden.</p>';
                                } else {
                                    const fragment = document.createDocumentFragment();
                                    diff.forEach(function (part) {
                                        const tag = part.added ? 'ins' : (part.removed ? 'del' : 'span');
                                        const el = document.createElement(tag);
                                        el.appendChild(document.createTextNode(part.value));
                                        fragment.appendChild(el);
                                    });
                                    diffViewer.appendChild(fragment);
                                }
                            } else {
                                // FIX: Klasse statt style
                                diffViewer.innerHTML = '<p class="loading-text diff-msg-error">Fehler: jsDiff-Bibliothek (Diff) nicht gefunden.</p>';
                            }
                        } catch (err) {
                            console.error('Fehler beim Erstellen des Diffs:', err);
                            // FIX: Klasse statt style
                            diffViewer.innerHTML = '<p class="loading-text diff-msg-error">Fehler beim Erstellen des Diffs.</p>';
                        }
                    }
                }, 10);
            });
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
