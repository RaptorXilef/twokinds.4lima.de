<?php
/**
 * Dies ist die Administrationsseite für den Thumbnail-Generator.
 * Sie überprüft, welche Thumbnails fehlen und bietet die Möglichkeit, diese zu erstellen.
 * Die Generierung erfolgt nun schrittweise über AJAX, um Speicherprobleme bei vielen Bildern zu vermeiden.
 * Eine Verzögerung von 1000ms zwischen den Generierungen entlastet das System.
 * Zusätzlich wird nach jeder Generierung eine explizite Garbage Collection durchgeführt,
 * und eine kurze PHP-Pause eingefügt, um Speicherressourcen effizienter freizugeben
 * und das Betriebssystem zu entlasten.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: generator_thumbnail.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer gestartet.");

// Erhöhe das PHP-Speicherlimit, um Probleme mit großen Bildern zu vermeiden.
// 512M sollte für Thumbnails ausreichend sein, aber 1G ist auch möglich für Konsistenz.
ini_set('memory_limit', '512M');
if ($debugMode)
    error_log("DEBUG: Speicherlimit auf 512M gesetzt.");

// Aktiviere die explizite Garbage Collection, um Speicher effizienter zu verwalten.
gc_enable();
if ($debugMode)
    error_log("DEBUG: Garbage Collection aktiviert.");

// Starte die PHP-Sitzung. Notwendig für die Admin-Anmeldung.
session_start();
if ($debugMode)
    error_log("DEBUG: Session gestartet in generator_thumbnail.php.");

// Logout-Funktion (wird über GET-Parameter ausgelöst)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    session_unset();     // Entfernt alle Session-Variablen
    session_destroy();   // Zerstört die Session
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php'); // Weiterleitung zur Login-Seite
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
// Wenn nicht angemeldet, zur Login-Seite weiterleiten.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($debugMode)
        error_log("DEBUG: Nicht angemeldet, Weiterleitung zur Login-Seite von generator_thumbnail.php.");
    // Beende den Output Buffer, da wir umleiten und keine weitere Ausgabe wollen.
    ob_end_clean();
    header('Location: index.php');
    exit;
}
if ($debugMode)
    error_log("DEBUG: Admin in generator_thumbnail.php angemeldet.");


// Pfade zu den benötigten Ressourcen und Verzeichnissen.
// Die 'assets'-Ordner liegen eine Ebene über 'admin'.
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$lowresDir = __DIR__ . '/../assets/comic_lowres/'; // Quellverzeichnis für Comic-Bilder
$hiresDir = __DIR__ . '/../assets/comic_hires/';   // Optionales Quellverzeichnis für Hi-Res Comic-Bilder
$thumbnailDir = __DIR__ . '/../assets/comic_thumbnails/'; // Zielverzeichnis für Thumbnails
if ($debugMode)
    error_log("DEBUG: Verzeichnispfade definiert.");

// Stelle sicher, dass die GD-Bibliothek geladen ist.
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Thumbnails können nicht generiert werden. Bitte PHP-Konfiguration prüfen.";
    error_log("GD-Bibliothek nicht geladen in thumbnail_generator.php");
    if ($debugMode)
        error_log("DEBUG: GD-Bibliothek nicht geladen.");
} else {
    $gdError = null;
    if ($debugMode)
        error_log("DEBUG: GD-Bibliothek geladen.");
}

/**
 * Scannt die Comic-Verzeichnisse nach vorhandenen Comic-Bildern.
 * Priorisiert Bilder im lowres-Ordner.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingComicIds(string $lowresDir, string $hiresDir, bool $debugMode): array
{
    $comicIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    // Scan lowres-Verzeichnis nach Bildern
    if (is_dir($lowresDir)) {
        $files = scandir($lowresDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $comicIds[$fileInfo['filename']] = true; // Assoziatives Array für Eindeutigkeit
            }
        }
        if ($debugMode)
            error_log("DEBUG: " . count($comicIds) . " IDs aus lowresDir gefunden.");
    }

    // Scan hires-Verzeichnis nach Bildern, nur hinzufügen, wenn nicht bereits in lowres gefunden
    if (is_dir($hiresDir)) {
        $files = scandir($hiresDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $comicIds[$fileInfo['filename']] = true;
            }
        }
        if ($debugMode)
            error_log("DEBUG: " . count($comicIds) . " IDs (inkl. hiresDir) gefunden.");
    }
    if ($debugMode)
        error_log("DEBUG: Gesamtzahl der Comic-IDs: " . count($comicIds));
    return array_keys($comicIds); // Eindeutige Dateinamen zurückgeben
}

/**
 * Scannt das Thumbnail-Verzeichnis nach vorhandenen Thumbnails.
 * @param string $thumbnailDir Pfad zum Thumbnail-Verzeichnis.
 * @return array Eine Liste vorhandener Thumbnail-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingThumbnailIds(string $thumbnailDir, bool $debugMode): array
{
    $thumbnailIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Prüfe auf gängige Bild-Erweiterungen

    if (is_dir($thumbnailDir)) {
        $files = scandir($thumbnailDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $thumbnailIds[] = $fileInfo['filename'];
            }
        }
        if ($debugMode)
            error_log("DEBUG: " . count($thumbnailIds) . " Thumbnails im Verzeichnis gefunden.");
    } else {
        if ($debugMode)
            error_log("DEBUG: Thumbnail-Verzeichnis nicht gefunden: " . $thumbnailDir);
    }
    return $thumbnailIds;
}

/**
 * Vergleicht alle vorhandenen Comic-IDs mit bereits vorhandenen Thumbnail-IDs.
 * @param array $allComicIds Alle gefundenen Comic-IDs.
 * @param array $existingThumbnailIds Alle gefundenen Thumbnail-IDs.
 * @return array Eine Liste von Comic-IDs, für die Thumbnails fehlen.
 */
function findMissingThumbnails(array $allComicIds, array $existingThumbnailIds, bool $debugMode): array
{
    $missing = array_values(array_diff($allComicIds, $existingThumbnailIds));
    if ($debugMode)
        error_log("DEBUG: Fehlende Thumbnails gefunden: " . count($missing));
    return $missing;
}

/**
 * Generiert ein einzelnes Thumbnail aus einem Quellbild.
 * Zielgröße: 187x250 Pixel, das Bild wird zentriert und proportional skaliert.
 * @param string $comicId Die ID des Comics, für das ein Thumbnail erstellt werden soll.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $thumbnailDir Pfad zum Thumbnail-Verzeichnis.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellter Pfad) und 'errors' (Fehlermeldungen).
 */
function generateThumbnail(string $comicId, string $lowresDir, string $hiresDir, string $thumbnailDir, bool $debugMode): array
{
    $errors = [];
    $createdPath = '';
    if ($debugMode)
        error_log("DEBUG: Starte Thumbnail-Generierung für Comic-ID: " . $comicId);

    // Erstelle den Zielordner, falls er nicht existiert.
    if (!is_dir($thumbnailDir)) {
        if (!mkdir($thumbnailDir, 0755, true)) {
            $errors[] = "Fehler: Zielverzeichnis '$thumbnailDir' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.";
            error_log("Fehler: Zielverzeichnis '$thumbnailDir' konnte nicht erstellt werden für Comic-ID $comicId.");
            if ($debugMode)
                error_log("DEBUG: Fehler: Zielverzeichnis konnte nicht erstellt werden.");
            return ['created' => $createdPath, 'errors' => $errors];
        }
        if ($debugMode)
            error_log("DEBUG: Zielverzeichnis erstellt: " . $thumbnailDir);
    } elseif (!is_writable($thumbnailDir)) {
        $errors[] = "Fehler: Zielverzeichnis '$thumbnailDir' ist nicht beschreibbar. Bitte Berechtigungen prüfen.";
        error_log("Fehler: Zielverzeichnis '$thumbnailDir' ist nicht beschreibbar für Comic-ID $comicId.");
        if ($debugMode)
            error_log("DEBUG: Fehler: Zielverzeichnis nicht beschreibbar.");
        return ['created' => $createdPath, 'errors' => $errors];
    }

    // Definiere die Zielabmessungen für Thumbnails
    $targetWidth = 198; // Korrigierte Breite
    $targetHeight = 258; // Korrigierte Höhe

    $sourceImagePath = '';
    $sourceImageExtension = '';

    // Priorisiere hires-Bild, ansonsten lowres-Bild
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($possibleExtensions as $ext) {
        $hiresPath = $hiresDir . $comicId . '.' . $ext;
        $lowresPath = $lowresDir . $comicId . '.' . $ext;

        if (file_exists($hiresPath)) {
            $sourceImagePath = $hiresPath;
            $sourceImageExtension = $ext;
            if ($debugMode)
                error_log("DEBUG: Quellbild in hiresDir gefunden: " . $sourceImagePath);
            break;
        } elseif (file_exists($lowresPath)) {
            $sourceImagePath = $lowresPath;
            $sourceImageExtension = $ext;
            if ($debugMode)
                error_log("DEBUG: Quellbild in lowresDir gefunden: " . $sourceImagePath);
            break;
        }
    }

    if (empty($sourceImagePath)) {
        $errors[] = "Quellbild für Comic-ID '$comicId' nicht gefunden in '$hiresDir' oder '$lowresDir'.";
        error_log("Quellbild für Comic-ID '$comicId' nicht gefunden.");
        if ($debugMode)
            error_log("DEBUG: Quellbild nicht gefunden.");
        return ['created' => $createdPath, 'errors' => $errors];
    }

    try {
        // Bildinformationen abrufen
        $imageInfo = @getimagesize($sourceImagePath); // @ unterdrückt Warnungen
        if ($imageInfo === false) {
            $errors[] = "Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId).";
            error_log("Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId).");
            if ($debugMode)
                error_log("DEBUG: Kann Bildinformationen nicht abrufen.");
            return ['created' => $createdPath, 'errors' => $errors];
        }
        list($width, $height, $type) = $imageInfo;
        if ($debugMode)
            error_log("DEBUG: Quellbild-Infos: Breite=" . $width . ", Höhe=" . $height . ", Typ=" . $type);

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
                error_log("Nicht unterstütztes Bildformat für Comic-ID '$comicId': " . $sourceImageExtension);
                if ($debugMode)
                    error_log("DEBUG: Nicht unterstütztes Bildformat.");
                return ['created' => $createdPath, 'errors' => $errors];
        }

        if (!$sourceImage) {
            $errors[] = "Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.";
            error_log("Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.");
            if ($debugMode)
                error_log("DEBUG: Fehler beim Laden des Bildes.");
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Berechne die Abmessungen, um das Bild proportional in die Zielgröße zu skalieren
        // Verwende min, um sicherzustellen, dass das gesamte Bild sichtbar ist (Letterboxing falls nötig)
        $ratio = min($targetWidth / $width, $targetHeight / $height);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;
        if ($debugMode)
            error_log("DEBUG: Neue Abmessungen: Breite=" . $newWidth . ", Höhe=" . $newHeight);


        // Erstelle ein neues True-Color-Bild für das Thumbnail
        $tempImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($tempImage === false) {
            $errors[] = "Fehler beim Erstellen des temporären Bildes für Comic-ID '$comicId'.";
            imagedestroy($sourceImage);
            if ($debugMode)
                error_log("DEBUG: Fehler beim Erstellen des temporären Bildes.");
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // === KORREKTUR: Transparenter Hintergrund ===
        // Deaktiviere das Blending, damit der Alphakanal direkt gesetzt werden kann.
        imagealphablending($tempImage, false);
        // Speichere den Alphakanal (Transparenz) für das PNG-Bild.
        imagesavealpha($tempImage, true);
        // Definiere die Hintergrundfarbe: Schwarz (0,0,0) mit 10% Deckkraft (ca. 90% Transparenz).
        // Der Alpha-Wert reicht von 0 (opak) bis 127 (vollständig transparent). 127 * 0.9 ≈ 114.
        $backgroundColor = imagecolorallocatealpha($tempImage, 0, 0, 0, 114);
        // Fülle den Hintergrund des Thumbnails mit der definierten Farbe.
        imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $backgroundColor);
        // Aktiviere das Blending wieder für die nachfolgenden Operationen (wie imagecopyresampled).
        imagealphablending($tempImage, true);
        // === ENDE KORREKTUR ===

        // Berechne Offsets, um das Bild auf dem neuen Canvas zu zentrieren
        $offsetX = ($targetWidth - $newWidth) / 2;
        $offsetY = ($targetHeight - $newHeight) / 2;
        if ($debugMode)
            error_log("DEBUG: Offsets: X=" . $offsetX . ", Y=" . $offsetY);


        // Bild auf die neue Größe und Position resamplen und kopieren
        if (
            !imagecopyresampled(
                $tempImage,
                $sourceImage,
                (int) $offsetX,
                (int) $offsetY,
                0,
                0,
                (int) $newWidth,
                (int) $newHeight,
                $width,
                $height
            )
        ) {
            $errors[] = "Fehler beim Resampling des Bildes für Comic-ID '$comicId'.";
            imagedestroy($sourceImage);
            imagedestroy($tempImage);
            if ($debugMode)
                error_log("DEBUG: Fehler beim Resampling des Bildes.");
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Bild als PNG speichern
        $thumbnailPath = $thumbnailDir . $comicId . '.png';
        // Speichere das Bild als PNG mit maximaler Kompression (Stufe 9).
        if (imagepng($tempImage, $thumbnailPath, 9)) {
            $createdPath = $thumbnailPath;
            if ($debugMode)
                error_log("DEBUG: Thumbnail erfolgreich als PNG gespeichert: " . $thumbnailPath);
        } else {
            $errors[] = "Fehler beim Speichern des PNG-Thumbnails für Comic-ID '$comicId' nach '$thumbnailPath'.";
            error_log("Fehler beim Speichern des PNG-Thumbnails für Comic-ID '$comicId' nach '$thumbnailPath'.");
            if ($debugMode)
                error_log("DEBUG: Fehler beim Speichern des Thumbnails.");
        }

        // Speicher freigeben
        imagedestroy($sourceImage);
        imagedestroy($tempImage);
        if ($debugMode)
            error_log("DEBUG: Bildspeicher freigegeben.");

    } catch (Throwable $e) { // Throwable fängt auch Errors (z.B. Memory Exhaustion) ab
        $errors[] = "Ausnahme/Fehler bei Comic-ID '$comicId': " . $e->getMessage() . " (Code: " . $e->getCode() . " in " . $e->getFile() . " Zeile " . $e->getLine() . ")";
        error_log("Kritischer Fehler bei Comic-ID '$comicId': " . $e->getMessage() . " in " . $e->getFile() . " Zeile " . $e->getLine());
        if ($debugMode)
            error_log("DEBUG: Kritischer Fehler: " . $e->getMessage());
    } finally {
        // Führe nach jeder Bildgenerierung eine explizite Garbage Collection durch
        gc_collect_cycles();
        // Füge eine kurze Pause ein, um dem System Zeit zur Ressourcenfreigabe zu geben
        usleep(50000); // 50 Millisekunden Pause
        if ($debugMode)
            error_log("DEBUG: Garbage Collection und Pause nach Generierung.");
    }
    return ['created' => $createdPath, 'errors' => $errors];
}

// --- AJAX-Anfrage-Handler ---
// Dieser Block wird nur ausgeführt, wenn eine POST-Anfrage mit der Aktion 'generate_single_thumbnail' gesendet wird.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_single_thumbnail') {
    if ($debugMode)
        error_log("DEBUG: AJAX-Anfrage 'generate_single_thumbnail' erkannt.");
    // Leere und beende den Output Buffer, um sicherzustellen, dass keine unerwünschten Ausgaben gesendet werden.
    ob_end_clean();
    // Temporär Fehleranzeige deaktivieren und Error Reporting unterdrücken, um JSON-Ausgabe nicht zu stören.
    ini_set('display_errors', 0);
    error_reporting(0);

    header('Content-Type: application/json'); // Wichtig für JSON-Antwort
    $response = ['success' => false, 'message' => ''];

    // Prüfe, ob GD geladen ist, bevor Bildoperationen versucht werden.
    if (!extension_loaded('gd')) {
        $response['message'] = "FEHLER: Die GD-Bibliothek ist nicht geladen. Thumbnails können nicht generiert werden.";
        error_log("AJAX-Anfrage: GD-Bibliothek nicht geladen.");
        if ($debugMode)
            error_log("DEBUG: AJAX-Fehler: GD-Bibliothek nicht geladen.");
        echo json_encode($response);
        exit;
    }

    $comicId = $_POST['comic_id'] ?? '';
    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID für die Generierung angegeben.';
        error_log("AJAX-Anfrage: Keine Comic-ID angegeben.");
        if ($debugMode)
            error_log("DEBUG: AJAX-Fehler: Keine Comic-ID angegeben.");
        echo json_encode($response);
        exit;
    }
    if ($debugMode)
        error_log("DEBUG: AJAX-Anfrage für Comic-ID: " . $comicId);

    // Pfade für die einzelne Generierung (müssen hier neu definiert werden, da es ein separater Request ist)
    $lowresDir = __DIR__ . '/../assets/comic_lowres/';
    $hiresDir = __DIR__ . '/../assets/comic_hires/';
    $thumbnailDir = __DIR__ . '/../assets/comic_thumbnails/';

    $result = generateThumbnail($comicId, $lowresDir, $hiresDir, $thumbnailDir, $debugMode);

    if (empty($result['errors'])) {
        $response['success'] = true;
        $response['message'] = 'Thumbnail für ' . $comicId . ' erfolgreich erstellt.';
        $response['imageUrl'] = '../assets/comic_thumbnails/' . $comicId . '.png?' . time(); // Cache-Buster
        $response['comicId'] = $comicId;
        if ($debugMode)
            error_log("DEBUG: AJAX-Erfolg für Comic-ID: " . $comicId);
    } else {
        $response['message'] = 'Fehler bei der Erstellung für ' . $comicId . ': ' . implode(', ', $result['errors']);
        error_log("AJAX-Anfrage: Fehler bei der Generierung für Comic-ID '$comicId': " . implode(', ', $result['errors']));
        if ($debugMode)
            error_log("DEBUG: AJAX-Fehler für Comic-ID: " . $comicId . ": " . implode(', ', $result['errors']));
    }
    // Überprüfe, ob json_encode einen Fehler hatte
    $jsonOutput = json_encode($response);
    if ($jsonOutput === false) {
        $jsonError = json_last_error_msg();
        error_log("AJAX-Anfrage: json_encode Fehler für Comic-ID '$comicId': " . $jsonError);
        if ($debugMode)
            error_log("DEBUG: AJAX-Fehler: JSON-Encoding fehlgeschlagen für Comic-ID: " . $comicId);
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
    error_log("DEBUG: Output Buffer geleert und gesendet.");


// Variablen für die Anzeige initialisieren
$allComicIds = getExistingComicIds($lowresDir, $hiresDir, $debugMode);
$existingThumbnailIds = getExistingThumbnailIds($thumbnailDir, $debugMode);
$missingThumbnails = findMissingThumbnails($allComicIds, $existingThumbnailIds, $debugMode);
if ($debugMode)
    error_log("DEBUG: Initialer Thumbnail-Status ermittelt.");


// Gemeinsamen Header einbinden.
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in generator_thumbnail.php eingebunden.");
} else {
    // Fallback oder Fehlerbehandlung, falls Header nicht gefunden wird.
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><h1>Fehler: Header nicht gefunden!</h1>";
    if ($debugMode)
        error_log("DEBUG: Fehler: Header-Datei nicht gefunden.");
}

// Basis-URL für die Bildanzeige bestimmen
$thumbnailWebPath = '../assets/comic_thumbnails/';
?>

<article>
    <header>
        <h1>Thumbnail-Generator</h1>
    </header>

    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
            <?php if ($debugMode)
                error_log("DEBUG: GD-Fehlermeldung angezeigt."); ?>
        <?php endif; ?>

        <h2>Status der Thumbnails PNG</h2>

        <!-- Container für die Buttons - JETZT HIER PLATZIERT -->
        <div id="fixed-buttons-container">
            <button type="button" id="generate-thumbnails-button" <?php echo $gdError || empty($missingThumbnails) ? 'disabled' : ''; ?>>Fehlende Thumbnails erstellen</button>
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
            <p id="progress-text">Generiere Thumbnails...</p>
        </div>

        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Es wurden keine Comic-Bilder in den Verzeichnissen
                `<?php echo htmlspecialchars($lowresDir); ?>` oder `<?php echo htmlspecialchars($hiresDir); ?>` gefunden,
                die als Basis dienen könnten.</p>
            <?php if ($debugMode)
                error_log("DEBUG: Keine Comic-Bilder gefunden-Nachricht angezeigt."); ?>
        <?php elseif (empty($missingThumbnails)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Thumbnails sind vorhanden.</p>
            <?php if ($debugMode)
                error_log("DEBUG: Alle Thumbnails vorhanden-Nachricht angezeigt."); ?>
        <?php else: ?>
            <p class="status-message status-red">Es fehlen <?php echo count($missingThumbnails); ?> Thumbnails.</p>
            <h3>Fehlende Thumbnails (IDs):</h3>
            <!-- Geänderter Bereich für die Anzeige der fehlenden Bilder -->
            <div id="missing-thumbnails-grid" class="missing-items-grid">
                <?php foreach ($missingThumbnails as $id): ?>
                    <span class="missing-item"
                        data-comic-id="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($id); ?></span>
                <?php endforeach; ?>
            </div>
            <?php if ($debugMode)
                error_log("DEBUG: Fehlende Thumbnails-Liste angezeigt."); ?>
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

    /* Image Grid Layout (für Thumbnails) */
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
        width: 187px;
        /* Feste Breite für Thumbnails */
        height: 260px;
        /* Feste Höhe, um Platz für Text zu lassen */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        /* Bild oben, Text unten */
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
    }

    .image-item span {
        word-break: break-all;
        font-size: 0.8em;
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
    document.addEventListener('DOMContentLoaded', function () {
        const generateButton = document.getElementById('generate-thumbnails-button');
        const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
        const loadingSpinner = document.getElementById('loading-spinner');
        const progressText = document.getElementById('progress-text');
        const missingThumbnailsGrid = document.getElementById('missing-thumbnails-grid'); // ID geändert
        const createdImagesContainer = document.getElementById('created-images-container');
        const generationResultsSection = document.getElementById('generation-results-section');
        const overallStatusMessage = document.getElementById('overall-status-message');
        const errorHeaderMessage = document.getElementById('error-header-message');
        const errorsList = document.getElementById('generation-errors-list');

        // Die Liste der fehlenden IDs, direkt von PHP übergeben
        const initialMissingIds = <?php echo json_encode($missingThumbnails); ?>;
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
                // Keine Thumbnails zum Generieren vorhanden
                generateButton.style.display = 'inline-block';
                generateButton.disabled = true;
                togglePauseResumeButton.style.display = 'none';
            } else if (isGenerationActive) {
                // Generierung ist aktiv oder pausiert
                generateButton.style.display = 'none';
                togglePauseResumeButton.style.display = 'inline-block';
                if (isPaused) {
                    togglePauseResumeButton.textContent = 'Fortsetzen';
                    togglePauseResumeButton.className = 'status-green-button';
                    progressText.textContent = `Generierung pausiert. ${createdCount + errorCount} von ${initialMissingIds.length} verarbeitet.`;
                } else {
                    togglePauseResumeButton.textContent = 'Pause';
                    togglePauseResumeButton.className = 'status-orange-button'; // Könnte eine neue Klasse sein
                }
            } else {
                // Generierung ist nicht aktiv (z.B. vor dem Start oder nach Abschluss)
                generateButton.style.display = 'inline-block';
                generateButton.disabled = false;
                togglePauseResumeButton.style.display = 'none';
            }
        }

        // Event Listener für den Generate-Button
        generateButton.addEventListener('click', function () {
            if (remainingIds.length === 0) {
                overallStatusMessage.textContent = 'Keine Thumbnails zum Generieren vorhanden.';
                overallStatusMessage.className = 'status-message status-orange';
                generationResultsSection.style.display = 'block';
                return;
            }

            // UI-Elemente zurücksetzen
            createdImagesContainer.innerHTML = '';
            errorsList.innerHTML = '';
            errorHeaderMessage.style.display = 'none';
            overallStatusMessage.style.display = 'none'; // Verstecke die allgemeine Statusmeldung beim Start
            generationResultsSection.style.display = 'block';

            createdCount = 0;
            errorCount = 0;
            isPaused = false;
            isGenerationActive = true; // Generierung beginnt
            updateButtonState(); // Buttons anpassen (Generieren aus, Toggle an)

            loadingSpinner.style.display = 'block';
            processNextImage();
        });

        // Event Listener für den Pause/Resume-Button
        togglePauseResumeButton.addEventListener('click', function () {
            isPaused = !isPaused;
            updateButtonState(); // Button-Text und Sichtbarkeit aktualisieren
            if (!isPaused) { // Wenn gerade fortgesetzt wurde
                processNextImage(); // Generierung fortsetzen
            }
        });

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
                    overallStatusMessage.textContent = `Alle ${createdCount} Thumbnails erfolgreich generiert!`;
                    overallStatusMessage.className = 'status-message status-green';
                }
                overallStatusMessage.style.display = 'block'; // Zeige die allgemeine Statusmeldung an
                return;
            }

            const currentId = remainingIds.shift(); // Nächste ID aus der Liste nehmen
            progressText.textContent = `Generiere Thumbnail ${createdCount + errorCount + 1} von ${initialMissingIds.length} (${currentId})...`;

            try {
                const response = await fetch(window.location.href, { // Anfrage an dasselbe PHP-Skript
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'generate_single_thumbnail', // Spezifische Aktion für AJAX
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
                        <img src="${data.imageUrl}" alt="Thumbnail ${data.comicId}">
                        <span>${data.comicId}</span>
                    `;
                    createdImagesContainer.appendChild(imageDiv);

                    // Entferne das Element aus dem Grid der fehlenden Bilder
                    if (missingThumbnailsGrid) {
                        const missingItemSpan = missingThumbnailsGrid.querySelector(`span[data-comic-id="${data.comicId}"]`);
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
        updateButtonState(); // Initialen Zustand der Buttons setzen
    });
</script>

<?php
// Gemeinsamen Footer einbinden.
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in generator_thumbnail.php eingebunden.");
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
    if ($debugMode)
        error_log("DEBUG: Fehler: Footer-Datei nicht gefunden.");
}
?>