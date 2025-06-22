<?php
// src/includes/functions.php

// Lade die globale Konfiguration
require_once __DIR__ . '/../config/app_config.php';

/**
 * Überprüft, ob der Benutzer eingeloggt ist und leitet bei Bedarf um.
 *
 * @param bool $redirectToLogin Ob bei fehlendem Login zur Login-Seite umgeleitet werden soll.
 * @return bool True, wenn eingeloggt; False, wenn nicht eingeloggt und nicht umgeleitet wurde.
 */
function checkAdminLogin($redirectToLogin = true) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['username'])) {
        if ($redirectToLogin) {
            header('Location: ' . ADMIN_DIR . '/login.php'); // Pfad zur Login-Seite
            exit();
        }
        return false;
    }
    return true;
}

/**
 * Lädt ein Template und übergibt Variablen an dieses.
 *
 * @param string $templatePath Der absolute Pfad zur Template-Datei.
 * @param array $data Ein assoziatives Array von Variablen, die im Template verfügbar sein sollen.
 */
function loadTemplate($templatePath, $data = []) {
    extract($data); // Extrahieren der Array-Elemente als Variablen
    if (file_exists($templatePath)) {
        require $templatePath;
    } else {
        error_log("Template-Datei nicht gefunden: " . $templatePath);
        // Optional: Eine Fehlermeldung auf der Seite anzeigen
        echo "";
    }
}

/**
 * Generiert HTML-Links für Bilder basierend auf Startbildern und Anzahl.
 * Annahme: comicnamen.php wird zu src/data/comic_names.php und ist ein PHP-Array.
 *
 * @param string $firstImage Die Start-Bild-ID (z.B. '20031022').
 * @param int $imageCount Die Anzahl der zu generierenden Bilder.
 * @param string $thumbnailsDirForAdmin Verzeichnis für Thumbnails im Admin (z.B. '../thumbnails/').
 * @param string $comicPHPfileDirForArchiv Verzeichnis für Comic-PHP-Dateien (z.B. '').
 * @param string $thumbnailsDirForArchiv Relatives Verzeichnis für Thumbnails im Archiv (z.B. 'thumbnails/').
 * @return string Der generierte HTML-Code.
 */
function generateImageLinks(
    $firstImage,
    $imageCount,
    $thumbnailsDirForAdmin,
    $comicPHPfileDirForArchiv,
    $thumbnailsDirForArchiv
) {
    $html = '';
    $currentImage = $firstImage;
    $pictureNumber = 1;

    // Lade die Comicnamen-Daten (angenommen als PHP-Array in src/data/comic_names.php)
    // Umwandlung von comicnamen.php zu einem PHP-Array in comic_names.php ist hier ideal.
    // Beispiel: $comicNames = ['20031022' => '', '20031024' => '', ...];
    static $comicNames = null; // Statische Variable, damit die Datei nur einmal geladen wird
    if ($comicNames === null) {
        if (file_exists(COMIC_NAMES_FILE)) {
            $comicNames = include COMIC_NAMES_FILE; // comic_names.php sollte ein Array zurückgeben
        } else {
            $comicNames = [];
            error_log("Comicnamen-Datei nicht gefunden: " . COMIC_NAMES_FILE);
        }
    }


    for ($i = 0; $i < $imageCount; $i++) {
        // Sicherstellen, dass das Datum 8 Ziffern hat
        $paddedImage = sprintf("%08d", $currentImage);
        $comicTitle = isset($comicNames['comicNameInput' . $paddedImage]) ? $comicNames['comicNameInput' . $paddedImage] : '';
        $comicType = isset($comicNames['comicTypInput' . $paddedImage]) ? $comicNames['comicTypInput' . $paddedImage] : '';

        // Prüfen, ob die Bilddatei existiert (für den öffentlichen Bereich relevant)
        // Die Pfade hier sind für die Generierung der Links im Archiv, nicht für die Existenzprüfung im Adminbereich
        // Pfad für das Bild im Archiv (z.B. 20031022.php)
        $comicPagePath = $comicPHPfileDirForArchiv . $paddedImage . '.php';

        $html .= '<a href="' . $comicPagePath . '" class="comicthumb lazy" title="' . htmlspecialchars($comicType . $paddedImage . ': ' . $comicTitle) . '" data-src="' . $thumbnailsDirForArchiv . $paddedImage . '.png">' . PHP_EOL;
        $html .= '<span>' . $pictureNumber . '</span>' . PHP_EOL;
        $html .= '</a>' . PHP_EOL;
        $pictureNumber++;

        // Nächstes Datum berechnen
        $currentImage = date('Ymd', strtotime($currentImage . ' +1 day'));
    }
    return $html;
}

// Zeitlimit setzen
set_time_limit(TIME_LIMIT);

// Fehlermeldungen anzeigen (nur für Entwicklungsumgebung)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Füge hier weitere allgemeine Funktionen hinzu
?>