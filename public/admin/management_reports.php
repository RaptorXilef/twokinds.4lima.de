<?php
/**
 * @file      ROOT/public/admin/management_reports.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 *
 * @description Administrationsseite zur Verwaltung der Fehlermeldungen (Reports).
 * Zeigt Meldungen an, filtert sie und erlaubt Aktionen (Schließen, Spam, Öffnen).
 * Verwendet flock() für alle Dateioperationen.
 */

// === 1. ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === 2. KONSTANTEN & VARIABLEN ===
$pageHeader = 'Fehlermeldungen verwalten';
$reportsFilePath = Path::getDataPath('comic_reports.json');
$archiveFilePath = Path::getDataPath('comic_reports_archive.json');
$message = ''; // Für Statusmeldungen
$messageType = 'info';

// === 3. HELFERFUNKTIONEN (mit flock) ===

/**
 * Liest eine JSON-Datei sicher (mit Sperre).
 */
function loadJsonWithLock(string $path, bool $debugMode): array
{
    if (!file_exists($path))
        return [];
    $handle = fopen($path, 'r');
    if (!$handle)
        return [];
    flock($handle, LOCK_SH); // Shared lock
    $content = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($debugMode)
            error_log("JSON Decode Error in $path: " . json_last_error_msg());
        return [];
    }
    return is_array($data) ? $data : [];
}

/**
 * Schreibt eine JSON-Datei sicher (mit exklusiver Sperre).
 */
function saveJsonWithLock(string $path, array $data, bool $debugMode): bool
{
    $handle = fopen($path, 'c+'); // 'c+' -> atomarer
    if (!$handle) {
        if ($debugMode)
            error_log("Failed to open $path for writing.");
        return false;
    }
    if (flock($handle, LOCK_EX)) {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
    } else {
        if ($debugMode)
            error_log("Failed to get exclusive lock for $path.");
        fclose($handle);
        return false;
    }
    fclose($handle);
    return true;
}

/**
 * Verschiebt/Ändert einen Report-Status.
 * Diese Funktion sperrt BEIDE Dateien, um Integrität zu gewährleisten.
 */
function moveReport(string $reportId, string $action, string $reportsPath, string $archivePath, bool $debugMode): bool
{
    // Beide Dateien öffnen und sperren, um Deadlocks zu vermeiden (immer in derselben Reihenfolge)
    $handleReports = fopen($reportsPath, 'c+');
    $handleArchive = fopen($archivePath, 'c+');

    if (!$handleReports || !$handleArchive) {
        if ($debugMode)
            error_log("Konnte nicht beide Report-Dateien öffnen.");
        if ($handleReports)
            fclose($handleReports);
        if ($handleArchive)
            fclose($handleArchive);
        return false;
    }

    // Exklusive Sperren holen
    if (!flock($handleReports, LOCK_EX) || !flock($handleArchive, LOCK_EX)) {
        if ($debugMode)
            error_log("Konnte nicht beide Report-Dateien sperren (LOCK_EX).");
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

    // --- Sicherer Bereich (beide Dateien gesperrt) ---

    // 1. Daten einlesen (aus den bereits geöffneten Handles)
    rewind($handleReports);
    $reportsContent = stream_get_contents($handleReports);
    $reports = json_decode($reportsContent, true);
    if (!is_array($reports))
        $reports = [];

    rewind($handleArchive);
    $archiveContent = stream_get_contents($handleArchive);
    $archive = json_decode($archiveContent, true);
    if (!is_array($archive))
        $archive = [];

    // 2. Report finden und Aktion ausführen
    $reportFound = false;
    $reportData = null;
    $sourceList = null; // 'reports' oder 'archive'
    $targetList = null; // 'reports' oder 'archive'
    $newStatus = '';

    // In $reports suchen
    foreach ($reports as $key => $report) {
        if ($report['id'] === $reportId) {
            $reportData = $report;
            $sourceList = 'reports';
            if ($action === 'close') {
                $newStatus = 'closed';
                $targetList = 'archive';
            } elseif ($action === 'spam') {
                $newStatus = 'spam';
                $targetList = 'reports';
            } elseif ($action === 'reopen') { // von spam -> open
                $newStatus = 'open';
                $targetList = 'reports';
            }
            unset($reports[$key]); // Aus Quell-Liste entfernen (oder Status aktualisieren)
            $reportFound = true;
            break;
        }
    }

    // Wenn nicht in $reports gefunden, in $archive suchen
    if (!$reportFound) {
        foreach ($archive as $key => $report) {
            if ($report['id'] === $reportId) {
                $reportData = $report;
                $sourceList = 'archive';
                if ($action === 'reopen') { // von closed -> open
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
    if ($reportFound && $reportData && $targetList && $newStatus) {
        $reportData['status'] = $newStatus;

        // In Zielliste einfügen
        if ($targetList === 'reports') {
            $reports[] = $reportData;
        } else {
            $archive[] = $reportData;
        }

        // 3. Beide Dateien zurückschreiben (in die gesperrten Handles)
        // Indizes neu anordnen, um JSON-Array statt Objekt sicherzustellen
        $reports = array_values($reports);
        $archive = array_values($archive);

        ftruncate($handleReports, 0);
        rewind($handleReports);
        fwrite($handleReports, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handleReports);

        ftruncate($handleArchive, 0);
        rewind($handleArchive);
        fwrite($handleArchive, json_encode($archive, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handleArchive);

        $success = true;
    } elseif ($reportFound && $newStatus) { // Für Aktionen ohne Verschieben (z.B. spam, reopen von spam)
        $reportData['status'] = $newStatus;
        $reports[] = $reportData; // Wieder hinzufügen (da oben entfernt)
        $reports = array_values($reports);

        ftruncate($handleReports, 0);
        rewind($handleReports);
        fwrite($handleReports, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handleReports);
        $success = true;
    }


    // --- Ende Sicherer Bereich ---

    // 4. Sperren freigeben und Handles schließen
    flock($handleReports, LOCK_UN);
    fclose($handleReports);
    flock($handleArchive, LOCK_UN);
    fclose($handleArchive);

    return $success;
}


// === 4. POST-AKTIONEN VERARBEITEN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    // CSRF-Token-Prüfung (aus init_admin.php)
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'CSRF-Token-Fehler. Aktion abgebrochen.';
        $messageType = 'red';
    } else {
        $action = $_POST['action'];
        $reportId = $_POST['report_id'];
        $success = false;

        if ($action === 'close' || $action === 'spam' || $action === 'reopen') {
            $success = moveReport($reportId, $action, $reportsFilePath, $archiveFilePath, $debugMode);
        }

        if ($success) {
            $message = "Report erfolgreich ($action).";
            $messageType = 'green';
        } else {
            $message = "Aktion ($action) fehlgeschlagen.";
            $messageType = 'red';
        }

        // Redirect-nach-POST, um Resubmission zu verhindern.
        // Hänge aktuelle GET-Filter und die Statusmeldung an die URL an.
        $queryParams = $_GET;
        $queryParams['message'] = urlencode($message);
        $queryParams['messageType'] = $messageType;
        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?' . http_build_query($queryParams));
        exit;
    }
}

// === 5. GET-PARAMETER (FILTER & STATUSMELDUNGEN) VERARBEITEN ===
$filterStatus = $_GET['status'] ?? 'open';
$filterComicId = $_GET['comic_id'] ?? '';
$filterSubmitter = $_GET['submitter'] ?? '';

// Statusmeldungen vom Redirect abfangen
if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
    $messageType = htmlspecialchars($_GET['messageType'] ?? 'info');
}

// === 6. DATEN LADEN & FILTERN ===
$reports = [];
$isArchive = ($filterStatus === 'closed');

if ($isArchive) {
    $reports = loadJsonWithLock($archiveFilePath, $debugMode);
    // Sortierung: Neueste zuerst (Archiv)
    usort($reports, fn($a, $b) => strtotime($b['date'] ?? 0) <=> strtotime($a['date'] ?? 0));
} else {
    $allReports = loadJsonWithLock($reportsFilePath, $debugMode);

    // Nach Status filtern (open oder spam)
    if ($filterStatus === 'open' || $filterStatus === 'spam') {
        $reports = array_filter($allReports, fn($r) => ($r['status'] ?? 'open') === $filterStatus);
    } else { // Fallback, falls 'all' oder ungültig -> 'open'
        $reports = array_filter($allReports, fn($r) => ($r['status'] ?? 'open') === 'open');
        if ($filterStatus !== 'open')
            $filterStatus = 'open'; // Korrigiere für <select>
    }

    // Sortierung: Älteste zuerst (Offen/Spam)
    usort($reports, fn($a, $b) => strtotime($a['date'] ?? 0) <=> strtotime($b['date'] ?? 0));
}

// Text-Filter anwenden
if (!empty($filterComicId)) {
    $reports = array_filter($reports, fn($r) => str_contains($r['comic_id'] ?? '', $filterComicId));
}
if (!empty($filterSubmitter)) {
    $reports = array_filter($reports, fn($r) => stripos($r['submitter_name'] ?? '', $filterSubmitter) !== false);
}

// === 7. SEITE RENDERN ===
require_once Path::getPartialTemplatePath('header.php');
?>

<div class="admin-content">

    <?php if ($message): ?>
        <div class="status-message status-<?php echo $messageType; ?>">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>

    <!-- Filter-Formular -->
    <form action="<?php echo basename($_SERVER['PHP_SELF']); ?>" method="GET" class="admin-form filter-form">
        <fieldset>
            <legend>Filter</legend>
            <div class="filter-controls">
                <div>
                    <label for="filter-status">Status</label>
                    <select id="filter-status" name="status">
                        <option value="open" <?php echo $filterStatus === 'open' ? 'selected' : ''; ?>>Offen (Älteste
                            zuerst)</option>
                        <option value="spam" <?php echo $filterStatus === 'spam' ? 'selected' : ''; ?>>Spam (Älteste
                            zuerst)</option>
                        <option value="closed" <?php echo $filterStatus === 'closed' ? 'selected' : ''; ?>>
                            Archiv/Geschlossen (Neueste zuerst)</option>
                    </select>
                </div>
                <div>
                    <label for="filter-comic-id">Comic-ID</label>
                    <input type="text" id="filter-comic-id" name="comic_id"
                        value="<?php echo htmlspecialchars($filterComicId); ?>">
                </div>
                <div>
                    <label for="filter-submitter">Einsender</label>
                    <input type="text" id="filter-submitter" name="submitter"
                        value="<?php echo htmlspecialchars($filterSubmitter); ?>">
                </div>
                <button type="submit" class="button">Filtern</button>
            </div>
        </fieldset>
    </form>

    <!-- Report-Tabelle -->
    <table class="admin-table data-table" id="reports-table">
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
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Keine Meldungen für die aktuellen Filter gefunden.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <?php
                    // Daten für die Tabelle und das Modal vorbereiten
                    $reportId = htmlspecialchars($report['id'] ?? '');
                    $comicId = htmlspecialchars($report['comic_id'] ?? 'Unbekannt');
                    $comicLink = Url::getComicPageUrl($comicId . $dateiendungPHP);
                    $date = htmlspecialchars(date('d.m.Y H:i', strtotime($report['date'] ?? 'now')));
                    $type = htmlspecialchars($report['report_type'] ?? 'k.A.');
                    $submitter = htmlspecialchars($report['submitter_name'] ?? 'k.A.');
                    $description = htmlspecialchars($report['description'] ?? '');
                    $suggestion = htmlspecialchars($report['transcript_suggestion'] ?? '');
                    $original = htmlspecialchars($report['transcript_original'] ?? ''); // Für Diff
                    $status = $report['status'] ?? 'open';

                    // Beschreibung kürzen
                    $shortDesc = mb_strlen($description) > 75 ? mb_substr($description, 0, 75) . '...' : $description;
                    if (empty($shortDesc) && !empty($suggestion)) {
                        $shortDesc = '<i>(Nur Transkript-Vorschlag)</i>';
                    }
                    ?>
                    <tr data-report-id="<?php echo $reportId; ?>" data-comic-id="<?php echo $comicId; ?>"
                        data-date="<?php echo $date; ?>" data-type="<?php echo $type; ?>"
                        data-submitter="<?php echo $submitter; ?>" data-status="<?php echo htmlspecialchars($status); ?>"
                        data-full-description="<?php echo $description; // Ungekürzt für Modal ?>"
                        data-suggestion="<?php echo $suggestion; // Für Modal ?>"
                        data-original="<?php echo $original; // Für Modal ?>">

                        <td style="white-space: nowrap;"><?php echo $date; ?></td>
                        <td><a href="<?php echo $comicLink; ?>" target="_blank"><?php echo $comicId; ?></a></td>
                        <td><?php echo $type; ?></td>
                        <td><?php echo $submitter; ?></td>
                        <td><?php echo $shortDesc; ?></td>
                        <td class="actions-cell">
                            <form action="<?php echo basename($_SERVER['PHP_SELF']); ?>?<?php echo http_build_query($_GET); ?>"
                                method="POST" style="display: inline-block; margin-bottom: 5px;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="report_id" value="<?php echo $reportId; ?>">

                                <button type="button" class="button detail-button">Details</button>

                                <?php if ($status === 'open'): ?>
                                    <button type="submit" name="action" value="close" class="button button-green">Schließen</button>
                                    <button type="submit" name="action" value="spam" class="button button-orange">Spam</button>
                                <?php elseif ($status === 'spam'): ?>
                                    <button type="submit" name="action" value="close" class="button button-green">Schließen</button>
                                    <button type="submit" name="action" value="reopen" class="button button-blue">Wieder
                                        öffnen</button>
                                <?php elseif ($status === 'closed'): ?>
                                    <button type="submit" name="action" value="reopen" class="button button-blue">Wieder
                                        öffnen</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal für Detailansicht (wird von JS befüllt) -->
<div id="report-detail-modal" class="modal admin-modal" style="display: none;" role="dialog" aria-modal="true"
    aria-labelledby="detail-modal-title">

    <div class="modal-overlay" data-action="close-detail-modal"></div>

    <div class="modal-content report-detail-modal-content">
        <button class="modal-close" data-action="close-detail-modal" aria-label="Schließen">&times;</button>
        <h2 id="detail-modal-title">Ticket-Details</h2>

        <div id="report-detail-content" class="admin-form">
            <p><strong>Comic-ID:</strong> <a id="detail-comic-link" href="#" target="_blank"><span
                        id="detail-comic-id"></span></a></p>
            <p><strong>Datum:</strong> <span id="detail-date"></span></p>
            <p><strong>Einsender:</strong> <span id="detail-submitter"></span></p>
            <p><strong>Typ:</strong> <span id="detail-type"></span></p>

            <h3>Beschreibung</h3>
            <div id="detail-description-container" class="report-text-box">
                <p id="detail-description"></p>
            </div>

            <div id="detail-transcript-section">
                <h3>Transkript-Vorschlag (Diff-Ansicht)</h3>
                <div id="detail-diff-viewer" class="report-text-box diff-box">
                    <p class="loading-text">Original-Transkript wird geladen...</p>
                </div>

                <h3>Vollständiger Vorschlag (Text)</h3>
                <div id="detail-suggestion-container" class="report-text-box">
                    <textarea id="detail-suggestion" rows="8" readonly style="width: 100%;"></textarea>
                </div>
            </div>

            <div class="modal-buttons" style="margin-top: 20px;">
                <button type="button" class="button" data-action="close-detail-modal">Schließen</button>
            </div>
        </div>
    </div>
</div>

<?php
// JS-Bibliotheken und unser neues Skript laden
// Pfad zur jsDiff-Bibliothek (Annahme, dass sie im assets-Ordner liegt, wie im DOCX erwähnt)
// Ich gehe davon aus, dass du 'diff.min.js' in 'public/assets/js/' ablegen wirst.
$jsDiffUrl = Url::getJsUrl('diff.min.js');
$adminReportsJsUrl = Url::getAdminJsUrl('admin_reports.js'); // true für admin-Ordner
?>
<script src="<?php echo htmlspecialchars($jsDiffUrl); ?>" nonce="<?php echo htmlspecialchars($nonce); ?>"></script>
<script src="<?php echo htmlspecialchars($adminReportsJsUrl); ?>"
    nonce="<?php echo htmlspecialchars($nonce); ?>"></script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>