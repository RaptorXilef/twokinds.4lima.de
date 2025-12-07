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
 *  - refactor(Page): Inline-CSS entfernt, Layout auf SCSS-Komponenten umgestellt, Icon-Buttons und Paginierung integriert.
 * */

// === 1. ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === 2. KONSTANTEN & VARIABLEN ===
$pageHeader = 'Fehlermeldungen verwalten';
$reportsFilePath = Path::getDataPath('comic_reports.json');
$archiveFilePath = Path::getDataPath('comic_reports_archive.json');
$message = ''; // Für Statusmeldungen
$messageType = 'info';

// Paginierung: Konstante nutzen (falls nicht definiert, Fallback auf 50)
$itemsPerPage = defined('COMIC_PAGES_PER_PAGE') ? COMIC_PAGES_PER_PAGE : 50;

// === 3. HELFERFUNKTIONEN (mit flock) ===

/**
 * Liest eine JSON-Datei sicher (mit Sperre).
 * Enthält Fallback-Mechanismen, falls flock fehlschlägt.
 */
function loadJsonWithLock(string $path, bool $debugMode): array
{
    if (!file_exists($path)) {
        if ($debugMode) {
            error_log("loadJsonWithLock: Datei nicht gefunden: $path");
        }
        return [];
    }

    $handle = fopen($path, 'r');
    if (!$handle) {
        if ($debugMode) {
            error_log("loadJsonWithLock: Konnte Datei nicht öffnen: $path");
        }
        return [];
    }

    $content = '';
    // Versuche Shared Lock
    if (flock($handle, LOCK_SH)) {
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
    } else {
        // Fallback: Dirty Read, falls Lock nicht möglich (besser als leere Seite)
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
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode) {
            error_log("JSON Decode Error in $path: " . json_last_error_msg());
        }
        return [];
    }
    return is_array($data) ? $data : [];
}

/**
 * Schreibt eine JSON-Datei sicher (mit exklusiver Sperre).
 */
function saveJsonWithLock(string $path, array $data, bool $debugMode): bool
{
    $handle = fopen($path, 'c+'); // 'c+' zum Lesen/Schreiben, Zeiger am Anfang
    if (!$handle) {
        if ($debugMode) {
            error_log("Failed to open $path for writing.");
        }
        return false;
    }

    if (flock($handle, LOCK_EX)) {
        ftruncate($handle, 0); // Datei leeren
        rewind($handle);       // Zeiger zurücksetzen
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
    } else {
        if ($debugMode) {
            error_log("Failed to get exclusive lock for $path.");
        }
        fclose($handle);
        return false;
    }
    fclose($handle);
    return true;
}

/**
 * Verschiebt/Ändert einen Report-Status.
 * Sperrt beide Dateien (Reports & Archiv) gleichzeitig, um Datenintegrität zu sichern.
 */
function moveReport(string $reportId, string $action, string $reportsPath, string $archivePath, bool $debugMode): bool
{
    $handleReports = fopen($reportsPath, 'c+');
    $handleArchive = fopen($archivePath, 'c+');

    if (!$handleReports || !$handleArchive) {
        if ($debugMode) {
            error_log("Konnte nicht beide Report-Dateien öffnen.");
        }
        if ($handleReports) {
            fclose($handleReports);
        }
        if ($handleArchive) {
            fclose($handleArchive);
        }
        return false;
    }

    // Versuche beide zu sperren
    if (!flock($handleReports, LOCK_EX) || !flock($handleArchive, LOCK_EX)) {
        if ($debugMode) {
            error_log("Konnte nicht beide Report-Dateien sperren (LOCK_EX).");
        }
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

    // --- Kritischer Bereich ---

    // Daten einlesen
    rewind($handleReports);
    $contentR = stream_get_contents($handleReports);
    $reports = !empty($contentR) ? json_decode($contentR, true) : [];
    if (!is_array($reports)) {
        $reports = [];
    }

    rewind($handleArchive);
    $contentA = stream_get_contents($handleArchive);
    $archive = !empty($contentA) ? json_decode($contentA, true) : [];
    if (!is_array($archive)) {
        $archive = [];
    }

    $reportFound = false;
    $reportData = null;
    $targetList = null;
    $newStatus = '';

    // Suche in Reports
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

    // Suche in Archiv (falls nicht gefunden)
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

        // Hinzufügen zur Zielliste
        if (($targetList === 'archive') || ($targetList === null && $action === 'close')) {
            $archive[] = $reportData;
        } else {
            $reports[] = $reportData;
        }

        // Indizes neu ordnen und speichern
        $reports = array_values($reports);
        $archive = array_values($archive);

        ftruncate($handleReports, 0);
        rewind($handleReports);
        fwrite($handleReports, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        ftruncate($handleArchive, 0);
        rewind($handleArchive);
        fwrite($handleArchive, json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $success = true;
    }

    // Sperren aufheben
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
        $success = moveReport($_POST['report_id'], $action, $reportsFilePath, $archiveFilePath, $debugMode);

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

// Laden mit robustem Fallback
$allReports = loadJsonWithLock($sourcePath, $debugMode);

// Filtern
$reports = array_filter($allReports, function ($r) use ($filterStatus, $filterComicId, $filterSubmitter) {
    // Status Filter
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

    // Text Filter
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
    usort($reports, fn($a, $b) => strtotime($b['date'] ?? 0) <=> strtotime($a['date'] ?? 0)); // Neueste zuerst
} else {
    usort($reports, fn($a, $b) => strtotime($a['date'] ?? 0) <=> strtotime($b['date'] ?? 0)); // Älteste zuerst
}

// Paginierung Berechnen
$totalItems = count($reports);
$totalPages = ceil($totalItems / $itemsPerPage);

// Korrektur falls Seite out of bounds ist
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}
if ($totalPages == 0) {
    $currentPage = 1;
}

// Slice für die aktuelle Ansicht
$displayedReports = array_slice($reports, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);

// Helper für Pagination-Links
function getPageLink($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// === 7. SEITE RENDERN ===
require_once Path::getPartialTemplatePath('header.php');
?>

<div class="admin-content">

    <?php if ($message) : ?>
        <div class="status-message status-<?php echo $messageType; ?>" style="display:block;">
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

    <div class="sitemap-table-container">
        <table class="admin-table data-table reports-table" id="reports-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Comic-ID</th>
                    <th>Typ</th>
                    <th>Einsender</th>
                    <th>Beschreibung (gekürzt)</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($displayedReports)) : ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Keine Meldungen für die aktuellen Filter gefunden.</td>
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

                        $shortDesc = mb_strlen($description) > 75 ? mb_substr($description, 0, 75) . '...' : $description;
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

<div id="report-detail-modal" class="modal admin-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="detail-modal-title">
    <div class="modal-overlay" data-action="close-detail-modal"></div>
    <div class="modal-content report-detail-modal-content">
        <button class="modal-close" data-action="close-detail-modal" aria-label="Schließen">&times;</button>
        <h2 id="detail-modal-title">Ticket-Details</h2>
        <div id="report-detail-content" class="admin-form">
            <p><strong>Comic-ID:</strong> <a id="detail-comic-link" href="#" target="_blank"><span id="detail-comic-id"></span></a></p>
            <p><strong>Datum:</strong> <span id="detail-date"></span></p>
            <p><strong>Einsender:</strong> <span id="detail-submitter"></span></p>
            <p><strong>Typ:</strong> <span id="detail-type"></span></p>

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
                <div id="detail-suggestion-html" class="report-text-box transcript-display-box"></div>
                <h3>Vorschlag (HTML-Quellcode)</h3>
                <div id="detail-suggestion-code-container" class="report-text-box">
                    <textarea id="detail-suggestion-code" rows="8" readonly style="width: 100%; box-sizing: border-box;"></textarea>
                </div>
            </div>

            <div class="modal-buttons" style="margin-top: 20px;">
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

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
