<?php

/**
 * Worker-Skript für den Comic-Seiten-Generator.
 * Erstellt die PHP-Datei für eine Comic-Seite (Stub, der den Renderer lädt).
 *
 * @file      ROOT/public/admin/check_and_generate_comic.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 *
 * @since 5.0.0
 * - Initiale Erstellung (Refactoring auf Worker-Pattern).
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
define('IS_API_CALL', true);

require_once __DIR__ . '/../../src/components/admin/init_admin.php';

header('Content-Type: application/json');

function sendJson($data)
{
    echo json_encode($data);
    exit;
}

/**
 * Berechnet den relativen Pfad zwischen zwei Verzeichnissen.
 */
function getRelativePath($from, $to)
{
    // Normalisiere Pfade
    $from = rtrim(str_replace('\\', '/', $from), '/');
    $to = rtrim(str_replace('\\', '/', $to), '/');

    // Zerlege in Arrays
    $fromParts = explode('/', $from);
    $toParts = explode('/', $to);

    // Gemeinsamen Stamm finden
    while (count($fromParts) && count($toParts) && ($fromParts[0] == $toParts[0])) {
        array_shift($fromParts);
        array_shift($toParts);
    }

    // ".." für jeden Schritt zurück
    $path = str_repeat('../', count($fromParts));
    // Restlicher Pfad zum Ziel
    $path .= implode('/', $toParts);

    return $path;
}

try {
    // CSRF
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
        sendJson(['status' => 'error', 'message' => 'Ungültiger CSRF Token.']);
    }

    $comicId = $_GET['id'] ?? '';
    if (empty($comicId)) {
        sendJson(['status' => 'error', 'message' => 'Keine Comic-ID übergeben.']);
    }

    // Sicherheit: ID darf nur aus Ziffern/Buchstaben bestehen
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $comicId)) {
        sendJson(['status' => 'error', 'message' => 'Ungültiges ID-Format.']);
    }

    // Pfade
    $targetDir = DIRECTORY_PUBLIC_COMIC;
    $rendererDir = DIRECTORY_PRIVATE_RENDERER;
    $targetFile = $targetDir . DIRECTORY_SEPARATOR . $comicId . '.php';

    // Prüfen
    if (file_exists($targetFile)) {
        sendJson(['status' => 'exists', 'message' => "Seite $comicId.php existiert bereits."]);
    }

    if (!is_writable($targetDir)) {
        throw new Exception("Keine Schreibrechte im Zielverzeichnis: $targetDir");
    }

    // Relativen Pfad zum Renderer berechnen
    // Z.B. von /public/comic/ nach /src/renderer/
    $relativePath = getRelativePath($targetDir, $rendererDir . '/renderer_comic_page.php');

    // PHP Content erstellen
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * Automatisch generierte Comic-Seite.\n";
    $content .= " * ID: $comicId\n";
    $content .= " */\n";
    $content .= "require_once __DIR__ . '/" . $relativePath . "';\n";

    if (file_put_contents($targetFile, $content) !== false) {
        sendJson(['status' => 'success', 'message' => "Seite $comicId.php erstellt."]);
    } else {
        throw new Exception("Fehler beim Schreiben der Datei $comicId.php.");
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()]);
}
