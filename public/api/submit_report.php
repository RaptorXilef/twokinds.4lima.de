<?php

/**
 * Beschreibung: Hochstabiler API-Endpunkt für Comic-Fehlermeldungen.
 * Verarbeitet POST-Daten, bereinigt HTML via HTML Purifier und schützt
 * die Anwendung durch globales Error-Handling (Throwable).
 *
 * @file      ROOT/public/api/submit_report.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     5.0.0
 * - Refactoring: Trennung von Funktionsdefinition und Logik-Fluss.
 * - Restore: Alle spezifischen Validierungs- und Rate-Limit-Regeln integriert.
 *
 * Variablen-Index:
 * - $configPath, $autoloadPath: string - Pfade für Systemkomponenten.
 * - $reportsFilePath: string - Pfad zur JSON-Datenbank.
 * - $clientIpHash: string - Fingerabdruck des Nutzers für das Rate-Limiting.
 * - $purifier: HTMLPurifier - Das Sicherheitswerkzeug für HTML-Input.
 */

declare(strict_types=1);

// === 1. SYSTEM-SETUP & HEADER ===
// Wir schalten die HTML-Fehleranzeige ab, da wir Fehler als JSON-Objekt abfangen.
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// === 2. HILFSWERKZEUGE (Definitionen) ===

/**
 * Sendet eine strukturierte JSON-Antwort und terminiert das Skript.
 */
function sendJsonResponse(bool $success, string $message, int $statusCode = 200, ?array $debug = null): void
{
    http_response_code($statusCode);
    echo json_encode(array_filter([
        'success' => $success,
        'message' => $message,
        'debug'   => $debug,
    ]), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lädt die JSON-Datenbank mit einer Lese-Sperre (Shared Lock).
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
    flock($handle, LOCK_SH);
    $content = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    $data = json_decode((string)$content, true);
    return is_array($data) ? $data : [];
}

/**
 * Speichert Daten in der JSON-Datenbank mit Schreib-Sperre (Exclusive Lock).
 */
function saveJsonWithLock(string $path, array $data): bool
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
    }
    fclose($handle);
    return true;
}

// === 3. AUSFÜHRUNGS-LOGIK (Der "Flow") ===

try {
    // --- A. Initialisierung ---
    $configPath = __DIR__ . '/../../src/components/load_config.php';
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';

    if (!file_exists($configPath)) {
        throw new RuntimeException('System-Konfiguration nicht gefunden.');
    }
    if (!file_exists($autoloadPath)) {
        throw new RuntimeException('Vendor-Bibliotheken fehlen (HTML Purifier).');
    }

    require_once $configPath;
    require_once $autoloadPath;

    $debugMode = $debugMode ?? false;
    $reportsFilePath = Path::getDataPath('comic_reports.json');
    $clientIpHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');

    // --- B. Request-Prüfung ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Methode nicht erlaubt.', 405);
    }

    $input = json_decode((string)file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        sendJsonResponse(false, 'Ungültige Anfrage (JSON erwartet).', 400);
    }

    // Honeypot (Bot-Schutz)
    if (!empty($input['report_honeypot'])) {
        sendJsonResponse(true, 'Meldung erfolgreich übermittelt.', 200);
    }

    // --- C. Detaillierte Validierung (Wiederhergestellt) ---
    $comicId      = $input['comic_id'] ?? null;
    $reportType   = $input['report_type'] ?? null;
    $description  = $input['report_description'] ?? '';
    $suggestion   = $input['report_transcript_suggestion'] ?? '';
    $original     = $input['report_transcript_original'] ?? '';
    $submitter    = $input['report_name'] ?? 'Anonym';

    $validTypes = ['transcript', 'image', 'other'];
    if (empty($comicId) || !in_array($reportType, $validTypes)) {
        sendJsonResponse(false, 'Fehlende oder ungültige Pflichtfelder.', 400);
    }

    // Spezialregel Transkript
    if ($reportType === 'transcript' && empty($description) && empty($suggestion)) {
        sendJsonResponse(false, 'Bitte gib eine Beschreibung oder einen Transkript-Vorschlag an.', 400);
    }
    // Spezialregel Bild/Sonstiges
    if ($reportType !== 'transcript' && empty($description)) {
        sendJsonResponse(false, 'Bitte gib eine Fehlerbeschreibung an.', 400);
    }

    // --- D. Rate-Limiting Logik (Wiederhergestellt) ---
    define('RATE_LIMIT_COUNT', 5);
    define('RATE_LIMIT_WINDOW', 600); // 10 Minuten

    $reports = loadJsonWithLock($reportsFilePath);
    $now = time();
    $userReportCount = 0;

    foreach ($reports as $report) {
        if (!(($report['ip_hash'] ?? '') === $clientIpHash)) {
            continue;
        }

        $reportTime = strtotime($report['date'] ?? '0');
        if ($reportTime <= $now - RATE_LIMIT_WINDOW) {
            continue;
        }

        $userReportCount++;
    }

    if ($userReportCount >= RATE_LIMIT_COUNT) {
        sendJsonResponse(false, 'Limit erreicht. Bitte versuche es in 10 Minuten erneut.', 429);
    }

    // --- E. Datenbereinigung ---
    $purifierConfig = HTMLPurifier_Config::createDefault();
    $purifierConfig->set('HTML.Allowed', 'p,b,strong,i,em,br');
    $purifierConfig->set('Cache.DefinitionImpl', null);
    $purifier = new HTMLPurifier($purifierConfig);

    $newReport = [
        'id'                    => uniqid('report_', true),
        'comic_id'              => $comicId,
        'date'                  => gmdate('c'),
        'status'                => 'open',
        'ip_hash'               => $clientIpHash,
        'submitter_name'        => htmlspecialchars(strip_tags($submitter), ENT_QUOTES, 'UTF-8'),
        'report_type'           => $reportType,
        'description'           => htmlspecialchars(strip_tags($description), ENT_QUOTES, 'UTF-8'),
        'transcript_suggestion' => $purifier->purify($suggestion),
        'transcript_original'   => $purifier->purify($original),
    ];

    // --- F. Speichern ---
    $reports[] = $newReport;
    if (!saveJsonWithLock($reportsFilePath, $reports)) {
        throw new RuntimeException('Fehler beim Schreiben der Datenbank-Datei.');
    }

    sendJsonResponse(true, 'Vielen Dank! Deine Meldung wurde erfolgreich übermittelt.', 201);
} catch (\Throwable $e) {
    // Globaler Catch-Block für absolute Stabilität
    $debug = $debugMode ? [
        'error' => $e->getMessage(),
        'file'  => basename($e->getFile()),
        'line'  => $e->getLine(),
    ] : null;

    sendJsonResponse(false, 'Ein interner Serverfehler ist aufgetreten.', 500, $debug);
}
