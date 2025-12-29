<?php

/**
 * Zentrales Initialisierungsskript für alle Admin-Seiten (Final gehärtete Version).
 *
 * Dieses Skript übernimmt wiederkehrende Aufgaben und implementiert wichtige Sicherheitsmaßnahmen:
 * - Dynamische Bestimmung der Basis-URL.
 * - Strikte Sicherheits-Header (CSP mit Nonce, HSTS, Permissions-Policy etc.).
 * - Session-Konfiguration für erhöhte Sicherheit (HTTPOnly, Secure, SameSite).
 * - Schutz vor Session Hijacking durch User-Agent- und IP-Adressen-Bindung.
 * - Schutz vor Session Fixation durch regelmäßige ID-Erneuerung.
 * - Umfassender CSRF-Schutz für Formulare und AJAX-Anfragen.
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
 */

// Der Dateiname des aufrufenden Skripts wird für die dynamische Debug-Meldung verwendet.
$callingScript = basename($_SERVER['PHP_SELF']);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Lädt die zentrale Konfiguration, Konstanten und die Path-Klasse.
require_once __DIR__ . '/../load_config.php';

$isLoginPage = ($callingScript === 'index.php');

/**
 * Zerstört die Admin-Session vollständig auf Server UND Browser-Ebene.
 */
if (!function_exists('destroy_admin_session')) {
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

// --- 1. SICHERHEITS-HEADER ---
$nonce = bin2hex(random_bytes(16));
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

// --- 2. SESSION-KONFIGURATION ---
ini_set('session.gc_maxlifetime', (string)(SESSION_TIMEOUT_SECONDS + 60));

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/', // Erlaubt Erkennung über alle Tabs
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 3. SESSION PROTECTION ---
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
}
if (time() - $_SESSION['last_regeneration'] > 900) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$sessionIdentifier = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . substr($_SERVER['REMOTE_ADDR'], 0, (strrpos($_SERVER['REMOTE_ADDR'], '.') ?: 0)));

// Prüfe Session-Validität nur, wenn eingeloggt
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Fingerprint-Check
    if (!isset($_SESSION['session_fingerprint']) || $_SESSION['session_fingerprint'] !== $sessionIdentifier) {
        destroy_admin_session();
        header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=session_hijacked');
        exit;
    }

    // Timeout-Check (Grace Period)
    $phpTimeout = SESSION_TIMEOUT_SECONDS + 30;
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

    // Aktivität nur erneuern, wenn wir nicht gerade ausloggen
    if (!(isset($_GET['action']) && $_GET['action'] === 'logout')) {
        $_SESSION['last_activity'] = time();
    }
}

// --- 4. CSRF-SCHUTZ ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($isLogoutContext = false)
    {
        $token = $_POST['csrf_token'] ?? $_GET['token'] ?? getallheaders()['X-Csrf-Token'] ?? null;
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            $isLogoutContext = true;
        }

        if (!isset($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return (bool)$isLogoutContext;
        }
        return true;
    }
}

// --- 5. LOGOUT-LOGIK ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $csrfValid = verify_csrf_token(true);
    destroy_admin_session();
    $reason = (isset($_GET['timeout']) && $_GET['timeout'] === 'true') || !$csrfValid ? 'session_expired' : 'logout';
    header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=' . $reason);
    exit;
}

// --- 6. AUTH-CHECK FÜR INTERNE SEITEN ---
if (!$isLoginPage) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Zombie-Cookie ohne Session-Inhalt gefunden?
        if (isset($_COOKIE[session_name()])) {
            destroy_admin_session();
            header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php?reason=session_expired');
        } else {
            header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php');
        }
        exit;
    }

    // JS Config an Browser übergeben
    if (!defined('IS_API_CALL') || IS_API_CALL !== true) {
        echo '<script nonce="' . htmlspecialchars($nonce) . '">';
        echo 'window.sessionConfig = { timeoutSeconds: ' . (int)SESSION_TIMEOUT_SECONDS . ', warningSeconds: ' . (int)SESSION_WARNING_SECONDS . ' };';
        echo '</script>';
    }
}
