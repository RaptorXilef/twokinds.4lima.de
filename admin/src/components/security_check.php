<?php
/**
 * Führt eine zentrale Sicherheits- und Sitzungsüberprüfung durch.
 * Diese Datei sollte auf jeder Seite im Admin-Bereich eingebunden werden, die einen Login erfordert.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definiere die Inaktivitätszeit in Sekunden (600 Sekunden = 10 Minuten)
define('SESSION_TIMEOUT_SECONDS', 600);

// --- Session-Timeout-Logik ---
// Diese Prüfung wird nur für angemeldete Benutzer durchgeführt.
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {

    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];

        if ($inactive_time > SESSION_TIMEOUT_SECONDS) {
            if ($debugMode) {
                error_log("DEBUG (security_check.php): Session abgelaufen. Inaktiv für " . $inactive_time . " Sekunden.");
            }
            // Session-Variablen löschen
            session_unset();
            // Session zerstören
            session_destroy();

            // Weiterleitung zur Login-Seite mit einer Benachrichtigung
            // Wichtig: ob_start() muss am Anfang des Haupt-Skripts aufgerufen werden.
            header("Location: index.php?reason=session_expired");
            exit;
        }
    }

    // Zeitstempel der letzten Aktivität bei jedem Seitenaufruf aktualisieren
    $_SESSION['last_activity'] = time();
    if ($debugMode) {
        error_log("DEBUG (security_check.php): Session-Aktivität aktualisiert.");
    }
}
?>