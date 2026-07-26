<?php

/**
 * Dieses Skript wird per AJAX aufgerufen, um die PHP-Session am Leben zu erhalten.
 * Es bindet die zentrale init_admin.php ein, um alle Sicherheitsprüfungen
 * (Login, Session-Fingerprint, CSRF-Token) zu durchlaufen.
 * Bei Erfolg wird der 'last_activity'-Zeitstempel in der Session durch init_admin.php automatisch aktualisiert.
 *
 * @file      ROOT/src/components/admin/keep_alive.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
* @since 3.0.0 - 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Umstellung auf die zentrale `init_admin.php` im übergeordneten Komponenten-Verzeichnis.
 *
 *    BUGFIXES
 *    - Behebung einer PHP-Warnung bezüglich doppelter Konstantendefinition (`IS_API_CALL`).
 *
 * @since 5.0.0
 * - fix(Path): Pfad zur init_admin.php korrigiert (../../src/...).
 * - feat(API): IS_API_CALL Konstante definiert, um JSON-Antworten bei Fehlern zu erzwingen.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// 1. Konstante definieren: Das sagt der init_admin.php, dass wir hier nur JSON wollen
// und keinen HTML-Redirect, falls die Session abgelaufen ist.
if (!defined('IS_API_CALL')) {
    define('IS_API_CALL', true);
}

// 2. Zentrale Initialisierung laden
// Dies prüft Login, Timeout, Fingerprint und CSRF.
// Wenn etwas nicht stimmt, sendet init_admin.php einen 401/403 JSON-Fehler und beendet das Skript.
require_once __DIR__ . '/init_admin.php';

// 3. Wenn wir hier ankommen, ist die Session gültig und wurde verlängert.
// Wir senden eine Erfolgsmeldung zurück.
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Session extended',
    'timestamp' => time()
]);
