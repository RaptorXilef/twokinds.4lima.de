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

if ($debugMode) {
    error_log("DEBUG (admin_init.php): Basis-URL bestimmt: " . $baseUrl);
}


// --- 2. Sicherheits-Header & CSP mit Nonce (Restriktiv für Admin) ---
$nonce = bin2hex(random_bytes(16));

// Content-Security-Policy (CSP) als Array für bessere Lesbarkeit und Wartbarkeit.
$csp = [
    // Standard-Richtlinie: Lade alles nur von der eigenen Domain ('self').
    'default-src' => ["'self'"],
    // Skripte: Erlaube 'self', inline-Skripte und füge die Nonce-Quelle hinzu.
    'script-src' => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net"],
    // Stylesheets: Erlaube 'self', inline-Styles ('unsafe-inline') und vertrauenswürdige CDNs.
    'style-src' => ["'self'", "'nonce-{$nonce}'", "https://cdn.twokinds.keenspot.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://fonts.googleapis.com"],
    // Schriftarten: Erlaube 'self' und CDNs.
    'font-src' => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com", "https://fonts.googleapis.com"],
    // Bilder: Erlaube 'self', data-URIs (für base64-Bilder) und den Placeholder-Dienst.
    'img-src' => ["'self'", "data:", "https://cdn.twokinds.keenspot.com", "https://placehold.co"],
    // Erlaubt Verbindungen (z.B. via fetch, XHR) zu den angegebenen Domains.
    'connect-src' => ["'self'"],
    // Plugins (Flash etc.): Verbiete alles.
    'object-src' => ["'none'"],
    // Framing: Erlaube das Einbetten der Seite nur durch sich selbst (Schutz vor Clickjacking).
    'frame-ancestors' => ["'self'"],
    'base-uri' => ["'self'"], // Verhindert, dass die Basis-URL manipuliert wird.
    'form-action' => ["'self'"], // Erlaubt Formular-Übermittlungen nur an die eigene Domain.
];

// Baue den CSP-Header-String aus dem Array zusammen.
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

// HTTP Strict Transport Security (HSTS): Weist den Browser an, für eine lange Zeit nur über HTTPS zu kommunizieren.
// WICHTIG: Nur aktivieren, wenn du sicher bist, dass deine Seite dauerhaft und ausschließlich über HTTPS laufen wird.
// Einmal gesetzt, erzwingt der Browser für die angegebene Zeit (hier 1 Jahr) HTTPS.
// if (isset($_SERVER['HTTPS'])) {
//     header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
// }


// --- 3: Strikte Session-Konfiguration ---
session_set_cookie_params([
    'lifetime' => 0, // Session-Cookie gilt bis zum Schließen des Browsers
    'path' => '/', // Gilt für die gesamte Domain
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']), // Cookie nur über HTTPS senden
    'httponly' => true, // Verhindert Zugriff durch JavaScript (Schutz vor XSS)
    'samesite' => 'Strict' // Schutz vor CSRF-Angriffen
]);

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 4: Schutz vor Session Hijacking & Fixation ---
// Schritt 4a: Binde die Session an den User Agent und die IP-Adresse (anonymisiert).
$sessionIdentifier = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . (substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.'))));
if (isset($_SESSION['session_fingerprint'])) {
    if ($_SESSION['session_fingerprint'] !== $sessionIdentifier) {
        // User Agent hat sich geändert -> Möglicher Angriff! Session zerstören.
        error_log("SECURITY ALERT: Session-Fingerabdruck hat sich geändert. Möglicher Hijacking-Versuch von IP: " . $_SERVER['REMOTE_ADDR']);
        session_unset();
        session_destroy();
        header('Location: index.php?reason=session_hijacked');
        exit;
    }
} else {
    $_SESSION['session_fingerprint'] = $sessionIdentifier;
}

// Schritt 4b: Regeneriert die Session-ID in regelmäßigen Abständen.
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 900) { // Alle 15 Minuten
    // Session-Daten sichern, regenerieren, wiederherstellen.
    $currentSessionData = $_SESSION;
    session_regenerate_id(true); // Erneuert die ID und löscht die alte Session-Datei
    $_SESSION = $currentSessionData;
    $_SESSION['last_regeneration'] = time();
    if ($debugMode)
        error_log("DEBUG: Session-ID wurde aus Sicherheitsgründen erneuert.");
}


// --- 5: CSRF-Schutz ---
// Erstelle einen CSRF-Token, wenn noch keiner existiert.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfTokenForJs = $_SESSION['csrf_token']; // Für JavaScript verfügbar machen


// Überprüft den CSRF-Token für POST- und AJAX-Anfragen. Bricht bei Fehler ab.
function verify_csrf_token()
{
    global $debugMode;
    $token = null;

    if (!empty($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    } else {
        $json_input = file_get_contents('php://input');
        if (!empty($json_input)) {
            $data = json_decode($json_input, true);
            $token = $data['csrf_token'] ?? null;
        }
    }

    // KORRIGIERT: Prüft auf Existenz beider Token, bevor hash_equals aufgerufen wird
    if ($token === null || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("SECURITY WARNING: Ungültiger CSRF-Token bei Anfrage von IP: " . $_SERVER['REMOTE_ADDR']);
        http_response_code(403);
        die('Ungültige Anfrage (CSRF-Token-Fehler).');
    }

    if ($debugMode)
        error_log("DEBUG: CSRF-Token erfolgreich validiert.");
}

// --- 6. Session-Timeout ---
// Binde die zentrale Sicherheits- und Sitzungsüberprüfung (Timeout) ein.
require_once __DIR__ . '/security_check.php';

// --- 7. Logout-Funktion ---
// Logout-Funktion mit CSRF-Token-Überprüfung
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Logge den fehlgeschlagenen Versuch (auch ohne Debug-Modus)
        error_log("SECURITY WARNING: Logout-Versuch mit ungültigem CSRF-Token von IP: " . $_SERVER['REMOTE_ADDR']);
        // Leite einfach zum Dashboard zurück, ohne Fehlermeldung, um keine Infos preiszugeben.
        header('Location: management_user.php');
        exit;
    }

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
        ob_end_clean();
        header('Location: index.php');
        exit;
    }
}

if ($debugMode && basename($_SERVER['PHP_SELF']) !== 'index.php')
    error_log("DEBUG: Admin ist angemeldet. Initialisierung durch admin_init.php abgeschlossen.");
?>