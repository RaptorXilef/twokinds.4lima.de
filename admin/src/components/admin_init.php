<?php
/**
 * Zentrales Initialisierungsskript für alle Admin-Seiten (Final gehärtete Version).
 *
 * Dieses Skript übernimmt wiederkehrende Aufgaben und implementiert wichtige Sicherheitsmaßnahmen:
 * - Strikte Sicherheits-Header (CSP, HSTS, Permissions-Policy etc.).
 * - Session-Konfiguration für erhöhte Sicherheit (HTTPOnly, Secure, SameSite).
 * - Schutz vor Session Hijacking durch User-Agent- und IP-Adressen-Bindung.
 * - Schutz vor Session Fixation durch regelmäßige ID-Erneuerung.
 * - CSRF-Schutz für die Logout-Funktion.
 *
 * Die Variable $debugMode sollte in der aufrufenden Datei VOR dem Einbinden dieses Skripts gesetzt werden.
 */

// Der Dateiname des aufrufenden Skripts wird für die dynamische Debug-Meldung verwendet.
$callingScript = basename($_SERVER['PHP_SELF']);

if (!isset($debugMode)) {
    $debugMode = false;
}

if ($debugMode)
    error_log("DEBUG: admin_init.php wird von {$callingScript} eingebunden.");

ob_start();

// --- SICHERHEITSVERBESSERUNG 1: Erweiterte HTTP Security Headers ---

// Content-Security-Policy (CSP) als Array für bessere Lesbarkeit und Wartbarkeit.
$csp = [
    // Standard-Richtlinie: Lade alles nur von der eigenen Domain ('self').
    'default-src' => ["'self'"],

    // Skripte: Erlaube 'self', inline-Skripte ('unsafe-inline') und vertrauenswürdige CDNs.
    'script-src' => ["'self'", "'unsafe-inline'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com", "https://cdn.twokinds.keenspot.com"],

    // Stylesheets: Erlaube 'self', inline-Styles ('unsafe-inline') und vertrauenswürdige CDNs.
    'style-src' => ["'self'", "'unsafe-inline'", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://cdn.twokinds.keenspot.com", "https://fonts.googleapis.com"],

    // Schriftarten: Erlaube 'self' und CDNs.
    'font-src' => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com", "https://cdn.twokinds.keenspot.com"],

    // Bilder: Erlaube 'self', data-URIs (für base64-Bilder) und den Placeholder-Dienst.
    'img-src' => ["'self'", "data:", "https://placehold.co", "https://cdn.twokinds.keenspot.com"],

    // *** NEU: connect-src Direktive hinzugefügt ***
    // Erlaubt Verbindungen (z.B. via fetch, XHR) zu den angegebenen Domains.
    'connect-src' => ["'self'", "https://cdn.twokinds.keenspot.com", "https://region1.google-analytics.com"],

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


// --- SICHERHEITSVERBESSERUNG 2: Strikte Session-Konfiguration ---
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

// --- SICHERHEITSVERBESSERUNG 3: Schutz vor Session Hijacking & Fixation ---
// Schritt 3a: Binde die Session an den User Agent und die IP-Adresse (anonymisiert).
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

// Schritt 3b: Regeneriert die Session-ID in regelmäßigen Abständen.
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 900) { // Alle 15 Minuten
    // WICHTIGE OPTIMIERUNG: Session-Daten sichern, regenerieren, wiederherstellen.
    $currentSessionData = $_SESSION;
    session_regenerate_id(true); // Erneuert die ID und löscht die alte Session-Datei
    $_SESSION = $currentSessionData;
    $_SESSION['last_regeneration'] = time();
    if ($debugMode)
        error_log("DEBUG: Session-ID wurde aus Sicherheitsgründen erneuert.");
}

// Binde die zentrale Sicherheits- und Sitzungsüberprüfung (Timeout) ein.
require_once __DIR__ . '/security_check.php';

// --- SICHERHEITSVERBESSERUNG 4: CSRF-Schutz für Logout ---
// Erstelle einen CSRF-Token, wenn noch keiner existiert.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

// FINALER SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet. Weiterleitung zur Login-Seite von admin_init.php (aufgerufen von {$callingScript}).");

    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean();
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin ist angemeldet. Initialisierung durch admin_init.php abgeschlossen.");
?>