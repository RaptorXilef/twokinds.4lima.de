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
 * V2.3: Session-Fingerprinting zur Erhöhung der Sicherheit wieder hinzugefügt.
 */

// Der Dateiname des aufrufenden Skripts wird für die dynamische Debug-Meldung verwendet.
$callingScript = basename($_SERVER['PHP_SELF']);

if (!isset($debugMode)) {
    $debugMode = false;
}

if ($debugMode)
    error_log("DEBUG: admin_init.php wird von {$callingScript} eingebunden.");

ob_start();


// --- 1. Dynamische Basis-URL Bestimmung ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// __DIR__ ist /admin/src/components, also gehen wir drei Ebenen hoch zum Anwendungs-Root.
$appRootAbsPath = str_replace('\\', '/', dirname(dirname(dirname(__DIR__))));
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
$subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);
if (!empty($subfolderPath) && $subfolderPath !== '/') {
    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
} elseif (empty($subfolderPath)) {
    $subfolderPath = '/';
}
$baseUrl = $protocol . $host . $subfolderPath;

// --- Server-Root-Pfad bestimmen ---
$projectRoot = $appRootAbsPath;

if ($debugMode) {
    error_log("DEBUG: Basis-URL: " . $baseUrl);
    error_log("DEBUG: Projekt-Root: " . $projectRoot);
}


// --- 2. Universelle Sicherheits-Header & CSP mit Nonce ---
$nonce = bin2hex(random_bytes(16));
$csp = [
    'default-src' => ["'self'"],
    'script-src' => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com", "https://placehold.co"],
    'style-src' => ["'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net", "https://fonts.googleapis.com", "https://code.jquery.com/", "https://cdnjs.cloudflare.com"], // 'unsafe-inline' für Summernote
    'font-src' => ["'self'", "data:", "https://cdn.jsdelivr.net", "https://fonts.gstatic.com", "https://cdnjs.cloudflare.com"], // 'data:' für Summernote
    'img-src' => ["'self'", "data:", "https://www.googletagmanager.com", "https://cdn.twokinds.keenspot.com", "https://twokindscomic.com", "https://placehold.co"],
    'object-src' => ["'none'"],
    'connect-src' => ["'self'", "https://cdn.jsdelivr.net", "https://cdn.twokinds.keenspot.com", "https://*.google-analytics.com"],
    'frame-ancestors' => ["'self'"],
    'base-uri' => ["'self'"],
    'form-action' => ["'self'"],
];
$cspHeader = '';
foreach ($csp as $directive => $sources) {
    $cspHeader .= $directive . ' ' . implode(' ', $sources) . '; ';
}
header("Content-Security-Policy: " . trim($cspHeader));
// Verhindert, dass der Browser versucht, den MIME-Typ zu erraten (Schutz vor "MIME-Sniffing").
header('X-Content-Type-Options: nosniff');
// Verhindert Clickjacking-Angriffe. 'frame-ancestors' in CSP ist moderner, aber X-Frame-Options bietet Abwärtskompatibilität.
header('X-Frame-Options: SAMEORIGIN');
// Kontrolliert, welche Referrer-Informationen gesendet werden, um Datenlecks zu minimieren.
header('Referrer-Policy: strict-origin-when-cross-origin');
// Permissions Policy: Deaktiviert sensible Browser-Features, die im Admin-Bereich nicht benötigt werden.
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");


// --- 3. Strikte Session-Konfiguration ---
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

// --- 4. Schutz vor Session Fixation und Hijacking ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) { // 15 Minuten
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
}
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

// NEU: Robuster Session-Fingerabdruck (User-Agent + IP-Netzwerk)
$sessionIdentifier = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . (substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.'))));
if (isset($_SESSION['session_fingerprint'])) {
    if ($_SESSION['session_fingerprint'] !== $sessionIdentifier) {
        // Fingerabdruck hat sich geändert -> Möglicher Angriff! Session zerstören.
        error_log("SECURITY ALERT: Session-Fingerabdruck hat sich geändert. Möglicher Hijacking-Versuch von IP: " . $_SERVER['REMOTE_ADDR']);
        session_unset();
        session_destroy();
        header('Location: index.php?reason=session_hijacked');
        exit;
    }
} else {
    $_SESSION['session_fingerprint'] = $sessionIdentifier;
}


// --- 5. Umfassender CSRF-Schutz ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function verify_csrf_token()
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

    if ($debugMode)
        error_log("DEBUG ({$callingScript}): CSRF-Prüfung. Erhaltener Token: " . ($token ?? 'KEINER') . ". Session-Token: " . $_SESSION['csrf_token']);

    if (!isset($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        if ($debugMode)
            error_log("FEHLER ({$callingScript}): CSRF-Token-Validierung fehlgeschlagen.");
        // Definiere IS_API_CALL, wenn es nicht existiert
        if (!defined('IS_API_CALL')) {
            define('IS_API_CALL', false);
        }
        if (IS_API_CALL) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage (CSRF-Token fehlt oder ist falsch).']);
        } else {
            die('CSRF-Token-Validierung fehlgeschlagen.');
        }
        exit;
    }
}


// --- 6. Hilfsfunktionen für Einstellungs-JSON ---
function load_settings(string $filePath, string $key, bool $debugMode): array
{
    $defaults = ['last_run_timestamp' => null];
    if (!file_exists($filePath)) {
        if ($debugMode)
            error_log("DEBUG: Einstellungsdatei $filePath nicht gefunden, verwende Standardwerte.");
        return $defaults;
    }
    $allSettings = json_decode(file_get_contents($filePath), true);
    return $allSettings[$key] ?? $defaults;
}

function save_settings(string $filePath, string $key, array $data, bool $debugMode): void
{
    $allSettings = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
    if (!is_array($allSettings))
        $allSettings = [];
    $allSettings[$key] = $data;
    file_put_contents($filePath, json_encode($allSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($debugMode)
        error_log("DEBUG: Einstellungen für Schlüssel '$key' in $filePath gespeichert.");
}


// --- 7. Logout-Logik ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    verify_csrf_token(); // Überprüfe den CSRF-Token, bevor du den Logout durchführst.
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion mit gültigem CSRF-Token erkannt.");

    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie, um einen sauberen Logout zu gewährleisten.
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

    // Zerstöre die Session auf dem Server.
    session_destroy();

    // Leere den Output Buffer und leite zur Login-Seite weiter.
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// --- 8. Finaler Login-Check ---
// KORRIGIERT: Schließt die Login-Seite explizit von der Prüfung aus.
if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        if ($debugMode)
            error_log("DEBUG: Nicht angemeldet. Weiterleitung zur Login-Seite von admin_init.php (aufgerufen von {$callingScript}).");

        // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
        header('Location: index.php');
        exit;
    }
}

// --- 9. Session-Timeout-Logik ---
define('SESSION_TIMEOUT_SECONDS', 600); // 10 Minuten
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
    if ($debugMode)
        error_log("DEBUG: Session abgelaufen. Letzte Aktivität vor " . (time() - $_SESSION['last_activity']) . " Sekunden.");

    session_unset();
    session_destroy();
    header("Location: index.php?reason=session_expired");
    exit;
}
$_SESSION['last_activity'] = time();

?>