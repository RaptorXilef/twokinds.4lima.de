<?php
/**
 * Zentrales Initialisierungsskript für alle Admin-Seiten (Gehärtete Version).
 *
 * Dieses Skript übernimmt wiederkehrende Aufgaben und implementiert wichtige Sicherheitsmaßnahmen:
 * - Session-Konfiguration für erhöhte Sicherheit (HTTPOnly, Secure, SameSite).
 * - Schutz vor Session Hijacking durch regelmäßige Erneuerung der Session-ID.
 * - Schutz vor Clickjacking durch X-Frame-Options Header.
 * - Handhabung des Debug-Modus.
 * - Starten des Output Buffers.
 * - Starten und Sichern der Session.
 * - Einbinden der zentralen Timeout-Prüfung.
 * - Bereitstellen einer globalen, validierten Logout-Funktion.
 * - Überprüfung, ob der Benutzer angemeldet ist, andernfalls Weiterleitung zum Login.
 *
 * Die Variable $debugMode sollte in der aufrufenden Datei VOR dem Einbinden dieses Skripts gesetzt werden.
 */

// Der Dateiname des aufrufenden Skripts wird für die dynamische Debug-Meldung verwendet.
$callingScript = basename($_SERVER['PHP_SELF']);

// Wenn $debugMode in der aufrufenden Datei nicht definiert wurde, wird es sicherheitshalber auf 'false' gesetzt.
if (!isset($debugMode)) {
    $debugMode = false;
}

if ($debugMode)
    error_log("DEBUG: admin_init.php wird von {$callingScript} eingebunden.");

// Starte den Output Buffer als ALLERERSTE Zeile.
ob_start();

// --- SICHERHEITSVERBESSERUNG 1: Strikte Session-Konfiguration ---
// Setzt sichere Cookie-Parameter, BEVOR die Session gestartet wird.
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

// --- SICHERHEITSVERBESSERUNG 2: Schutz vor Session Hijacking ---
// Regeneriert die Session-ID in regelmäßigen Abständen.
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 900) { // Alle 15 Minuten
    session_regenerate_id(true); // Erneuert die ID und löscht die alte Session-Datei
    $_SESSION['last_regeneration'] = time();
    if ($debugMode)
        error_log("DEBUG: Session-ID wurde aus Sicherheitsgründen erneuert.");
}

// --- SICHERHEITSVERBESSERUNG 3: Schutz vor Clickjacking ---
// Verhindert, dass die Admin-Seite in einem <frame> oder <iframe> auf einer anderen Domain geladen wird.
header('X-Frame-Options: SAMEORIGIN');

// Binde die zentrale Sicherheits- und Sitzungsüberprüfung (Timeout) ein.
require_once __DIR__ . '/security_check.php';

if ($debugMode)
    error_log("DEBUG: Session gestartet und security_check.php eingebunden.");

// Logout-Funktion (wird über GET-Parameter ausgelöst)
// Validierung des 'action'-Parameters
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion in admin_init.php erkannt.");

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