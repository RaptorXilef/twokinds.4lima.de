<?php
/**
 * @file      ROOT/public/api/submit_report.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 *
 * @description Öffentlicher API-Endpunkt zum Empfangen von Fehlermeldungen (Reports) von der Comic-Seite.
 * Nimmt JSON-POST-Daten entgegen, validiert sie, prüft auf Rate-Limiting/Honeypot
 * und speichert die Meldung in data/comic_reports.json.
 */

// === 1. ZENTRALE INITIALISIERUNG & KONFIGURATION ===
// Lädt die Pfad-Konstanten und die Path-Klasse.
require_once __DIR__ . '/../../src/components/load_config.php';

// === 2. KONSTANTEN & VARIABLEN ===
$debugMode = $debugMode ?? false;
define('RATE_LIMIT_COUNT', 5); // Max 5 Meldungen...
define('RATE_LIMIT_WINDOW', 600); // ...innerhalb von 10 Minuten (600 Sekunden).
$reportsFilePath = Path::getDataPath('comic_reports.json');
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$clientIpHash = hash('sha256', $clientIp); // IP-Adresse hashen

// === 3. HELFERFUNKTIONEN ===

/**
 * Sendet eine JSON-Antwort und beendet das Skript.
 * @param bool $success Erfolg oder Misserfolg
 * @param string $message Die Nachricht an den Benutzer
 * @param int $statusCode HTTP-Statuscode (z.B. 400, 405, 429, 500)
 */
function sendJsonResponse(bool $success, string $message, int $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

/**
 * Liest die JSON-Datei sicher (mit Sperre).
 * @param string $path Pfad zur Datei
 * @return array Die dekodierten Daten
 */
function loadJsonWithLock(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $handle = fopen($path, 'r');
    if (!$handle) {
        return [];
    }
    flock($handle, LOCK_SH); // Shared lock für das Lesen
    $content = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Schreibt die JSON-Datei sicher (mit exklusiver Sperre).
 * @param string $path Pfad zur Datei
 * @param array $data Die zu schreibenden Daten
 * @return bool Erfolg
 */
function saveJsonWithLock(string $path, array $data): bool
{
    $handle = fopen($path, 'c+'); // 'c+' öffnet zum Lesen/Schreiben, erstellt Datei, wenn nicht existent, scheitert nicht, wenn existent.
    if (!$handle) {
        return false;
    }
    if (flock($handle, LOCK_EX)) { // Exklusive Sperre
        ftruncate($handle, 0); // Inhalt löschen
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN); // Sperre freigeben
    }
    fclose($handle);
    return true;
}

// === 4. SKRIPT-LOGIK ===

// --- A. Nur POST-Requests erlauben ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Methode nicht erlaubt.', 405);
}

// --- B. JSON-Body einlesen ---
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    sendJsonResponse(false, 'Ungültige Anfrage (JSON erwartet).', 400);
}

// --- C. Honeypot-Prüfung ---
if (!empty($input['report_honeypot'])) {
    // Bot gefangen. Wir tun so, als wäre es erfolgreich, um den Bot nicht zu alarmieren.
    // Wir loggen dies aber ggf. oder speichern es als 'spam' (optional, hier einfacher Exit).
    sendJsonResponse(true, 'Meldung erfolgreich übermittelt.', 200);
}

// --- D. Datenvalidierung ---
$comicId = $input['comic_id'] ?? null;
$reportType = $input['report_type'] ?? null;
$description = $input['report_description'] ?? '';
$suggestion = $input['report_transcript_suggestion'] ?? '';
$original = $input['report_transcript_original'] ?? ''; // Das Original für den Diff
$submitterName = $input['report_name'] ?? 'Anonym';

// Typ muss einer der erwarteten Werte sein
$validTypes = ['transcript', 'image', 'other'];
if (empty($comicId) || empty($reportType) || !in_array($reportType, $validTypes)) {
    sendJsonResponse(false, 'Fehlende oder ungültige Daten (Comic-ID und Typ sind Pflichtfelder).', 400);
}

// Wenn Typ "Transkript" ist, MUSS entweder eine Beschreibung ODER ein Vorschlag vorhanden sein.
if ($reportType === 'transcript' && empty($description) && empty($suggestion)) {
    sendJsonResponse(false, 'Bitte gib eine Beschreibung oder einen Transkript-Vorschlag an.', 400);
}
// Wenn Typ nicht "Transkript" ist, MUSS eine Beschreibung vorhanden sein.
if ($reportType !== 'transcript' && empty($description)) {
    sendJsonResponse(false, 'Bitte gib eine Fehlerbeschreibung an.', 400);
}

// --- E. Rate-Limiting ---
$reports = loadJsonWithLock($reportsFilePath);
$now = time();
$userReportCount = 0;

foreach ($reports as $report) {
    if (($report['ip_hash'] ?? '') === $clientIpHash) {
        $reportTime = strtotime($report['date'] ?? 0);
        if ($reportTime > ($now - RATE_LIMIT_WINDOW)) {
            $userReportCount++;
        }
    }
}

if ($userReportCount >= RATE_LIMIT_COUNT) {
    sendJsonResponse(false, 'Du hast das Limit für Meldungen erreicht. Bitte versuche es später noch einmal.', 429);
}

// --- F. Datenbereinigung & Erstellung ---
// Bereinige alle Benutzereingaben
$cleanSubmitterName = htmlspecialchars(strip_tags($submitterName), ENT_QUOTES, 'UTF-8');
$cleanDescription = htmlspecialchars(strip_tags($description), ENT_QUOTES, 'UTF-8');
$cleanSuggestion = htmlspecialchars(strip_tags($suggestion), ENT_QUOTES, 'UTF-8');
$cleanOriginal = htmlspecialchars(strip_tags($original), ENT_QUOTES, 'UTF-8'); // Auch Original bereinigen

$newReport = [
    'id' => uniqid('report_', true),
    'comic_id' => $comicId, // Comic-ID wird als sicher angenommen, da sie aus dem Modal-Datenattribut stammt
    'date' => gmdate('c'), // ISO 8601 Zeitstempel (UTC)
    'status' => 'open',
    'ip_hash' => $clientIpHash,
    'submitter_name' => $cleanSubmitterName,
    'report_type' => $reportType,
    'description' => $cleanDescription,
    'transcript_suggestion' => $cleanSuggestion,
    'transcript_original' => $cleanOriginal // Speichere das Original für den Diff
];

// --- G. Speichern ---
$reports[] = $newReport;
if (saveJsonWithLock($reportsFilePath, $reports)) {
    sendJsonResponse(true, 'Vielen Dank! Deine Meldung wurde erfolgreich übermittelt.', 201);
} else {
    if ($debugMode) {
        error_log("FEHLER [submit_report.php]: Konnte '$reportsFilePath' nicht schreiben (flock/fopen Problem).");
    }
    sendJsonResponse(false, 'Ein interner Serverfehler ist aufgetreten. Die Meldung konnte nicht gespeichert werden.', 500);
}
?>