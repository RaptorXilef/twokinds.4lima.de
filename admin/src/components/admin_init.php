<?php
/**
 * Zentrales Initialisierungsskript für alle Admin-Seiten.
 *
 * Dieses Skript übernimmt wiederkehrende Aufgaben wie:
 * - Handhabung des Debug-Modus
 * - Starten des Output Buffers, um Header-Weiterleitungen zu ermöglichen
 * - Starten der Session
 * - Einbinden der zentralen Sicherheits- und Sitzungsüberprüfung
 * - Bereitstellen einer globalen Logout-Funktion
 * - Überprüfung, ob der Benutzer angemeldet ist, andernfalls Weiterleitung zum Login
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

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer von admin_init.php gestartet.");

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
// __DIR__ verweist hier auf /src/components/, daher ist der Pfad korrekt.
require_once __DIR__ . '/security_check.php';

if ($debugMode)
    error_log("DEBUG: Session gestartet und security_check.php eingebunden.");

// Logout-Funktion (wird über GET-Parameter ausgelöst)
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
            $params["httponly"] // Korrekte Schreibweise
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