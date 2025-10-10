<?php
/**
 * Dieses Skript wird per AJAX aufgerufen, um die PHP-Session am Leben zu erhalten.
 * Es bindet die zentrale admin_init.php ein, um alle Sicherheitsprüfungen
 * (Login, Session-Fingerprint, CSRF-Token) zu durchlaufen.
 * Bei Erfolg wird der 'last_activity'-Zeitstempel aktualisiert.
 *
 * @version 2.0 (Strukturell überarbeitet für zentrale Sicherheit)
 * @date 2025-09-07
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Definiere eine Konstante, damit admin_init.php weiß, dass dies ein API-Aufruf ist.
define('IS_API_CALL', true);

// Binde die zentrale Initialisierungs- und Sicherheitsdatei ein.
// Diese Datei kümmert sich um alles: Session-Start, CSRF-Prüfung, Login-Status etc.
require_once __DIR__ . '/admin_init.php';

// Wenn das Skript bis hierhin ohne Fehler durchläuft (d.h., admin_init.php hat
// keinen exit() wegen eines Fehlers ausgelöst), ist der Benutzer authentifiziert
// und der CSRF-Token war gültig.

// Jetzt aktualisieren wir einfach die letzte Aktivität.
$_SESSION['last_activity'] = time();

// Sende eine Erfolgsmeldung zurück.
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Session extended.']);
exit;
?>