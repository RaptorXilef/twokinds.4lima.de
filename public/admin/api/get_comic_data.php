<?php
/**
 * @file      ROOT/public/admin/api/get_comic_data.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 * @since     1.0.0 Initiale Erstellung
 *
 * @description Sicherer Admin-API-Endpunkt.
 * Wird von management_reports.php (via admin_reports.js) aufgerufen.
 * Prüft die Admin-Session und liefert das Original-Transkript für eine gegebene Comic-ID.
 */

// === 1. ZENTRALE ADMIN-INITIALISIERUNG (API-MODUS) ===

// Definiere IS_API_CALL, damit init_admin.php die JSON-Fehlerbehandlung aktiviert
define('IS_API_CALL', true);

// init_admin.php lädt load_config.php, startet die Session, prüft Login,
// prüft CSRF (wenn nicht als GET-Parameter übergeben, prüft es POST/Header),
// und setzt JSON-Header bei Fehlern.
require_once __DIR__ . '/../../../src/components/admin/init_admin.php';

// Wenn wir hier ankommen, ist der Admin eingeloggt und der CSRF-Token (via GET) war gültig.

// === 2. DATEN ABRUFEN ===

// --- A. Parameter validieren ---
$comicId = $_GET['id'] ?? null;
if (empty($comicId)) {
    // init_admin.php hat bereits den JSON-Header gesetzt
    echo json_encode(['success' => false, 'message' => 'Fehler: Keine Comic-ID angegeben.']);
    exit;
}

// --- B. Comic-Daten laden ---
// Lade alle Comic-Daten (diese Datei wird von init_admin geladen, wenn $comicData nicht existiert)
// Wir laden sie hier explizit, um sicherzugehen.
require_once Path::getComponentPath('load_comic_data.php'); // $comicData wird hier definiert

if (empty($comicData) || !is_array($comicData)) {
    echo json_encode(['success' => false, 'message' => 'Fehler: Konnte comic_var.json nicht laden oder sie ist leer.']);
    exit;
}

// --- C. Spezifischen Comic finden ---
if (!isset($comicData[$comicId])) {
    echo json_encode(['success' => false, 'message' => "Fehler: Comic-ID '$comicId' nicht in der Datenbank gefunden."]);
    exit;
}

$transcript = $comicData[$comicId]['transcript'] ?? '';

// --- D. Erfolg melden ---
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'comic_id' => $comicId,
    'transcript' => $transcript
]);
exit;
?>