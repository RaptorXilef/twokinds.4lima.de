<?php

/**
 * Worker-Skript für den Thumbnail-Generator.
 *
 * @file      ROOT/public/admin/check_and_generate_thumbnail.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 *
 * @since 5.0.0
 * - Refactoring auf neue Architektur (Path-Klasse, JSON-Response).
 * - Rückgabe der ImageURL für Frontend-Vorschau hinzugefügt.
 * - Angleichung an Social-Media-Generator (Limits, White-BG Option).
 */

// Limits erhöhen für stabile Verarbeitung
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
set_time_limit(120);

// Definiere Konstante für init_admin.php, damit kein HTML-Header geladen wird
define('IS_API_CALL', true);

// === INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// JSON Header setzen
header('Content-Type: application/json');

// Hilfsfunktion für JSON-Exit
function sendJson($data)
{
    echo json_encode($data);
    exit;
}

try {
    // CSRF Prüfung (GET Parameter)
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
        sendJson(['status' => 'error', 'message' => 'Ungültiger CSRF Token.']);
    }

    // Parameter prüfen
    $imageName = $_GET['image'] ?? '';
    $format = $_GET['format'] ?? 'webp';
    $quality = (int)($_GET['quality'] ?? 80);
    $lossless = isset($_GET['lossless']) && $_GET['lossless'] === '1';
    // NEU: Option für Hintergrund
    $forceWhiteBg = isset($_GET['force_white_bg']) && $_GET['force_white_bg'] === '1';

    if (empty($imageName)) {
        sendJson(['status' => 'error', 'message' => 'Kein Bildname.']);
    }

    // Pfade definieren
    $sourceDir = DIRECTORY_PUBLIC_IMG_COMIC_LOWRES;
    $targetDir = DIRECTORY_PUBLIC_IMG_COMIC_THUMBNAILS;

    // Sicherheit: Nur Dateinamen erlauben, keine Pfade
    $imageName = basename($imageName);

    // Quelldatei suchen (Erweiterung kann variieren)
    $sourceFile = null;
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    foreach ($possibleExtensions as $ext) {
        // Prüfe, ob der Dateiname bereits eine Endung hat
        if (file_exists($sourceDir . DIRECTORY_SEPARATOR . $imageName)) {
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $imageName;
            break;
        }
        // Prüfe Varianten (falls nur ID übergeben wurde)
        if (file_exists($sourceDir . DIRECTORY_SEPARATOR . $imageName . '.' . $ext)) {
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $imageName . '.' . $ext;
            break;
        }
    }

    if (!$sourceFile) {
        sendJson(['status' => 'error', 'message' => 'Quelldatei nicht gefunden: ' . $imageName]);
    }

    // Zielpfad generieren
    $targetFilename = pathinfo($imageName, PATHINFO_FILENAME) . '.' . $format;
    $targetFile = $targetDir . DIRECTORY_SEPARATOR . $targetFilename;

    if (file_exists($targetFile)) {
        sendJson(['status' => 'exists', 'message' => 'Thumbnail existiert bereits.']);
    }

    // === GENERIERUNG (GD Library) ===
    $info = getimagesize($sourceFile);
    if (!$info) {
        throw new Exception("Konnte Bildgröße nicht lesen.");
    }

    $mime = $info['mime'];
    $srcWidth = $info[0];
    $srcHeight = $info[1];

    // Bild laden
    $sourceImage = null;
    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourceFile);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourceFile);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourceFile);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourceFile);
            break;
        default:
            throw new Exception("Nicht unterstütztes Quellformat: $mime");
    }

    if (!$sourceImage) {
        throw new Exception("Fehler beim Laden des Bildes.");
    }

    // Zielgröße berechnen (Max 200px Breite/Höhe, Aspekt beibehalten)
    $maxWidth = 200;
    $maxHeight = 300;

    $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
    if ($ratio > 1) {
        $ratio = 1;
    }

    $newWidth = (int)($srcWidth * $ratio);
    $newHeight = (int)($srcHeight * $ratio);

    $thumbImage = imagecreatetruecolor($newWidth, $newHeight);

    // HINTERGRUND LOGIK
    $useWhiteBackground = $forceWhiteBg || ($format === 'jpg' || $format === 'jpeg');

    if ($useWhiteBackground) {
        $bgColor = imagecolorallocate($thumbImage, 255, 255, 255);
        imagefilledrectangle($thumbImage, 0, 0, $newWidth, $newHeight, $bgColor);
    } else {
        imagealphablending($thumbImage, false);
        imagesavealpha($thumbImage, true);
        $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
        imagefilledrectangle($thumbImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resampling
    imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

    // Speichern
    $saved = false;
    switch ($format) {
        case 'jpeg':
        case 'jpg':
            $saved = imagejpeg($thumbImage, $targetFile, $quality);
            break;
        case 'png':
            $pngQuality = (int)(9 - (($quality / 100) * 9));
            $saved = imagepng($thumbImage, $targetFile, $pngQuality);
            break;
        case 'webp':
            $saved = imagewebp($thumbImage, $targetFile, ($lossless && defined('IMG_WEBP_LOSSLESS')) ? IMG_WEBP_LOSSLESS : $quality);
            break;
    }

    imagedestroy($sourceImage);
    imagedestroy($thumbImage);

    if ($saved) {
        // Image URL generieren für Frontend-Vorschau
        $webUrl = Url::getImgComicThumbnailsUrl($targetFilename) . '?' . time();

        sendJson([
            'status' => 'success',
            'message' => $targetFilename,
            'details' => "{$newWidth}x{$newHeight}",
            'imageUrl' => $webUrl // URL mitsenden
        ]);
    } else {
        throw new Exception("Konnte Datei nicht speichern (Rechte?).");
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()]);
}
