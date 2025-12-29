<?php

/**
 * Zentrales Initialisierungsskript für Admin-Seiten (v5.0.0 - Final Perfection).
 *
 * Sicherheits-Architektur:
 * 1. Content-Security-Policy (CSP): Schutz vor XSS durch Nonces und Whitelisting.
 * 2. Session-Hardening: Fingerprinting (IP-Segment + UA), Regeneration & Strict Cookies.
 * 3. Zombie-Protection: Aktive Cookie-Vernichtung im Browser bei Logout/Ablauf.
 * 4. Multi-Tab-Sync: Globale Pfad-Erkennung für nahtlose Tab-Übergänge.
 *
 * @file      ROOT/src/components/admin/init_admin.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 2.0.0 - 4.0.0
 *    SICHERHEIT & SESSION-MANAGEMENT
 *    - Wiedereinführung von Session-Fingerprinting zur Erhöhung der Sicherheit.
 *    - Verlagerung des `session_save_path` in ein geschütztes Verzeichnis (`secret/sessions`).
 *
 *    BUGFIXES & STABILITÄT
 *    - Behebung von Login-Problemen (GitHub #76) durch Entfernung des problematischen 'domain'-Parameters.
 *    - Korrektur der Logout-Logik: Entfernung von `ob_end_clean()` garantiert das Senden des Lösch-Headers.
 *
 *    ARCHITEKTUR
 *    - Umstellung auf die dynamische Path-Helfer-Klasse.
 *
 * @since 5.0.0
 * - fix(Session): Session-Rotation (ID-Erneuerung) von Timeout entkoppelt (eigener Timestamp).
 * - fix(UX): Fehlender Logout-Grund bei abgelaufener Session in Block 7 ergänzt (Cookie-Check).
 * - feat(API): Korrekte JSON-Antworten (401) für AJAX-Calls bei Session-Ende statt HTML-Redirects.
 * - feat(JS): Übergabe der Timeout-Konstanten an JavaScript via globalem window-Objekt.
 * - fix(Stability): PHP-Timeout um 30 Sekunden verlängert ("Grace Period"), damit JS-Logout-Requests nicht in eine abgelaufene Session laufen.
 * - fix(Stability): Explizites Setzen von `session.gc_maxlifetime` passend zum Timeout.
 * - fix(Security): `verify_csrf_token` erkennt Logout-Aktionen nun automatisch als "fehlertolerant", um White-Screen-Errors bei abgelaufener Session zu verhindern.
 *
 * - fix(Session): Zombie-Cookie-Vernichtung integriert.
 * - fix(Logic): Timeout-Redirect auf index.php deaktiviert, um POST-Requests nicht zu unterbrechen.
 *
 * - Zusammenführung von Session-Stabilität und High-Security Headern.
 * - Optimierte CSP für Summernote-Support und Google Analytics.
 * - Wiederherstellung der automatischen Verzeichniserstellung
 */

// Der Dateiname des aufrufenden Skripts wird für die dynamische Debug-Meldung verwendet.
$callingScript = basename($_SERVER['PHP_SELF']);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// 1. Zentrale Konfiguration laden
require_once __DIR__ . '/../load_config.php';

$isLoginPage = ($callingScript === 'index.php');

// --- SESSION-PFAD & ORDNER-CHECK ---
try {
    $sessionSavePath = Path::getSecretPath('sessions');
    if (!is_dir($sessionSavePath)) {
        // Erstellt den Ordner mit rwx für den Besitzer (PHP)
        if (!mkdir($sessionSavePath, 0755, true)) {
            error_log("FEHLER: Session-Ordner konnte nicht erstellt werden: " . $sessionSavePath);
        }
    }
    if (is_dir($sessionSavePath)) {
        session_save_path($sessionSavePath);
    }
} catch (Exception $e) {
    error_log("FEHLER beim Session-Pfad: " . $e->getMessage());
}

// --- HILFSFUNKTION: SESSION-VERNICHTUNG ---
/**
 * Hilfsfunktion zur vollständigen Zerstörung der Session (Server & Browser).
 */
if (!function_exists('destroy_admin_session')) {
    /**
     * Eliminiert die Session auf dem Server und erzwingt das Löschen des Browser-Cookies.
     */
    function destroy_admin_session()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_destroy();
    }
}

// --- 2. SICHERHEITS-HEADER & CSP ---
// Einmalige Nonce für diesen Request generieren
$nonce = bin2hex(random_bytes(16));

$csp = [
    'default-src' => ["'self'"],
    'script-src'  => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com"],
    'style-src'   => ["'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net", "https://fonts.googleapis.com", "https://cdnjs.cloudflare.com"],
    'font-src'    => ["'self'", "data:", "https://cdn.jsdelivr.net", "https://fonts.gstatic.com", "https://cdnjs.cloudflare.com"],
    'img-src'     => ["'self'", "data:", "https://www.googletagmanager.com", "https://cdn.twokinds.keenspot.com", "https://twokindscomic.com", "https://placehold.co"],
    'connect-src' => ["'self'", "https://cdn.jsdelivr.net", "https://*.google-analytics.com"],
    'object-src'  => ["'none'"],
    'frame-ancestors' => ["'self'"],
    'base-uri'    => ["'self'"],
    'form-action' => ["'self'"],
];

$cspHeader = "";
foreach ($csp as $directive => $sources) {
    $cspHeader .= $directive . " " . implode(" ", $sources) . "; ";
}

header("Content-Security-Policy: " . trim($cspHeader));
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

// --- 3. SESSION-KONFIGURATION ---
// PHP Garbage Collection Zeit mit dem Timeout synchronisieren
ini_set('session.gc_maxlifetime', (string)(SESSION_TIMEOUT_SECONDS + 60));

session_set_cookie_params([
    'lifetime' => 0,       // Session-Cookie erlischt beim Schließen des Browsers
    'path'     => '/',     // Wichtig für Multi-Tab Erkennung
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,    // Schutz vor Cookie-Diebstahl via JS
    'samesite' => 'Strict', // Schutz vor CSRF
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 4. SESSION PROTECTION (Rotation & Fingerprint) ---
// Session-ID regelmäßig erneuern (Schutz vor Fixation)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
}
// Nutzt SESSION_REGENERATION_SECOUNDS aus deiner Config
$regenTime = defined('SESSION_REGENERATION_SECOUNDS') ? SESSION_REGENERATION_SECOUNDS : 900;
if (time() - $_SESSION['last_regeneration'] > $regenTime) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Fingerprint aus User-Agent und IP-Netzsegment (Oktett 1-3)
$ipSegment = substr($_SERVER['REMOTE_ADDR'], 0, (strrpos($_SERVER['REMOTE_ADDR'], '.') ?: 0));
$sessionIdentifier = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . $ipSegment);

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Check 1: Fingerprint (Hijacking-Schutz)
    if (!isset($_SESSION['session_fingerprint']) || $_SESSION['session_fingerprint'] !== $sessionIdentifier) {
        destroy_admin_session();
        header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=session_hijacked');
        exit;
    }

    // Check 2: Timeout (Inaktivitäts-Schutz)
    $phpTimeout = SESSION_TIMEOUT_SECONDS + 30; // 30s Grace Period für AJAX/JS
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $phpTimeout)) {
        destroy_admin_session();
        if (defined('IS_API_CALL') && IS_API_CALL === true) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'session_expired']);
            exit;
        }
        header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=session_expired');
        exit;
    }

    // Aktivität nur bei echtem Seitenaufruf erneuern, nicht bei Logout-Request
    if (!(isset($_GET['action']) && $_GET['action'] === 'logout')) {
        $_SESSION['last_activity'] = time();
    }
}

// --- 5. CSRF-SCHUTZ ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Validiert das CSRF-Token für POST, GET oder AJAX-Header.
     */
    function verify_csrf_token($isLogoutContext = false)
    {
        $token = $_POST['csrf_token'] ?? $_GET['token'] ?? null;

        // AJAX Fallback
        if (!$token && function_exists('getallheaders')) {
            $headers = getallheaders();
            $token = $headers['X-Csrf-Token'] ?? null;
        }

        // Logout ist fehlertolerant (falls Session bereits weg)
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            $isLogoutContext = true;
        }

        if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return (bool)$isLogoutContext;
        }
        return true;
    }
}

// --- 6. LOGOUT-LOGIK ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $csrfValid = verify_csrf_token(true);
    destroy_admin_session();
    $reason = (isset($_GET['timeout']) && $_GET['timeout'] === 'true') || !$csrfValid ? 'session_expired' : 'logout';
    header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=' . $reason);
    exit;
}

// --- 7. GATEKEEPER FÜR INTERNE SEITEN ---
if (!$isLoginPage) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Falls ein Cookie da ist, aber die Session-Daten fehlen -> Cleanup!
        if (isset($_COOKIE[session_name()])) {
            destroy_admin_session();
            header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=session_expired');
        } else {
            header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php');
        }
        exit;
    }

    // JS-Brücke: Übergabe der Timeout-Konfiguration an session_timeout.js
    if (!defined('IS_API_CALL') || IS_API_CALL !== true) {
        echo '<script nonce="' . htmlspecialchars($nonce) . '">';
        echo 'window.sessionConfig = {
                timeoutSeconds: ' . (int)SESSION_TIMEOUT_SECONDS . ',
                warningSeconds: ' . (int)SESSION_WARNING_SECONDS . '
              };';
        echo '</script>';
    }
}
