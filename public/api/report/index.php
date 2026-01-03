<?php

/**
 * ARCHITECT REPORTING ENDPOINT (v1.1.0)
 * Receiving browser-side performance and security reports.
 */

// Absoluter Pfad zum Log-Verzeichnis (außerhalb des öffentlichen Ordners)
$logDir = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . 'logs';
$logFile = $logDir . DIRECTORY_SEPARATOR . 'browser_reports.log';

// Verzeichnis-Sicherung
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Erstelle .htaccess im logs Ordner, falls nicht vorhanden (Sicherheits-Layer)
if (!file_exists($logDir . '/.htaccess')) {
    file_put_contents($logDir . '/.htaccess', "Require all denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');

    if (!empty($json)) {
        $data = json_decode($json, true);
        $entry = [
            't' => date('Y-m-d H:i:s'),
            'ip' => substr(md5($_SERVER['REMOTE_ADDR']), 0, 8),
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'rep' => $data,
        ];

        file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

http_response_code(204);
exit;
