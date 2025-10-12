<?php
/**
 * Dieses Skript wird per AJAX aufgerufen, um die PHP-Session am Leben zu erhalten.
 * Es bindet die zentrale admin_init.php ein, um alle Sicherheitsprüfungen
 * (Login, Session-Fingerprint, CSRF-Token) zu durchlaufen.
 * Bei Erfolg wird der 'last_activity'-Zeitstempel aktualisiert.
 *
 * @file      ROOT/public/admin/src/components/keep_alive.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   3.0.0
 * @since     3.0.0 Umstellung auf die zentrale admin_init.php im übergeordneten Komponenten-Verzeichnis.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Definiere eine Konstante, damit admin_init.php weiß, dass dies ein API-Aufruf ist.
define('IS_API_CALL', true);

// Binde die zentrale Initialisierungs- und Sicherheitsdatei ein.
// Diese Datei kümmert sich um alles: Session-Start, CSRF-Prüfung, Login-Status etc.
// Der Pfad wurde an die neue, zentrale Struktur angepasst.
require_once __DIR__ . '/../../../src/components/admin_init.php';

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