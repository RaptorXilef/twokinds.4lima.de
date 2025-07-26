<?php
/**
 * Dies ist die Administrationsseite für den Social Media Bild-Generator mit Crop-Funktion.
 * Sie überprüft, welche Social Media Bilder fehlen und bietet die Möglichkeit, diese zu erstellen.
 * Die Bilder werden ausschließlich aus dem 'comic_hires'-Verzeichnis bezogen.
 * Das generierte Bild wird durch Beschneiden des oberen Teils des Quellbildes erstellt,
 * um das Zielformat 1200x630px zu erreichen.
 * Die Generierung erfolgt schrittweise über AJAX, um Speicherprobleme zu vermeiden.
 * Eine Verzögerung von 1000ms zwischen den Generierungen entlastet das System.
 * Zusätzlich wird nach jeder Generierung eine explizite Garbage Collection durchgeführt,
 * und eine kurze PHP-Pause eingefügt, um Speicherressourcen effizienter freizugeben
 * und das Betriebssystem zu entlasten.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: generator_image_socialmedia_crop.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in generator_image_socialmedia_crop.php gestartet.");

// Erhöhe das PHP-Speicherlimit, um Probleme mit großen Bildern zu vermeiden.
// 1G hat sich als optimaler Wert erwiesen, um Ruckeln zu vermeiden.
ini_set('memory_limit', '1G');
if ($debugMode)
    error_log("DEBUG: PHP memory_limit auf 1G gesetzt.");

// Aktiviere die explizite Garbage Collection, um Speicher effizienter zu verwalten.
gc_enable();
if ($debugMode)
    error_log("DEBUG: Garbage Collection aktiviert.");

// Starte die PHP-Sitzung. Notwendig für die Admin-Anmeldung.
session_start();
if ($debugMode)
    error_log("DEBUG: Session gestartet in generator_image_socialmedia_crop.php.");

// Logout-Funktion (wird über GET-Parameter ausgelöst)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    session_unset();     // Entfernt alle Session-Variablen
    session_destroy();   // Zerstört die Session
    header('Location: index.php'); // Weiterleitung zur Login-Seite
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
// Wenn nicht angemeldet, zur Login-Seite weiterleiten.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von generator_image_socialmedia_crop.php.");
    // Beende den Output Buffer, da wir umleiten und keine weitere Ausgabe wollen.
    ob_end_clean();
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in generator_image_socialmedia_crop.php angemeldet.");

// Pfade zu den benötigten Ressourcen und Verzeichnissen.
// Die 'assets'-Ordner liegen eine Ebene über 'admin'.
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$hiresDir = __DIR__ . '/../assets/comic_hires/';   // Quellverzeichnis für Hi-Res Comic-Bilder
$socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/'; // Zielverzeichnis für Social Media Bilder
if ($debugMode) {
    error_log("DEBUG: Pfade definiert: hiresDir=" . $hiresDir . ", socialMediaImageDir=" . $socialMediaImageDir);
}

// Stelle sicher, dass die GD-Bibliothek geladen ist.
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Social Media Bilder können nicht generiert werden. Bitte PHP-Konfiguration prüfen.";
    error_log("FEHLER: GD-Bibliothek nicht geladen in socialmedia_image_generator_crop.php");
    if ($debugMode)
        error_log("DEBUG: GD-Bibliothek nicht geladen. Bildgenerierung nicht möglich.");
} else {
    $gdError = null;
    if ($debugMode)
        error_log("DEBUG: GD-Bibliothek ist geladen.");
}

/**
 * Scannt das hires-Verzeichnis nach vorhandenen Comic-Bildern.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingComicIds(string $hiresDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getExistingComicIds() aufgerufen für: " . $hiresDir);
    $comicIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (is_dir($hiresDir)) {
        $files = scandir($hiresDir);
        if ($files === false) {
            if ($debugMode)
                error_log("DEBUG: scandir() fehlgeschlagen für " . $hiresDir);
            return [];
        }
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $comicIds[$fileInfo['filename']] = true; // Assoziatives Array für Eindeutigkeit
                if ($debugMode)
                    error_log("DEBUG: Comic-ID gefunden: " . $fileInfo['filename']);
            }
        }
    } else {
        if ($debugMode)
            error_log("DEBUG: hiresDir nicht gefunden oder ist kein Verzeichnis: " . $hiresDir);
    }
    if ($debugMode)
        error_log("DEBUG: " . count($comicIds) . " vorhandene Comic-IDs gefunden.");
    return array_keys($comicIds); // Eindeutige Dateinamen zurückgeben
}

/**
 * Scannt das Social Media Bild-Verzeichnis nach vorhandenen Bildern.
 * @param string $socialMediaImageDir Pfad zum Social Media Bild-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste vorhandener Social Media Bild-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingSocialMediaImageIds(string $socialMediaImageDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: getExistingSocialMediaImageIds() aufgerufen für: " . $socialMediaImageDir);
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Prüfe auf gängige Bild-Erweiterungen

    if (is_dir($socialMediaImageDir)) {
        $files = scandir($socialMediaImageDir);
        if ($files === false) {
            if ($debugMode)
                error_log("DEBUG: scandir() fehlgeschlagen für " . $socialMediaImageDir);
            return [];
        }
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $imageIds[] = $fileInfo['filename'];
                if ($debugMode)
                    error_log("DEBUG: Social Media Bild-ID gefunden: " . $fileInfo['filename']);
            }
        }
    } else {
        if ($debugMode)
            error_log("DEBUG: socialMediaImageDir nicht gefunden oder ist kein Verzeichnis: " . $socialMediaImageDir);
    }
    if ($debugMode)
        error_log("DEBUG: " . count($imageIds) . " vorhandene Social Media Bild-IDs gefunden.");
    return $imageIds;
}

/**
 * Vergleicht alle vorhandenen Comic-IDs mit bereits vorhandenen Social Media Bild-IDs.
 * @param array $allComicIds Alle gefundenen Comic-IDs.
 * @param array $existingSocialMediaImageIds Alle gefundenen Social Media Bild-IDs.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Eine Liste von Comic-IDs, für die Social Media Bilder fehlen.
 */
function findMissingSocialMediaImages(array $allComicIds, array $existingSocialMediaImageIds, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: findMissingSocialMediaImages() aufgerufen.");
    $missing = array_values(array_diff($allComicIds, $existingSocialMediaImageIds));
    if ($debugMode)
        error_log("DEBUG: " . count($missing) . " fehlende Social Media Bilder gefunden.");
    return $missing;
}

/**
 * Generiert ein einzelnes Social Media Bild aus einem Quellbild durch Beschneiden.
 * Zielgröße: 1200x630 Pixel (typisches Open Graph Bildformat).
 * Das Bild wird vom oberen Rand des Quellbildes beschnitten, um das Zielformat zu erreichen.
 * @param string $comicId Die ID des Comics, für das ein Social Media Bild erstellt werden soll.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $socialMediaImageDir Pfad zum Social Media Bild-Verzeichnis.
 * @param bool $debugMode Debug-Modus Flag.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellter Pfad) und 'errors' (Fehlermeldungen).
 */
function generateSocialMediaImage(string $comicId, string $hiresDir, string $socialMediaImageDir, bool $debugMode): array
{
    if ($debugMode)
        error_log("DEBUG: generateSocialMediaImage() aufgerufen für Comic-ID: " . $comicId);
    $errors = [];
    $createdPath = '';

    // Erstelle den Zielordner, falls er nicht existiert.
    if (!is_dir($socialMediaImageDir)) {
        if ($debugMode)
            error_log("DEBUG: Zielverzeichnis existiert nicht, versuche zu erstellen: " . $socialMediaImageDir);
        if (!mkdir($socialMediaImageDir, 0755, true)) {
            $errors[] = "Fehler: Zielverzeichnis '$socialMediaImageDir' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.";
            error_log("FEHLER: Zielverzeichnis '$socialMediaImageDir' konnte nicht erstellt werden für Comic-ID $comicId.");
            return ['created' => $createdPath, 'errors' => $errors];
        }
        if ($debugMode)
            error_log("DEBUG: Zielverzeichnis erfolgreich erstellt: " . $socialMediaImageDir);
    } elseif (!is_writable($socialMediaImageDir)) {
        $errors[] = "Fehler: Zielverzeichnis '$socialMediaImageDir' ist nicht beschreibbar. Bitte Berechtigungen prüfen.";
        error_log("FEHLER: Zielverzeichnis '$socialMediaImageDir' ist nicht beschreibbar für Comic-ID $comicId.");
        if ($debugMode)
            error_log("DEBUG: Zielverzeichnis nicht beschreibbar: " . $socialMediaImageDir);
        return ['created' => $createdPath, 'errors' => $errors];
    }

    // Definiere die Zielabmessungen für Social Media Bilder
    $targetWidth = 1200;
    $targetHeight = 630;
    if ($debugMode)
        error_log("DEBUG: Zielgröße: " . $targetWidth . "x" . $targetHeight . "px.");

    $sourceImagePath = '';
    $sourceImageExtension = '';

    // Bild muss aus dem hires-Ordner kommen
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($possibleExtensions as $ext) {
        $hiresPath = $hiresDir . $comicId . '.' . $ext;
        if (file_exists($hiresPath)) {
            $sourceImagePath = $hiresPath;
            $sourceImageExtension = $ext;
            if ($debugMode)
                error_log("DEBUG: Quellbild gefunden: " . $sourceImagePath);
            break;
        }
    }

    if (empty($sourceImagePath)) {
        $errors[] = "Quellbild für Comic-ID '$comicId' nicht gefunden im '$hiresDir' Verzeichnis.";
        error_log("FEHLER: Quellbild für Comic-ID '$comicId' nicht gefunden.");
        if ($debugMode)
            error_log("DEBUG: Quellbild nicht gefunden für Comic-ID: " . $comicId);
        return ['created' => $createdPath, 'errors' => $errors];
    }

    try {
        // Bildinformationen abrufen
        $imageInfo = @getimagesize($sourceImagePath); // @ unterdrückt Warnungen
        if ($imageInfo === false) {
            $errors[] = "Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId). Möglicherweise ist die Datei beschädigt oder kein gültiges Bild.";
            error_log("FEHLER: Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId).");
            if ($debugMode)
                error_log("DEBUG: Fehler beim Abrufen der Bildinformationen für: " . $sourceImagePath);
            return ['created' => $createdPath, 'errors' => $errors];
        }
        list($width, $height, $type) = $imageInfo;
        if ($debugMode)
            error_log("DEBUG: Quellbild-Dimensionen: " . $width . "x" . $height . "px, Typ: " . $type);

        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($sourceImagePath);
                break;
            default:
                $errors[] = "Nicht unterstütztes Bildformat für Comic-ID '$comicId': " . $sourceImageExtension . ". Erwartet: JPG, PNG, GIF.";
                error_log("FEHLER: Nicht unterstütztes Bildformat für Comic-ID '$comicId': " . $sourceImageExtension);
                if ($debugMode)
                    error_log("DEBUG: Nicht unterstütztes Bildformat: " . $sourceImageExtension);
                return ['created' => $createdPath, 'errors' => $errors];
        }

        if (!$sourceImage) {
            $errors[] = "Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'. Möglicherweise ist der Speicher erschöpft oder das Bild ist korrupt.";
            error_log("FEHLER: Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.");
            if ($debugMode)
                error_log("DEBUG: Fehler beim Laden des Quellbildes: " . $sourceImagePath);
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Berechne die Skalierung, um die Zielbreite zu erreichen
        $scale = $targetWidth / $width;
        $scaledHeight = $height * $scale;
        if ($debugMode)
            error_log("DEBUG: Skalierungsfaktor: " . $scale . ", Skalierte Höhe: " . $scaledHeight . "px.");

        // Berechne den Y-Offset für den Crop, um den oberen Teil zu erhalten
        // Wenn die skalierte Höhe größer als die Zielhöhe ist, schneiden wir von oben ab.
        // Andernfalls zentrieren wir vertikal, um keine schwarzen Balken zu haben.
        $srcY = 0; // Standardmäßig von oben croppen
        if ($scaledHeight < $targetHeight) {
            // Wenn das Bild nach Skalierung zu kurz ist, zentriere es vertikal
            $srcY = ($height - ($targetHeight / $scale)) / 2;
            $scaledHeight = $targetHeight; // Setze skalierte Höhe auf Zielhöhe, um den gesamten Canvas zu füllen
            if ($debugMode)
                error_log("DEBUG: Bild ist nach Skalierung zu kurz, vertikal zentriert. srcY: " . (int) $srcY);
        } else {
            if ($debugMode)
                error_log("DEBUG: Bild von oben beschnitten. srcY: " . (int) $srcY);
        }


        // Erstelle ein neues True-Color-Bild für das Social Media Bild
        $tempImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($tempImage === false) {
            $errors[] = "Fehler beim Erstellen des temporären Bildes für Comic-ID '$comicId'.";
            error_log("FEHLER: Fehler beim Erstellen des temporären Bildes für Comic-ID '$comicId'.");
            imagedestroy($sourceImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }
        if ($debugMode)
            error_log("DEBUG: Temporäres Bild (" . $targetWidth . "x" . $targetHeight . "px) erstellt.");

        // Für PNGs: Transparenz erhalten
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
            if ($debugMode)
                error_log("DEBUG: Transparenz für PNG aktiviert.");
        }

        // Fülle den Hintergrund mit Weiß (oder einer anderen passenden Farbe)
        $backgroundColor = imagecolorallocate($tempImage, 255, 255, 255); // Weißer Hintergrund
        imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $backgroundColor);
        if ($debugMode)
            error_log("DEBUG: Hintergrund des temporären Bildes gefüllt.");

        // Bild auf die neue Größe und Position resamplen und kopieren
        // crop von srcY, srcX ist 0
        if (
            !imagecopyresampled(
                $tempImage,
                $sourceImage,
                0,
                0,
                0,
                (int) $srcY,
                $targetWidth,
                $scaledHeight,
                $width,
                $height - (2 * (int) $srcY)
            )
        ) { // Adjust source height for crop
            $errors[] = "Fehler beim Resampling/Cropping des Bildes für Comic-ID '$comicId'.";
            error_log("FEHLER: Fehler beim Resampling/Cropping des Bildes für Comic-ID '$comicId'.");
            imagedestroy($sourceImage);
            imagedestroy($tempImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }
        if ($debugMode)
            error_log("DEBUG: Bild resampled und gecroppt.");

        // Bild als JPG speichern (für Social Media empfohlen)
        $socialMediaImagePath = $socialMediaImageDir . $comicId . '.jpg';
        if (imagejpeg($tempImage, $socialMediaImagePath, 90)) { // 90% Qualität
            $createdPath = $socialMediaImagePath;
            if ($debugMode)
                error_log("DEBUG: Social Media Bild erfolgreich gespeichert: " . $socialMediaImagePath);
        } else {
            $errors[] = "Fehler beim Speichern des Social Media Bildes für Comic-ID '$comicId' nach '$socialMediaImagePath'. Möglicherweise sind die Dateiberechtigungen falsch oder das Verzeichnis existiert nicht.";
            error_log("FEHLER: Fehler beim Speichern des Social Media Bildes für Comic-ID '$comicId' nach '$socialMediaImagePath'.");
            if ($debugMode)
                error_log("DEBUG: Fehler beim Speichern des Bildes: " . $socialMediaImagePath);
        }

        // Speicher freigeben
        imagedestroy($sourceImage);
        imagedestroy($tempImage);
        if ($debugMode)
            error_log("DEBUG: Bildressourcen freigegeben.");

    } catch (Throwable $e) { // Throwable fängt auch Errors (z.B. Memory Exhaustion) ab
        $errors[] = "Ausnahme/Fehler bei Comic-ID '$comicId': " . $e->getMessage() . " (Code: " . $e->getCode() . " in " . $e->getFile() . " Zeile " . $e->getLine() . ")";
        error_log("KRITISCHER FEHLER: Bei Comic-ID '$comicId': " . $e->getMessage() . " in " . $e->getFile() . " Zeile " . $e->getLine());
        if ($debugMode)
            error_log("DEBUG: Kritischer Fehler in generateSocialMediaImage: " . $e->getMessage());
    } finally {
        // Führe nach jeder Bildgenerierung eine explizite Garbage Collection durch
        gc_collect_cycles();
        // Füge eine kurze Pause ein, um dem System Zeit zur Ressourcenfreigabe zu geben
        usleep(50000); // 50 Millisekunden Pause
        if ($debugMode)
            error_log("DEBUG: Garbage Collection und usleep() nach Bildgenerierung.");
    }
    return ['created' => $createdPath, 'errors' => $errors];
}

// --- AJAX-Anfrage-Handler ---
// Dieser Block wird nur ausgeführt, wenn eine POST-Anfrage mit der Aktion 'generate_single_social_media_image' gesendet wird.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_single_social_media_image') {
    if ($debugMode)
        error_log("DEBUG: AJAX-Anfrage 'generate_single_social_media_image' erkannt.");
    // Leere und beende den Output Buffer, um sicherzustellen, dass keine unerwünschten Ausgaben gesendet werden.
    ob_end_clean();
    // Temporär Fehleranzeige deaktivieren und Error Reporting unterdrücken, um JSON-Ausgabe nicht zu stören.
    ini_set('display_errors', 0);
    error_reporting(0);

    // Setze den Content-Type für die JSON-Antwort.
    header('Content-Type: application/json');

    $response = ['success' => false, 'message' => ''];

    // Prüfe, ob GD geladen ist, bevor Bildoperationen versucht werden.
    if (!extension_loaded('gd')) {
        $response['message'] = "FEHLER: Die GD-Bibliothek ist nicht geladen. Social Media Bilder können nicht generiert werden.";
        error_log("AJAX-Anfrage: GD-Bibliothek nicht geladen.");
        if ($debugMode)
            error_log("DEBUG: AJAX: GD-Bibliothek nicht geladen, sende Fehlerantwort.");
        echo json_encode($response);
        exit;
    }

    $comicId = $_POST['comic_id'] ?? '';
    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID für die Generierung angegeben.';
        error_log("AJAX-Anfrage: Keine Comic-ID angegeben.");
        if ($debugMode)
            error_log("DEBUG: AJAX: Keine Comic-ID angegeben, sende Fehlerantwort.");
        echo json_encode($response);
        exit;
    }
    if ($debugMode)
        error_log("DEBUG: AJAX: Starte Generierung für Comic-ID: " . $comicId);

    // Pfade für die einzelne Generierung (müssen hier neu definiert werden, da es ein separater Request ist)
    $hiresDir = __DIR__ . '/../assets/comic_hires/';
    $socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/';

    $result = generateSocialMediaImage($comicId, $hiresDir, $socialMediaImageDir, $debugMode);

    if (empty($result['errors'])) {
        $response['success'] = true;
        $response['message'] = 'Social Media Bild für ' . $comicId . ' erfolgreich erstellt.';
        $response['imageUrl'] = '../assets/comic_socialmedia/' . $comicId . '.jpg?' . time();
        $response['comicId'] = $comicId;
        if ($debugMode)
            error_log("DEBUG: AJAX: Bild für Comic-ID " . $comicId . " erfolgreich generiert.");
    } else {
        // Gib die spezifischen Fehlermeldungen aus der Generierungsfunktion zurück
        $response['message'] = 'Fehler bei der Erstellung für ' . $comicId . ': ' . implode(', ', $result['errors']);
        error_log("AJAX-Anfrage: Fehler bei der Generierung für Comic-ID '$comicId': " . implode(', ', $result['errors']));
        if ($debugMode)
            error_log("DEBUG: AJAX: Fehler bei der Generierung für Comic-ID " . $comicId . ": " . implode(', ', $result['errors']));
    }

    // Überprüfe, ob json_encode einen Fehler hatte
    $jsonOutput = json_encode($response);
    if ($jsonOutput === false) {
        $jsonError = json_last_error_msg();
        error_log("AJAX-Anfrage: json_encode Fehler für Comic-ID '$comicId': " . $jsonError);
        if ($debugMode)
            error_log("DEBUG: AJAX: JSON-Encoding fehlgeschlagen für Comic-ID " . $comicId . ": " . $jsonError);
        echo json_encode(['success' => false, 'message' => 'Interner Serverfehler: JSON-Encoding fehlgeschlagen.']);
    } else {
        echo $jsonOutput;
    }
    exit; // WICHTIG: Beende die Skriptausführung für AJAX-Anfragen hier!
}
// --- Ende AJAX-Anfrage-Handler ---


// --- Normaler Seitenaufbau (wenn keine AJAX-Anfrage vorliegt) ---
// Leere den Output Buffer und sende den Inhalt, der bis hierhin gesammelt wurde.
ob_end_flush();
if ($debugMode)
    error_log("DEBUG: Normale Seitenladung (kein AJAX-Call). Output Buffer wird geleert und ausgegeben.");


// Variablen für die Anzeige initialisieren
$allComicIds = getExistingComicIds($hiresDir, $debugMode);
$existingSocialMediaImageIds = getExistingSocialMediaImageIds($socialMediaImageDir, $debugMode);
$missingSocialMediaImages = findMissingSocialMediaImages($allComicIds, $existingSocialMediaImageIds, $debugMode);
if ($debugMode)
    error_log("DEBUG: " . count($missingSocialMediaImages) . " fehlende Social Media Bilder für Anzeige.");

// Gemeinsamen Header einbinden.
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in generator_image_socialmedia_crop.php eingebunden.");
} else {
    // Fallback oder Fehlerbehandlung, falls Header nicht gefunden wird.
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><h1>Fehler: Header nicht gefunden!</h1>";
    if ($debugMode)
        error_log("DEBUG: Header-Datei nicht gefunden, Fallback-HTML ausgegeben.");
}

// Basis-URL für die Bildanzeige bestimmen
$socialMediaImageWebPath = '../assets/comic_socialmedia/';
?>

<article>
    <header>
        <h1>Social Media Bild-Generator (Crop)</h1>
    </header>

    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
            <?php if ($debugMode)
                error_log("DEBUG: GD-Fehlermeldung auf der Seite angezeigt."); ?>
        <?php endif; ?>

        <h2>Status der Social Media Bilder (Crop)</h2>

        <!-- Container für die Buttons - JETZT HIER PLATZIERT -->
        <div id="fixed-buttons-container">
            <button type="button" id="generate-images-button" <?php echo $gdError || empty($missingSocialMediaImages) ? 'disabled' : ''; ?>>Fehlende Social Media Bilder erstellen</button>
            <button type="button" id="toggle-pause-resume-button" style="display:none;"></button>
        </div>

        <!-- Ergebnisse der Generierung -->
        <div id="generation-results-section" style="margin-top: 20px; display: none;">
            <h2 style="margin-top: 20px;">Ergebnisse der Generierung</h2>
            <p id="overall-status-message" class="status-message"></p>
            <div id="created-images-container" class="image-grid">
                <!-- Hier werden die erfolgreich generierten Bilder angezeigt -->
            </div>
            <p class="status-message status-red" style="display: none;" id="error-header-message">Fehler bei der
                Generierung:</p>
            <ul id="generation-errors-list">
                <!-- Hier werden Fehler angezeigt -->
            </ul>
        </div>

        <!-- Lade-Indikator und Fortschrittsanzeige (JETZT ZWISCHEN ERGEBNISSEN UND FEHLENDEN BILDERN) -->
        <div id="loading-spinner" style="display: none; text-align: center; margin-top: 20px;">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Social Media Bilder...</p>
        </div>

        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Es wurden keine Comic-Bilder im Verzeichnis
                `<?php echo htmlspecialchars($hiresDir); ?>` gefunden, die als Basis dienen könnten.</p>
            <?php if ($debugMode)
                error_log("DEBUG: Keine Comic-Bilder im hiresDir gefunden, Statusmeldung angezeigt."); ?>
        <?php elseif (empty($missingSocialMediaImages)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Social Media Bilder (Crop) sind
                vorhanden.</p>
            <?php if ($debugMode)
                error_log("DEBUG: Alle Social Media Bilder vorhanden, Statusmeldung angezeigt."); ?>
        <?php else: ?>
            <p class="status-message status-red">Es fehlen <?php echo count($missingSocialMediaImages); ?> Social Media
                Bilder (Crop).</p>
            <h3>Fehlende Social Media Bilder (IDs):</h3>
            <!-- Geänderter Bereich für die Anzeige der fehlenden Bilder -->
            <div id="missing-images-grid" class="missing-items-grid">
                <?php foreach ($missingSocialMediaImages as $id): ?>
                    <span class="missing-item"
                        data-comic-id="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?></span>
                <?php endforeach; ?>
            </div>
            <?php if ($debugMode)
                error_log("DEBUG: Fehlende Social Media Bilder angezeigt."); ?>
        <?php endif; ?>
    </div>
</article>

<style>
    /* CSS-Variablen für Light- und Dark-Mode */
    :root {
        /* Light Mode Defaults */
        --missing-grid-border-color: #e0e0e0;
        --missing-grid-bg-color: #f9f9f9;
        --missing-item-bg-color: #e9e9e9;
        --missing-item-text-color: #333;
        /* Standardtextfarbe */
    }

    body.theme-night {
        /* GEÄNDERT: von body.dark-mode zu body.theme-night */
        /* Dark Mode Overrides */
        --missing-grid-border-color: #045d81;
        --missing-grid-bg-color: #03425b;
        --missing-item-bg-color: #025373;
        --missing-item-text-color: #f0f0f0;
        /* Hellerer Text für Dark Mode */
    }

    /* Allgemeine Statusmeldungen */
    .status-message {
        padding: 8px 12px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .status-green {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .status-orange {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .status-red {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Neue Button-Stile */
    .status-red-button {
        background-color: #dc3545;
        /* Bootstrap-Rot */
        color: white;
        border: 1px solid #dc3545;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
    }

    .status-red-button:hover {
        background-color: #c82333;
    }

    .status-red-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #e9ecef;
        cursor: not-allowed;
    }

    .status-green-button {
        background-color: #28a745;
        /* Bootstrap-Grün */
        color: white;
        border: 1px solid #28a745;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.2s ease;
    }

    .status-green-button:hover {
        background-color: #218838;
    }

    .status-green-button:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        border-color: #e9ecef;
        cursor: not-allowed;
    }

    /* Spinner CSS */
    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border-left-color: #09f;
        animation: spin 1s ease infinite;
        margin: 0 auto 10px auto;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Image Grid Layout (für Social Media Bilder) */
    .image-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
        padding-bottom: 20px;
    }

    .image-item {
        text-align: center;
        border: 1px solid #ccc;
        padding: 5px;
        border-radius: 8px;
        width: calc(50% - 5px);
        /* Für 2 Bilder pro Reihe, da sie breiter sind */
        min-width: 300px;
        /* Mindestbreite für Responsivität */
        height: auto;
        /* Flexibel in der Höhe */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-sizing: border-box;
        overflow: hidden;
    }

    .image-item img {
        display: block;
        max-width: 100%;
        height: auto;
        /* Wichtig für proportionale Skalierung */
        object-fit: contain;
        border-radius: 4px;
        margin-bottom: 5px;
    }

    .image-item span {
        word-break: break-all;
        font-size: 0.8em;
    }

    /* Responsive Anpassungen für den Image-Grid */
    @media (max-width: 1000px) {
        .image-item {
            width: 100%;
            /* 1 pro Reihe auf kleineren Bildschirmen */
        }
    }

    /* Stil für den Button-Container - initial statisch, wird per JS zu 'fixed' */
    #fixed-buttons-container {
        z-index: 1000;
        /* Stellt sicher, dass die Buttons über anderen Inhalten liegen */
        display: flex;
        /* Für nebeneinanderliegende Buttons */
        gap: 10px;
        /* Abstand zwischen den Buttons */
        margin-top: 20px;
        /* Fügt etwas Abstand hinzu, wenn die Buttons statisch sind */
        margin-bottom: 20px;
        /* Abstand nach unten, wenn statisch */
        justify-content: flex-end;
        /* Richtet die Buttons im statischen Zustand am rechten Rand aus */
        /* top und right werden dynamisch per JavaScript gesetzt, position wird auch per JS gesetzt */
    }

    /* Anpassung für kleinere Bildschirme, falls die Buttons zu viel Platz einnehmen */
    @media (max-width: 768px) {
        #fixed-buttons-container {
            flex-direction: column;
            /* Buttons untereinander auf kleinen Bildschirmen */
            gap: 5px;
            align-items: flex-end;
            /* Auch im Spalten-Layout rechts ausrichten */
        }
    }

    /* NEUE STILE FÜR DIE KOMPAKTE LISTE DER FEHLENDEN ELEMENTE */
    .missing-items-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        /* Abstand zwischen den Elementen */
        max-height: 300px;
        /* Maximale Höhe */
        overflow-y: auto;
        /* Scrollbar, wenn Inhalt die Höhe überschreitet */
        border: 1px solid var(--missing-grid-border-color);
        /* Dynamischer Rahmen */
        padding: 10px;
        border-radius: 5px;
        background-color: var(--missing-grid-bg-color);
        /* Dynamischer Hintergrund */
    }

    .missing-item {
        background-color: var(--missing-item-bg-color);
        /* Dynamischer Hintergrund */
        color: var(--missing-item-text-color);
        /* Dynamische Textfarbe */
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.9em;
        white-space: nowrap;
        /* Verhindert Zeilenumbruch innerhalb eines Eintrags */
        overflow: hidden;
        text-overflow: ellipsis;
        /* Fügt "..." hinzu, wenn der Text zu lang ist */
        max-width: 150px;
        /* Begrenzt die Breite jedes Eintrags */
        flex-shrink: 0;
        /* Verhindert, dass Elemente schrumpfen */
    }
</style>

<script>
    // JavaScript-Logik bleibt unverändert, da sie keine direkten PHP-Debug-Meldungen verarbeitet.
    // Sie kann jedoch die PHP-Fehler in der Konsole sehen, wenn error_reporting aktiviert ist.
    document.addEventListener('DOMContentLoaded', function () {
        const generateButton = document.getElementById('generate-images-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingImagesGrid = document.getElementById('missing-images-grid'); // ID geändert
        const createdImagesContainer = document.getElementById('created-images-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const overallStatusMessage = document.getElementById('overall-status-message');
        const errorHeaderMessage = document.getElementById('error-header-message');
        const errorsList = document.getElementById('generation-errors-list');

        // Die Liste der fehlenden IDs, direkt von PHP übergeben
        const initialMissingIds = <?php echo json_encode($missingSocialMediaImages); ?>;
        let remainingIds = [...initialMissingIds];
        let createdCount = 0;
        let errorCount = 0;
        let isPaused = false; // Status für die Pause-Funktion
        let isGenerationActive = false; // Neuer Flag, um zu verfolgen, ob die Generierung läuft

        // Elemente für die Positionierung der Buttons
        const mainContent = document.getElementById('content'); // Das Haupt-Content-Element
        const fixedButtonsContainer = document.getElementById('fixed-buttons-container');

        // Sicherheitscheck: Wenn der Button-Container nicht gefunden wird, breche ab.
        if (!fixedButtonsContainer) {
            console.error("Fehler: Das Element '#fixed-buttons-container' wurde nicht gefunden. Die Buttons können nicht positioniert werden.");
            return;
        }

        let initialButtonTopOffset; // Die absolute Top-Position der Buttons im Dokument, wenn sie nicht fixed sind
        let stickyThreshold; // Der Scroll-Y-Wert, ab dem die Buttons fixiert werden sollen
        const stickyOffset = 18; // Gewünschter Abstand vom oberen Viewport-Rand, wenn sticky
        const rightOffset = 24; // Gewünschter Abstand vom rechten Rand des Main-Elements, wenn sticky

        /**
         * Berechnet die initialen Positionen und den Schwellenwert für das "Klebenbleiben".
         * Diese Funktion muss aufgerufen werden, wenn sich das Layout ändert (z.B. bei Fenstergröße).
         */
        function calculateInitialPositions() {
            // Sicherstellen, dass die Buttons nicht 'fixed' sind, um ihre natürliche Position zu ermitteln
            // Die CSS-Eigenschaft justify-content: flex-end; kümmert sich jetzt um die horizontale Ausrichtung im statischen Zustand.
            fixedButtonsContainer.style.position = 'static';
            fixedButtonsContainer.style.top = 'auto';
            fixedButtonsContainer.style.right = 'auto';

            // Die absolute Top-Position des Button-Containers im Dokument
            initialButtonTopOffset = fixedButtonsContainer.getBoundingClientRect().top + window.scrollY;

            // Der Schwellenwert: Wenn der Benutzer so weit scrollt, dass die Buttons
            // 'stickyOffset' (18px) vom oberen Viewport-Rand entfernt wären, sollen sie fixiert werden.
            stickyThreshold = initialButtonTopOffset - stickyOffset;

            if (!mainContent) {
                console.warn("Warnung: Das 'main' Element mit ID 'content' wurde nicht gefunden. Die rechte Position der Buttons wird relativ zum Viewport berechnet.");
            }
        }

        /**
         * Behandelt das Scroll-Ereignis, um die Buttons zu fixieren oder freizugeben.
         */
        function handleScroll() {
            if (!fixedButtonsContainer) return; // Sicherheitscheck

            const currentScrollY = window.scrollY; // Aktuelle Scroll-Position

            if (currentScrollY >= stickyThreshold) {
                // Wenn der Scroll-Y-Wert den Schwellenwert erreicht oder überschreitet, fixiere die Buttons
                if (fixedButtonsContainer.style.position !== 'fixed') {
                    fixedButtonsContainer.style.position = 'fixed';
                    fixedButtonsContainer.style.top = `${stickyOffset}px`; // 18px vom oberen Viewport-Rand

                    // Berechne die rechte Position:
                    if (mainContent) {
                        const mainRect = mainContent.getBoundingClientRect();
                        // Abstand vom rechten Viewport-Rand zum rechten Rand des Main-Elements + gewünschter Offset
                        fixedButtonsContainer.style.right = (window.innerWidth - mainRect.right + rightOffset) + 'px';
                    } else {
                        // Fallback: Wenn mainContent nicht gefunden wird, positioniere relativ zum Viewport-Rand
                        fixedButtonsContainer.style.right = `${rightOffset}px`;
                    }
                }
            } else {
                // Wenn der Scroll-Y-Wert unter dem Schwellenwert liegt, gib die Buttons frei (normaler Fluss)
                if (fixedButtonsContainer.style.position === 'fixed') {
                    fixedButtonsContainer.style.position = 'static'; // Zurück zum normalen Fluss
                    fixedButtonsContainer.style.top = 'auto';
                    fixedButtonsContainer.style.right = 'auto';
                }
            }
        }

        /**
         * Behandelt das Resize-Ereignis, um Positionen neu zu berechnen und den Scroll-Status anzupassen.
         */
        function handleResize() {
            calculateInitialPositions(); // Positionen neu berechnen, da sich das Layout geändert haben könnte
            handleScroll(); // Den Sticky-Zustand basierend auf den neuen Positionen neu bewerten
        }

        // Initiales Setup beim Laden der Seite
        // Zuerst Positionen berechnen, dann den Scroll-Status anpassen
        calculateInitialPositions();
        handleScroll(); // Setze den initialen Zustand basierend auf der aktuellen Scroll-Position

        // Event Listener für Scroll- und Resize-Ereignisse
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('resize', handleResize);

        // Funktion zum Aktualisieren des Button-Zustands (Text, Farbe und Sichtbarkeit)
        function updateButtonState() {
            if (initialMissingIds.length === 0) {
                // Keine Bilder zum Generieren vorhanden
                generateButton.style.display = 'inline-block';
                generateButton.disabled = true;
                togglePauseResumeButton.style.display = 'none';
            } else if (isGenerationActive) {
                // Generierung ist aktiv oder pausiert
                generateButton.style.display = 'none';
                togglePauseResumeButton.style.display = 'inline-block';
                if (isPaused) {
                    togglePauseResumeButton.textContent = 'Generierung fortsetzen';
                    togglePauseResumeButton.className = 'status-green-button';
                } else {
                    togglePauseResumeButton.textContent = 'Generierung pausieren';
                    togglePauseResumeButton.className = 'status-red-button';
                }
                togglePauseResumeButton.disabled = false;
            } else if (remainingIds.length === 0 && createdCount + errorCount === initialMissingIds.length) {
                // Alle Bilder verarbeitet (Generierung abgeschlossen)
                generateButton.style.display = 'inline-block';
                generateButton.disabled = true; // Nichts mehr zu generieren
                togglePauseResumeButton.style.display = 'none';
            } else {
                // Initialer Zustand: Bilder zum Generieren vorhanden, aber noch nicht gestartet
                generateButton.style.display = 'inline-block';
                generateButton.disabled = false;
                togglePauseResumeButton.style.display = 'none';
            }
        }

        // Initialen Zustand der Buttons beim Laden der Seite setzen
        updateButtonState();

        if (generateButton) {
            generateButton.addEventListener('click', function () {
                if (initialMissingIds.length === 0) {
                    console.log('Keine Social Media Bilder zum Generieren vorhanden.');
                    return;
                }

                // UI zurücksetzen und Ladezustand anzeigen
                loadingSpinner.style.display = 'block';
                generationResultsSection.style.display = 'block';
                overallStatusMessage.textContent = '';
                overallStatusMessage.className = 'status-message'; // Klasse zurücksetzen
                createdImagesContainer.innerHTML = '';
                errorsList.innerHTML = '';
                errorHeaderMessage.style.display = 'none'; // Fehler-Header initial ausblenden

                // Setze remainingIds neu, falls der Button erneut geklickt wird nach Abschluss
                remainingIds = [...initialMissingIds];
                createdCount = 0;
                errorCount = 0;
                isPaused = false;

                isGenerationActive = true; // Generierung starten
                updateButtonState(); // Buttons anpassen (Generieren aus, Pause an)
                processNextImage();
            });
        }

        if (togglePauseResumeButton) {
            togglePauseResumeButton.addEventListener('click', function () {
                isPaused = !isPaused; // Zustand umschalten
                if (isPaused) {
                    progressText.textContent = `Generierung pausiert. ${createdCount + errorCount} von ${initialMissingIds.length} verarbeitet.`;
                }
                updateButtonState(); // Button-Text und Sichtbarkeit aktualisieren
                if (!isPaused) { // Wenn gerade fortgesetzt wurde
                    processNextImage(); // Generierung fortsetzen
                }
            });
        }

        async function processNextImage() {
            if (isPaused) {
                // Wenn pausiert, beende die Ausführung, bis fortgesetzt wird
                return;
            }

            if (remainingIds.length === 0) {
                // Alle Bilder verarbeitet
                loadingSpinner.style.display = 'none';
                progressText.textContent = `Generierung abgeschlossen. ${createdCount} erfolgreich, ${errorCount} Fehler.`;
                isGenerationActive = false; // Generierung beendet
                updateButtonState(); // Buttons anpassen (Toggle aus, Generieren an)

                if (errorCount > 0) {
                    overallStatusMessage.textContent = `Generierung abgeschlossen mit Fehlern: ${createdCount} erfolgreich, ${errorCount} Fehler.`;
                    overallStatusMessage.className = 'status-message status-orange';
                    errorHeaderMessage.style.display = 'block';
                } else {
                    overallStatusMessage.textContent = `Alle ${createdCount} Social Media Bilder erfolgreich generiert!`;
                    overallStatusMessage.className = 'status-message status-green';
                }
                return;
            }

            const currentId = remainingIds.shift(); // Nächste ID aus der Liste nehmen
            progressText.textContent = `Generiere Social Media Bild ${createdCount + errorCount + 1} von ${initialMissingIds.length} (${currentId})...`;

            try {
                const response = await fetch(window.location.href, { // Anfrage an dasselbe PHP-Skript
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'generate_single_social_media_image', // Spezifische Aktion für AJAX
                        comic_id: currentId
                    })
                });

                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    const responseText = await response.text();
                    throw new Error(`Fehler beim Parsen der JSON-Antwort für ${currentId}: ${jsonError.message}. Antwort war: ${responseText.substring(0, 200)}...`);
                }


                if (data.success) {
                    createdCount++;
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'image-item';
                    imageDiv.innerHTML = `
                    <img src="${data.imageUrl}" alt="Social Media Bild ${data.comicId}">
                    <span>${data.comicId}</span>
                `;
                    createdImagesContainer.appendChild(imageDiv);

                    // Entferne das Element aus dem Grid der fehlenden Bilder
                    if (missingImagesGrid) {
                        const missingItemSpan = missingImagesGrid.querySelector(`span[data-comic-id="${data.comicId}"]`);
                        if (missingItemSpan) {
                            missingItemSpan.remove();
                        }
                    }

                } else {
                    errorCount++;
                    const errorItem = document.createElement('li');
                    errorItem.textContent = `Fehler für ${currentId}: ${data.message}`;
                    errorsList.appendChild(errorItem);
                    errorHeaderMessage.style.display = 'block';
                }
            } catch (error) {
                errorCount++;
                const errorItem = document.createElement('li');
                errorItem.textContent = `Netzwerkfehler oder unerwartete Antwort für ${currentId}: ${error.message}`;
                errorsList.appendChild(errorItem);
                errorHeaderMessage.style.display = 'block';
            }

            // Fügen Sie hier eine kleine Verzögerung ein, bevor das nächste Bild verarbeitet wird
            setTimeout(() => {
                processNextImage();
            }, 1000); // 1000 Millisekunden (1 Sekunde) Verzögerung
        }
    });
</script>

<?php
// Gemeinsamen Footer einbinden.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in generator_image_socialmedia_crop.php eingebunden.");
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
    if ($debugMode)
        error_log("DEBUG: Footer-Datei nicht gefunden, HTML manuell geschlossen.");
}
?>