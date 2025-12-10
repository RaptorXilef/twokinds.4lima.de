<?php

/**
 * Worker-Skript für den Social-Media-Bild-Generator.
 * Generiert Bilder im Format 1200x630 (OpenGraph Standard).
 *
 * @file      ROOT/public/admin/check_and_generate_socialmedia.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International
 *
 * @since 5.0.0
 * - Initiale Erstellung.
 * - Feature: Variable Ausschnitt-Position (Top, Center, Bottom etc.) hinzugefügt.
 * - Fix: Speicher- und Zeitlimits erhöht für stabilen Batch-Prozess.
 */

// Fehlerunterdrückung für sauberes JSON, aber Logging aktiv lassen
ini_set('display_errors', '0');
error_reporting(E_ALL);

// WICHTIG: Limits erhöhen für High-Res Bildbearbeitung
ini_set('memory_limit', '1024M'); // 1GB RAM für GD-Operationen
set_time_limit(120); // 2 Minuten pro Bild max

define('IS_API_CALL', true);

require_once __DIR__ . '/../../src/components/admin/init_admin.php';

header('Content-Type: application/json');

function sendJson($data)
{
    echo json_encode($data);
    exit;
}

try {
    // CSRF Prüfung
    if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'])) {
        sendJson(['status' => 'error', 'message' => 'Ungültiger CSRF Token.']);
    }

    // Parameter
    $imageName = $_GET['image'] ?? '';
    $format = $_GET['format'] ?? 'webp';
    $quality = (int)($_GET['quality'] ?? 85);
    $lossless = isset($_GET['lossless']) && $_GET['lossless'] === '1';
    $resizeMode = $_GET['resize_mode'] ?? 'cover'; // 'cover' oder 'contain'
    $cropPosition = $_GET['crop_position'] ?? 'center'; // 'top', 'top_center', 'center', 'bottom_center', 'bottom'

    if (empty($imageName)) {
        sendJson(['status' => 'error', 'message' => 'Kein Bildname.']);
    }

    // Pfade (Nutze HiRes als Quelle für beste Qualität)
    $sourceDir = DIRECTORY_PUBLIC_IMG_COMIC_HIRES;
    $targetDir = DIRECTORY_PUBLIC_IMG_COMIC_SOCIALMEDIA;
    $imageName = basename($imageName);

    // Quelle finden
    $sourceFile = null;
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    foreach ($possibleExtensions as $ext) {
        if (file_exists($sourceDir . DIRECTORY_SEPARATOR . $imageName)) {
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $imageName;
            break;
        }
        if (file_exists($sourceDir . DIRECTORY_SEPARATOR . $imageName . '.' . $ext)) {
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $imageName . '.' . $ext;
            break;
        }
    }

    if (!$sourceFile) {
        sendJson(['status' => 'error', 'message' => 'Quelldatei (HiRes) nicht gefunden: ' . $imageName]);
    }

    // Ziel definieren
    $targetFilename = pathinfo($imageName, PATHINFO_FILENAME) . '.' . $format;
    $targetFile = $targetDir . DIRECTORY_SEPARATOR . $targetFilename;

    if (file_exists($targetFile)) {
        sendJson(['status' => 'exists', 'message' => 'Bild existiert bereits.']);
    }

    // === GENERIERUNG ===
    $info = getimagesize($sourceFile);
    if (!$info) {
        throw new Exception("Konnte Bildgröße nicht lesen.");
    }

    $mime = $info['mime'];
    $srcWidth = $info[0];
    $srcHeight = $info[1];

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
        default:
            throw new Exception("Nicht unterstütztes Quellformat: $mime");
    }

    if (!$sourceImage) {
        throw new Exception("Fehler beim Laden des Bildes.");
    }

    // Ziel: OpenGraph Standard (1200x630)
    $targetW = 1200;
    $targetH = 630;
    $finalImage = imagecreatetruecolor($targetW, $targetH);

    // Hintergrund füllen (Weiß für 'contain', oder generell als Basis)
    $bgColor = imagecolorallocate($finalImage, 255, 255, 255);
    imagefilledrectangle($finalImage, 0, 0, $targetW, $targetH, $bgColor);

    // Berechnung
    $srcRatio = $srcWidth / $srcHeight;
    $targetRatio = $targetW / $targetH;

    $dstX = 0;
    $dstY = 0;
    $dstW = $targetW;
    $dstH = $targetH;
    $srcX = 0;
    $srcY = 0;
    $srcW = $srcWidth;
    $srcH = $srcHeight;

    if ($resizeMode === 'cover') {
        // ZUSCHNEIDEN (Füllt das Bild komplett aus)
        if ($srcRatio > $targetRatio) {
            // Bild ist breiter als Ziel -> Links/Rechts abschneiden (Horizontal immer zentriert)
            $srcW = (int)($srcHeight * $targetRatio);
            $srcX = (int)(($srcWidth - $srcW) / 2);
        } else {
            // Bild ist höher als Ziel -> Oben/Unten abschneiden
            $srcH = (int)($srcWidth / $targetRatio);

            // Maximale Verschiebung möglich (Differenz zwischen Originalhöhe und Zielhöhe im Quellbild)
            $maxOffsetY = $srcHeight - $srcH;

            // Vertikale Position berechnen
            switch ($cropPosition) {
                case 'top':
                    $srcY = 0;
                    break;
                case 'top_center': // Zwischen Oben und Mitte (25%)
                    $srcY = (int)($maxOffsetY * 0.25);
                    break;
                case 'bottom_center': // Zwischen Mitte und Unten (75%)
                    $srcY = (int)($maxOffsetY * 0.75);
                    break;
                case 'bottom':
                    $srcY = $maxOffsetY;
                    break;
                case 'center':
                default:
                    $srcY = (int)($maxOffsetY / 2);
                    break;
            }
        }
    } else {
        // EINPASSEN ('contain') - Positionierung wirkt hier auf das eingefügte Bild im Zielrahmen
        if ($srcRatio > $targetRatio) {
            // Bild ist breiter -> Balken oben/unten
            $dstH = (int)($targetW / $srcRatio);
            // Hier auch Positionierung anwenden? Meistens will man es zentriert.
            // Der Einfachheit halber wenden wir die Logik auch hier an, falls gewünscht.
            $maxDstOffsetY = $targetH - $dstH;

            switch ($cropPosition) {
                case 'top':
                    $dstY = 0;
                    break;
                case 'top_center':
                    $dstY = (int)($maxDstOffsetY * 0.25);
                    break;
                case 'bottom_center':
                    $dstY = (int)($maxDstOffsetY * 0.75);
                    break;
                case 'bottom':
                    $dstY = $maxDstOffsetY;
                    break;
                default:
                    $dstY = (int)($maxDstOffsetY / 2);
                    break;
            }
        } else {
            // Bild ist höher -> Balken links/rechts (Horizontal bleibt zentriert)
            $dstW = (int)($targetH * $srcRatio);
            $dstX = (int)(($targetW - $dstW) / 2);
        }
    }

    imagecopyresampled($finalImage, $sourceImage, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

    // Speichern
    $saved = false;
    switch ($format) {
        case 'jpeg':
        case 'jpg':
            $saved = imagejpeg($finalImage, $targetFile, $quality);
            break;
        case 'png':
            $pngQ = (int)(9 - (($quality / 100) * 9));
            $saved = imagepng($finalImage, $targetFile, $pngQ);
            break;
        case 'webp':
            $saved = imagewebp($finalImage, $targetFile, ($lossless && defined('IMG_WEBP_LOSSLESS')) ? IMG_WEBP_LOSSLESS : $quality);
            break;
    }

    imagedestroy($sourceImage);
    imagedestroy($finalImage);

    if ($saved) {
        $webUrl = Url::getImgComicSocialMediaUrl($targetFilename) . '?' . time();
        sendJson([
            'status' => 'success',
            'message' => $targetFilename,
            'details' => "{$resizeMode}/{$cropPosition}",
            'imageUrl' => $webUrl
        ]);
    } else {
        throw new Exception("Speichern fehlgeschlagen.");
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()]);
}
