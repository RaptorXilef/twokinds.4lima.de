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
 */

// Der Dateiname des aufrufenden Skripts wird für die dynamische Debug-Meldung verwendet.
$callingScript = basename($_SERVER['PHP_SELF']);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Lädt die zentrale Konfiguration, Konstanten und die Path-Klasse.
require_once __DIR__ . '/../load_config.php';

if ($debugMode) {
    error_log("DEBUG: init_admin.php wird von {$callingScript} eingebunden.");
    error_log("DEBUG (config_loader): Basis-URL bestimmt: " . (defined('DIRECTORY_PUBLIC_URL') ? DIRECTORY_PUBLIC_URL : 'NICHT DEFINIERT'));
}

// Fallback für Konstanten, falls config_main.php älter ist
if (!defined('SESSION_TIMEOUT_SECONDS')) {
    define('SESSION_TIMEOUT_SECONDS', 600);
}
if (!defined('SESSION_WARNING_SECONDS')) {
    define('SESSION_WARNING_SECONDS', 60);
}

// --- Eigener Session-Speicherpfad ---
// Wir nutzen die Path-Klasse, um einen Pfad im 'secret' (nicht-öffentlichen) Verzeichnis zu definieren.
// Dieser Ordner muss für den PHP-Prozess beschreibbar sein.
try {
    $sessionSavePath = Path::getSecretPath('sessions');

    // Sicherstellen, dass das Verzeichnis existiert
    if (!is_dir($sessionSavePath)) {
        // Versuche, es rekursiv zu erstellen.
        // 0700 = Nur der Eigentümer (PHP-Prozess) darf lesen/schreiben/ausführen.
        if (!mkdir($sessionSavePath, 0755, true)) { // Vorzugsweise 0700, bei Shared-Hosting ist 0755 aber die zuverlässigere Wahl.
            error_log("FEHLER: Konnte das benutzerdefinierte Session-Verzeichnis nicht erstellen: " . $sessionSavePath);
        } else {
            // Verzeichnis erfolgreich erstellt, setze den Pfad
            session_save_path($sessionSavePath);
            if ($debugMode) {
                error_log("DEBUG: Eigenes Session-Verzeichnis erstellt und Pfad gesetzt auf: " . $sessionSavePath);
            }
        }
    } else {
        // Verzeichnis existiert bereits, setze den Pfad
        session_save_path($sessionSavePath);
        if ($debugMode) {
            error_log("DEBUG: Eigenen Session-Pfad gesetzt auf: " . $sessionSavePath);
        }
    }
} catch (Exception $e) {
    error_log("FEHLER beim Setzen des Session-Pfads: " . $e->getMessage());
}
// --- ENDE: Eigener Session-Speicherpfad ---

ob_start();

// --- 1. SICHERHEITS-HEADER & CSP MIT NONCE ---
$nonce = bin2hex(random_bytes(16));
$csp = [
    'default-src' => ["'self'"],
    'script-src' => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com", "https://placehold.co"],
    'style-src' => ["'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net", "https://fonts.googleapis.com", "https://code.jquery.com/", "https://cdnjs.cloudflare.com"], // 'unsafe-inline' für Summernote
    'font-src' => ["'self'", "data:", "https://cdn.jsdelivr.net", "https://fonts.gstatic.com", "https://cdnjs.cloudflare.com", "https://twokinds.4lima.de"], // 'data:' für Summernote
    'img-src' => ["'self'", "data:", "https://www.googletagmanager.com", "https://cdn.twokinds.keenspot.com", "https://twokindscomic.com", "https://placehold.co"],
    'connect-src' => ["'self'", "https://cdn.jsdelivr.net", "https://cdn.twokinds.keenspot.com", "https://twokindscomic.com", "https://*.google-analytics.com"],
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
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");

// --- 2. SESSION-KONFIGURATION ---
// TIMING FIX: Wir stellen sicher, dass PHP die Session nicht vor unserem Timeout löscht.
// Wir addieren 10 Sekunden Puffer zur Garbage Collection Zeit.
ini_set('session.gc_maxlifetime', (string)(SESSION_TIMEOUT_SECONDS + 10));

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 3. SESSION PROTECTION ---
// SCHUTZ VOR SESSION FIXATION UND HIJACKING
// Eigenen Timestamp für Regeneration nutzen, damit es unabhängig vom Logout-Timeout ist.
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
}
$regenTime = defined('SESSION_REGENERATION_SECOUNDS') ? SESSION_REGENERATION_SECOUNDS : 900; // Alle 15 Minuten ID erneuern
if (time() - $_SESSION['last_regeneration'] > $regenTime) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Robuster Session-Fingerabdruck (User-Agent + IP-Netzwerk)
$sessionIdentifier = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . (substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.'))));
if (isset($_SESSION['session_fingerprint'])) {
    if ($_SESSION['session_fingerprint'] !== $sessionIdentifier) {
        error_log("SECURITY ALERT: Session-Fingerabdruck hat sich geändert. Möglicher Hijacking-Versuch von IP: " . $_SERVER['REMOTE_ADDR']);
        session_unset();
        session_destroy();

        // API Check
        if (defined('IS_API_CALL') && IS_API_CALL === true) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'session_hijacked', 'redirect' => 'index.php?reason=session_hijacked']);
            exit;
        }

        header('Location: index.php?reason=session_hijacked');
        exit;
    }
} else {
    $_SESSION['session_fingerprint'] = $sessionIdentifier;
}

// --- 4. CSRF-SCHUTZ ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($isLogoutContext = false)
    {
        global $debugMode, $callingScript;
        $token = null;

        if (!empty($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (!empty($_GET['token'])) {
            $token = $_GET['token'];
        } else {
            $headers = getallheaders();
            if (isset($headers['X-Csrf-Token'])) {
                $token = $headers['X-Csrf-Token'];
            }
        }

        if ($debugMode) {
            error_log("DEBUG ({$callingScript}): CSRF-Prüfung. Erhaltener Token: " . ($token ?? 'KEINER') . ". Session-Token: " . $_SESSION['csrf_token']);
        }

        // SAFETY NET: Wenn es ein Logout-Request ist, erzwingen wir den "Fehlertoleranz"-Modus,
        // auch wenn die Funktion versehentlich ohne `true` aufgerufen wurde.
        if (isset($_GET['action']) && $_GET['action'] === 'logout') {
            $isLogoutContext = true;
        }

        if (!isset($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            if ($debugMode) {
                error_log("FEHLER ({$callingScript}): CSRF-Token-Validierung fehlgeschlagen.");
            }

            // FIX: Im Logout-Kontext erlauben wir das Scheitern (Session wahrscheinlich eh weg)
            if ($isLogoutContext) {
                return false;
            }

            $isApiCall = (defined('IS_API_CALL') && IS_API_CALL === true);

            if ($isApiCall) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage (CSRF-Token fehlt oder ist falsch).']);
            } else {
                die('CSRF-Token-Validierung fehlgeschlagen.');
            }
            exit;
        }
        return true;
    }
}

// --- 5. HILFSFUNKTIONEN FÜR EINSTELLUNGS-JSON ---
if (!function_exists('load_settings')) {
    function load_settings(string $filePath, string $key, bool $debugMode): array
    {
        $defaults = ['last_run_timestamp' => null];
        if (!file_exists($filePath)) {
            if ($debugMode) {
                error_log("DEBUG: Einstellungsdatei $filePath nicht gefunden, verwende Standardwerte.");
            }
            return $defaults;
        }
        $allSettings = json_decode(file_get_contents($filePath), true);
        return $allSettings[$key] ?? $defaults;
    }
}

if (!function_exists('save_settings')) {
    function save_settings(string $filePath, string $key, array $data, bool $debugMode): void
    {
        $allSettings = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
        if (!is_array($allSettings)) {
            $allSettings = [];
        }
        $allSettings[$key] = $data;
        file_put_contents($filePath, json_encode($allSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($debugMode) {
            error_log("DEBUG: Einstellungen für Schlüssel '$key' in $filePath gespeichert.");
        }
    }
}

// --- 6. LOGOUT-LOGIK ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $csrfValid = verify_csrf_token(true);
    if ($debugMode) {
        error_log("DEBUG: Logout-Aktion mit gültigem CSRF-Token erkannt.");
    }

    $_SESSION = array();

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
    session_destroy();

    // Grund ermitteln: Expliziter Timeout vom JS oder CSRF-Fehler (Session schon weg)
    if ((isset($_GET['timeout']) && $_GET['timeout'] === 'true') || !$csrfValid) {
        $reason = 'session_expired';
    } else {
        $reason = 'logout';
    }

    header('Location: index.php?reason=' . $reason);
    exit;
}

// --- 7. FINALER LOGIN-CHECK ---
if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        if ($debugMode) {
            error_log("DEBUG: Nicht angemeldet. Redirect zur Login-Seite.");
        }

        // API-Calls sollen JSON zurückgeben, kein HTML-Redirect
        if (defined('IS_API_CALL') && IS_API_CALL === true) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'auth_required', 'redirect' => 'index.php?reason=session_expired']);
            exit;
        }

        if ($debugMode) {
            error_log("DEBUG: Nicht angemeldet. Weiterleitung zur Login-Seite.");
        }

        $redirectUrl = 'index.php';
        if (isset($_COOKIE[session_name()])) {
            $redirectUrl .= '?reason=session_expired';
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    // --- JS-ÜBERGABE ---
    // Wenn wir eingeloggt sind (und nicht auf index.php), geben wir die Config an JS weiter.
    // Dies wird im <head> ausgegeben, wenn init_admin.php vor dem HTML eingebunden wird,
    // oder muss im Layout-Header stehen.
    // Da wir hier direkt Code ausführen, geben wir ein <script> Tag aus, wenn es kein API Call ist.
    if (!defined('IS_API_CALL') || IS_API_CALL !== true) {
        echo '<script nonce="' . htmlspecialchars($nonce) . '">';
        echo 'window.sessionConfig = {';
        echo ' timeoutSeconds: ' . (int)SESSION_TIMEOUT_SECONDS . ',';
        echo ' warningSeconds: ' . (int)SESSION_WARNING_SECONDS;
        echo '};';
        echo '</script>';
    }
}

// --- 8. SESSION-TIMEOUT-LOGIK ---
// TIMING FIX: PHP bekommt 30 Sekunden Puffer.
// Das JS (Timeout aus Config) loggt den User also aus, BEVOR PHP diesen Block erreicht.
$phpTimeout = SESSION_TIMEOUT_SECONDS + 30;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $phpTimeout)) {
    if ($debugMode) {
        error_log("DEBUG: Session abgelaufen (PHP-seitig).");
    }

    session_unset();
    session_destroy();

    // API-Check auch hier
    if (defined('IS_API_CALL') && IS_API_CALL === true) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'session_timeout', 'redirect' => 'index.php?reason=session_expired']);
        exit;
    }

    header("Location: index.php?reason=session_expired");
    exit;
}
$_SESSION['last_activity'] = time();
