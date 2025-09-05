<?php
/**
 * Zentrales Initialisierungsskript für alle Admin-Seiten (Gehärtete Version).
 *
 * Dieses Skript übernimmt wiederkehrende Aufgaben und implementiert wichtige Sicherheitsmaßnahmen:
 * - Strikte Sicherheits-Header (CSP, X-Content-Type-Options etc.)
 * - Session-Konfiguration für erhöhte Sicherheit (HTTPOnly, Secure, SameSite).
 * - Schutz vor Session Hijacking durch User-Agent-Bindung und regelmäßige ID-Erneuerung.
 * - Schutz vor Clickjacking durch X-Frame-Options Header.
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

// --- SICHERHEITSVERBESSERUNG 1: HTTP Security Headers ---
// Content-Security-Policy: Schränkt ein, von wo Ressourcen (Skripte, Stylesheets etc.) geladen werden dürfen.
// Dies ist ein starker Schutz gegen XSS-Angriffe.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https://placehold.co;");
// Verhindert, dass der Browser versucht, den MIME-Typ zu erraten.
header('X-Content-Type-Options: nosniff');
// Verhindert Clickjacking-Angriffe.
header('X-Frame-Options: SAMEORIGIN');
// Kontrolliert, welche Referrer-Informationen gesendet werden.
header('Referrer-Policy: strict-origin-when-cross-origin');


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
// Schritt 3a: Binde die Session an den User Agent des Benutzers.
if (isset($_SESSION['user_agent'])) {
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        // User Agent hat sich geändert -> Möglicher Angriff! Session zerstören.
        session_unset();
        session_destroy();
        header('Location: index.php?reason=session_hijacked');
        exit;
    }
} else {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

// Schritt 3b: Regeneriert die Session-ID in regelmäßigen Abständen.
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 900) { // Alle 15 Minuten
    session_regenerate_id(true); // Erneuert die ID und löscht die alte Session-Datei
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

// Logout-Funktion mit CSRF-Token-Überprüfung
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Überprüfe, ob der Token gültig ist.
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        // Ungültiger Token -> Logout abbrechen
        if ($debugMode)
            error_log("DEBUG: Logout-Versuch mit ungültigem CSRF-Token abgebrochen.");
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