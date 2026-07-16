<?php
/**
 * Sicherer API-Endpunkt für Admin-Anfragen.
 * Dient als öffentlicher Vermittler für private Skripte.
 *
 * @file      ROOT/public/admin/api/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.0.0 Initiale Erstellung als keep_alive_api.php
 * @since     1.1.0 Verschoben und umbenannt zu public/admin/api/index.php für zukünftige Erweiterbarkeit und robustere Pfad-Initialisierung.
 */

// --- 1. Grundlegende Initialisierung & Sicherheit ---

// Definiere eine Konstante, damit nachgelagerte Skripte (wie init_admin.php)
// erkennen, dass dieser Aufruf über die API und nicht direkt erfolgt.
define('IS_API_CALL', true);


// Lade die zentrale Konfigurationsdatei. Diese Datei ist der Dreh- und Angelpunkt
// und kümmert sich um das Laden aller weiteren Abhängigkeiten, Pfad-Konstanten
// und der `init_admin.php` mit allen Sicherheitsprüfungen.
// Der Pfad wird dynamisch und robust aus dem aktuellen Dateispeicherort generiert.
$initPath = '../../../src/components/admin/init_admin.php';

// Stelle sicher, dass die init-Datei existiert, bevor sie eingebunden wird.
if (!file_exists($initPath)) {
    http_response_code(500); // Internal Server Error
    // Sende eine generische Fehlermeldung, um keine Server-Details preiszugeben.
    echo json_encode(['status' => 'error', 'message' => 'Server Konfiguration Error.']);
    exit;
}
require_once $initPath;


// --- 2. Aktion-Routing ---

// Ermittle die angeforderte Aktion aus dem URL-Parameter (z.B. .../api.php?action=keep_alive).
$action = $_GET['action'] ?? null;

switch ($action) {
    case 'keep_alive':
        // Die `init_admin.php` (durch `load_config.php` geladen) hat bereits alle
        // Sicherheitsprüfungen (Login-Status, CSRF-Token etc.) durchgeführt.
        // Wir können das private Skript nun sicher einbinden.
        $keepAlivePath = DIRECTORY_PRIVATE_COMPONENTS_ADMIN . DIRECTORY_SEPARATOR . 'keep_alive.php';
        if (file_exists($keepAlivePath)) {
            require_once $keepAlivePath;
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Keep-alive Script nicht auf dem Server gefunden.']);
        }
        break;

    // Hier könnten später weitere sichere API-Aktionen hinzugefügt werden.
    // Beispiel:
    // case 'get_user_stats':
    //     require_once DIRECTORY_PRIVATE_COMPONENTS_ADMIN . '/stats_handler.php';
    //     break;

    default:
        // Wenn eine ungültige oder keine Aktion angefordert wurde.
        http_response_code(400); // 400 Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Falscher oder fehlender Aktion-Parameter.']);
        exit;
}
?>