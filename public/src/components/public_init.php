<?php
/**
 * Zentrales Initialisierungsskript für alle öffentlichen Seiten.
 *
 * Dieses Skript übernimmt grundlegende Aufgaben und implementiert wichtige,
 * universelle Sicherheitsmaßnahmen, die auf jeder Seite der Webseite gelten sollen.
 * - Starten und sicheres Konfigurieren der PHP-Session.
 * - Setzen von strikten HTTP-Sicherheits-Headern.
 * - Generierung einer einmaligen Nonce für die Content-Security-Policy (CSP) zum Schutz vor XSS.
 * 
 * @file      /src/components/public_init.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

// === DEBUG-MODUS STEUERUNG ===
// Kann in der aufrufenden Datei VOR dem Include gesetzt werden.
$debugMode = $debugMode ?? false;

require_once __DIR__ . '/../../../../twokinds_src/configLoader.php';
if ($debugMode) {
    error_log("DEBUG (public_init.php): CONFIG_PATH = " . CONFIG_PATH);
}

// Setzt das maximale Ausführungszeitlimit für das Skript.
// set_time_limit(300);


// --- 1. Strikte Session-Konfiguration (für alle Seiten) ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// --- 2. Dynamische Basis-URL Bestimmung ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// __DIR__ ist /src/components, also gehen wir zwei Ebenen hoch zum Anwendungs-Root.
$appRootAbsPath = str_replace('\\', '/', dirname(dirname(__DIR__)));
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
$subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);
if (!empty($subfolderPath) && $subfolderPath !== '/') {
    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
} elseif (empty($subfolderPath)) {
    $subfolderPath = '/';
}
$baseUrl = $protocol . $host . $subfolderPath;

if ($debugMode) {
    error_log("DEBUG (public_init.php): Basis-URL bestimmt: " . $baseUrl);
}


// --- 3. Universelle Sicherheits-Header & CSP mit Nonce ---
$nonce = bin2hex(random_bytes(16));

// Content-Security-Policy (CSP)
$csp = [
    'default-src' => ["'self'"],
    'script-src' => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com", "https://cdn.twokinds.keenspot.com"],
    'style-src' => ["'self'", "'nonce-{$nonce}'", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://cdn.twokinds.keenspot.com", "https://fonts.googleapis.com"],
    'font-src' => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com", "https://cdn.twokinds.keenspot.com"],
    // KORREKTUR: Fehlende Domains für Lizenzbilder und tkbutton hinzugefügt
    'img-src' => ["'self'", "data:", "https://placehold.co", "https://cdn.twokinds.keenspot.com", "twokindscomic.com", "https://www.2kinds.com", "https://i.creativecommons.org", "https://licensebuttons.net"],
    'connect-src' => ["'self'", "https://cdn.twokinds.keenspot.com", "https://region1.google-analytics.com"],
    'object-src' => ["'none'"],
    'frame-ancestors' => ["'self'"],
    'base-uri' => ["'self'"],
    'form-action' => ["'self'"],
];
$cspHeader = '';
foreach ($csp as $directive => $sources) {
    $cspHeader .= $directive . ' ' . implode(' ', $sources) . '; ';
}
header("Content-Security-Policy: " . trim($cspHeader));

// Weitere Sicherheits-Header
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");

?>