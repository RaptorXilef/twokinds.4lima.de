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

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();

// Erhöhe das PHP-Speicherlimit, um Probleme mit großen Bildern zu vermeiden.
// 1G hat sich als optimaler Wert erwiesen, um Ruckeln zu vermeiden.
ini_set('memory_limit', '1G');

// Aktiviere die explizite Garbage Collection, um Speicher effizienter zu verwalten.
gc_enable();

// Starte die PHP-Sitzung. Notwendig für die Admin-Anmeldung.
session_start();

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
// Wenn nicht angemeldet, zur Login-Seite weiterleiten.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Beende den Output Buffer, da wir umleiten und keine weitere Ausgabe wollen.
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen und Verzeichnissen.
// Die 'assets'-Ordner liegen eine Ebene über 'admin'.
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$hiresDir = __DIR__ . '/../assets/comic_hires/';   // Quellverzeichnis für Hi-Res Comic-Bilder (ausschließlich für diesen Generator)
$socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/'; // Zielverzeichnis für Social Media Bilder

// Stelle sicher, dass die GD-Bibliothek geladen ist.
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Social Media Bilder können nicht generiert werden. Bitte PHP-Konfiguration prüfen.";
    error_log("GD-Bibliothek nicht geladen in socialmedia_image_generator_crop.php");
} else {
    $gdError = null;
}

/**
 * Scannt das Hi-Res Comic-Verzeichnis nach vorhandenen Comic-Bildern.
 * Für diesen Generator werden nur Bilder aus dem hires-Ordner berücksichtigt.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingComicIds(string $hiresDir): array {
    $comicIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    // Scan hires-Verzeichnis nach Bildern
    if (is_dir($hiresDir)) {
        $files = scandir($hiresDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $comicIds[$fileInfo['filename']] = true;
            }
        }
    }
    return array_keys($comicIds); // Eindeutige Dateinamen zurückgeben
}

/**
 * Scannt das Social Media Bild-Verzeichnis nach vorhandenen Bildern.
 * @param string $socialMediaImageDir Pfad zum Social Media Bild-Verzeichnis.
 * @return array Eine Liste vorhandener Social Media Bild-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingSocialMediaImageIds(string $socialMediaImageDir): array {
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Prüfe auf gängige Bild-Erweiterungen

    if (is_dir($socialMediaImageDir)) {
        $files = scandir($socialMediaImageDir);
        foreach ($files as $file) {
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $imageExtensions)) {
                $imageIds[] = $fileInfo['filename'];
            }
        }
    }
    return $imageIds;
}

/**
 * Vergleicht alle vorhandenen Comic-IDs mit bereits vorhandenen Social Media Bild-IDs.
 * @param array $allComicIds Alle gefundenen Comic-IDs.
 * @param array $existingSocial MediaImageIds Alle gefundenen Social Media Bild-IDs.
 * @return array Eine Liste von Comic-IDs, für die Social Media Bilder fehlen.
 */
function findMissingSocialMediaImages(array $allComicIds, array $existingSocialMediaImageIds): array {
    return array_values(array_diff($allComicIds, $existingSocialMediaImageIds));
}

/**
 * Generiert ein einzelnes Social Media Bild aus einem Quellbild durch Zuschneiden des oberen Teils.
 * Zielgröße: 1200x630 Pixel (typisches Open Graph Bildformat).
 * @param string $comicId Die ID des Comics, für das ein Social Media Bild erstellt werden soll.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $socialMediaImageDir Pfad zum Social Media Bild-Verzeichnis.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellter Pfad) und 'errors' (Fehlermeldungen).
 */
function generateSocialMediaImage(string $comicId, string $hiresDir, string $socialMediaImageDir): array {
    $errors = [];
    $createdPath = '';

    // Erstelle den Zielordner, falls er nicht existiert.
    if (!is_dir($socialMediaImageDir)) {
        if (!mkdir($socialMediaImageDir, 0755, true)) {
            $errors[] = "Fehler: Zielverzeichnis '$socialMediaImageDir' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.";
            error_log("Fehler: Zielverzeichnis '$socialMediaImageDir' konnte nicht erstellt werden für Comic-ID $comicId.");
            return ['created' => $createdPath, 'errors' => $errors];
        }
    } elseif (!is_writable($socialMediaImageDir)) {
        $errors[] = "Fehler: Zielverzeichnis '$socialMediaImageDir' ist nicht beschreibbar. Bitte Berechtigungen prüfen.";
        error_log("Fehler: Zielverzeichnis '$socialMediaImageDir' ist nicht beschreibbar für Comic-ID $comicId.");
        return ['created' => $createdPath, 'errors' => $errors];
    }

    // Definiere die Zielabmessungen für Social Media Bilder
    $targetWidth = 1200;
    $targetHeight = 630;
    $targetRatio = $targetWidth / $targetHeight;

    $sourceImagePath = '';
    $sourceImageExtension = '';

    // Suche das Quellbild ausschließlich im hires-Ordner
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($possibleExtensions as $ext) {
        $hiresPath = $hiresDir . $comicId . '.' . $ext;
        if (file_exists($hiresPath)) {
            $sourceImagePath = $hiresPath;
            $sourceImageExtension = $ext;
            break;
        }
    }

    if (empty($sourceImagePath)) {
        $errors[] = "Quellbild für Comic-ID '$comicId' nicht im '$hiresDir'-Verzeichnis gefunden.";
        error_log("Quellbild für Comic-ID '$comicId' nicht im hires-Verzeichnis gefunden.");
        return ['created' => $createdPath, 'errors' => $errors];
    }

    try {
        // Bildinformationen abrufen
        $imageInfo = @getimagesize($sourceImagePath); // @ unterdrückt Warnungen
        if ($imageInfo === false) {
            $errors[] = "Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId). Möglicherweise ist die Datei beschädigt oder kein gültiges Bild.";
            error_log("Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId).");
            return ['created' => $createdPath, 'errors' => $errors];
        }
        list($width, $height, $type) = $imageInfo;
        $sourceRatio = $width / $height;

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
                return ['created' => $createdPath, 'errors' => $errors];
        }

        if (!$sourceImage) {
            $errors[] = "Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'. Möglicherweise ist der Speicher erschöpft oder das Bild ist korrupt.";
            error_log("Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.");
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // --- Zuschneide-Logik für den oberen Teil des Bildes ---
        $src_x = 0;
        $src_y = 0; // Immer von oben beginnen

        // Berechne die Quellbreite und -höhe, die dem Ziel-Seitenverhältnis entsprechen
        // und aus dem Quellbild entnommen werden können.
        if ($sourceRatio > $targetRatio) {
            // Quellbild ist breiter als das Zielverhältnis (relativ flacher)
            // Wir nutzen die volle Quellhöhe und berechnen die entsprechende Breite.
            $src_h = $height;
            $src_w = $height * $targetRatio;
            // Da wir den oberen Teil nehmen, bleibt src_x = 0 (von links zuschneiden)
        } else {
            // Quellbild ist schmaler oder gleich dem Zielverhältnis (relativ höher oder quadratisch)
            // Wir nutzen die volle Quellbreite und berechnen die entsprechende Höhe.
            $src_w = $width;
            $src_h = $width / $targetRatio;
            // Da wir den oberen Teil nehmen, bleibt src_y = 0 (von oben zuschneiden)
        }

        // Erstelle ein neues True-Color-Bild für das Social Media Bild
        $tempImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($tempImage === false) {
            $errors[] = "Fehler beim Erstellen des temporären Bildes für Comic-ID '$comicId'.";
            error_log("Fehler beim Erstellen des temporären Bildes für Comic-ID '$comicId'.");
            imagedestroy($sourceImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Für PNGs: Transparenz erhalten
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
        }

        // Fülle den Hintergrund mit Weiß (oder einer anderen passenden Farbe)
        $backgroundColor = imagecolorallocate($tempImage, 255, 255, 255); // Weißer Hintergrund
        imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $backgroundColor);

        // Bild von der Quelle auf das temporäre Bild resamplen und kopieren
        // Hier wird der obere Teil des Quellbildes (src_x, src_y, src_w, src_h)
        // auf die Zielgröße (targetWidth, targetHeight) skaliert.
        if (!imagecopyresampled($tempImage, $sourceImage, 0, 0, (int)$src_x, (int)$src_y,
                               $targetWidth, $targetHeight, (int)$src_w, (int)$src_h)) {
            $errors[] = "Fehler beim Resampling des Bildes für Comic-ID '$comicId'.";
            error_log("Fehler beim Resampling des Bildes für Comic-ID '$comicId'.");
            imagedestroy($sourceImage);
            imagedestroy($tempImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Bild als JPG speichern (für Social Media empfohlen)
        $socialMediaImagePath = $socialMediaImageDir . $comicId . '.jpg';
        if (imagejpeg($tempImage, $socialMediaImagePath, 90)) { // 90% Qualität
            $createdPath = $socialMediaImagePath;
        } else {
            $errors[] = "Fehler beim Speichern des Social Media Bildes für Comic-ID '$comicId' nach '$socialMediaImagePath'. Möglicherweise sind die Dateiberechtigungen falsch oder das Verzeichnis existiert nicht.";
            error_log("Fehler beim Speichern des Social Media Bildes für Comic-ID '$comicId' nach '$socialMediaImagePath'.");
        }

        // Speicher freigeben
        imagedestroy($sourceImage);
        imagedestroy($tempImage);

    } catch (Throwable $e) { // Throwable fängt auch Errors (z.B. Memory Exhaustion) ab
        $errors[] = "Ausnahme/Fehler bei Comic-ID '$comicId': " . $e->getMessage() . " (Code: " . $e->getCode() . " in " . $e->getFile() . " Zeile " . $e->getLine() . ")";
        error_log("Kritischer Fehler bei Comic-ID '$comicId': " . $e->getMessage() . " in " . $e->getFile() . " Zeile " . $e->getLine());
    } finally {
        // Führe nach jeder Bildgenerierung eine explizite Garbage Collection durch
        gc_collect_cycles();
        // Füge eine kurze Pause ein, um dem System Zeit zur Ressourcenfreigabe zu geben
        usleep(50000); // 50 Millisekunden Pause
    }
    return ['created' => $createdPath, 'errors' => $errors];
}

// --- AJAX-Anfrage-Handler ---
// Dieser Block wird nur ausgeführt, wenn eine POST-Anfrage mit der Aktion 'generate_single_social_media_image' gesendet wird.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_single_social_media_image') {
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
        echo json_encode($response);
        exit;
    }

    $comicId = $_POST['comic_id'] ?? '';
    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID für die Generierung angegeben.';
        error_log("AJAX-Anfrage: Keine Comic-ID angegeben.");
        echo json_encode($response);
        exit;
    }

    // Pfade für die einzelne Generierung (müssen hier neu definiert werden, da es ein separater Request ist)
    $hiresDir = __DIR__ . '/../assets/comic_hires/';
    $socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/';

    $result = generateSocialMediaImage($comicId, $hiresDir, $socialMediaImageDir);

    if (empty($result['errors'])) {
        $response['success'] = true;
        $response['message'] = 'Social Media Bild für ' . $comicId . ' erfolgreich erstellt.';
        $response['imageUrl'] = '../assets/comic_socialmedia/' . $comicId . '.jpg?' . time();
        $response['comicId'] = $comicId;
    } else {
        // Gib die spezifischen Fehlermeldungen aus der Generierungsfunktion zurück
        $response['message'] = 'Fehler bei der Erstellung für ' . $comicId . ': ' . implode(', ', $result['errors']);
        error_log("AJAX-Anfrage: Fehler bei der Generierung für Comic-ID '$comicId': " . implode(', ', $result['errors']));
    }

    // Überprüfe, ob json_encode einen Fehler hatte
    $jsonOutput = json_encode($response);
    if ($jsonOutput === false) {
        $jsonError = json_last_error_msg();
        error_log("AJAX-Anfrage: json_encode Fehler für Comic-ID '$comicId': " . $jsonError);
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

// Variablen für die Anzeige initialisieren
// Beachte: getExistingComicIds wird hier nur mit $hiresDir aufgerufen
$allComicIds = getExistingComicIds($hiresDir);
$existingSocialMediaImageIds = getExistingSocialMediaImageIds($socialMediaImageDir);
$missingSocialMediaImages = findMissingSocialMediaImages($allComicIds, $existingSocialMediaImageIds);

// Gemeinsamen Header einbinden.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    // Fallback oder Fehlerbehandlung, falls Header nicht gefunden wird.
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><h1>Fehler: Header nicht gefunden!</h1>";
}

// Basis-URL für die Bildanzeige bestimmen
$socialMediaImageWebPath = '../assets/comic_socialmedia/';
?>

<article>
    <header>
        <h1>Social Media Bild-Generator (Crop von oben)</h1>
    </header>

    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
        <?php endif; ?>

        <h2>Status der Social Media Bilder</h2>
        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Es wurden keine Comic-Bilder im Verzeichnis `<?php echo htmlspecialchars($hiresDir); ?>` gefunden, die als Basis dienen könnten.</p>
        <?php elseif (empty($missingSocialMediaImages)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Social Media Bilder sind vorhanden.</p>
        <?php else: ?>
            <p class="status-message status-red">Es fehlen <?php echo count($missingSocialMediaImages); ?> Social Media Bilder.</p>
            <h3>Fehlende Social Media Bilder (IDs):</h3>
            <ul id="missing-images-list">
                <?php foreach ($missingSocialMediaImages as $id): ?>
                    <li><?php echo htmlspecialchars($id); ?></li>
                <?php endforeach; ?>
            </ul>
            <!-- Container für die fest positionierten Buttons -->
            <div id="fixed-buttons-container">
                <button type="button" id="generate-images-button" <?php echo $gdError ? 'disabled' : ''; ?>>Fehlende Social Media Bilder erstellen</button>
                <button type="button" id="toggle-pause-resume-button" style="display:none;"></button>
            </div>
        <?php endif; ?>

        <!-- Lade-Indikator und Fortschrittsanzeige -->
        <div id="loading-spinner" style="display: none; text-align: center; margin-top: 20px;">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Social Media Bilder...</p>
        </div>

        <!-- Ergebnisse der Generierung -->
        <div id="generation-results-section" style="margin-top: 20px; display: none;">
            <h2 style="margin-top: 20px;">Ergebnisse der Generierung</h2>
            <p id="overall-status-message" class="status-message"></p>
            <div id="created-images-container" class="image-grid">
                <!-- Hier werden die erfolgreich generierten Bilder angezeigt -->
            </div>
            <p class="status-message status-red" style="display: none;" id="error-header-message">Fehler bei der Generierung:</p>
            <ul id="generation-errors-list">
                <!-- Hier werden Fehler angezeigt -->
            </ul>
        </div>
    </div>
</article>

<style>
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
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
        width: calc(50% - 5px); /* Für 2 Bilder pro Reihe, da sie breiter sind */
        min-width: 300px; /* Mindestbreite für Responsivität */
        height: auto; /* Flexibel in der Höhe */
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
        height: auto; /* Wichtig für proportionale Skalierung */
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
            width: 100%; /* 1 pro Reihe auf kleineren Bildschirmen */
        }
    }

    /* Stil für den fest positionierten Button-Container */
    #fixed-buttons-container {
        position: fixed; /* Fixiert die Position relativ zum Viewport */
        z-index: 1000; /* Stellt sicher, dass die Buttons über anderen Inhalten liegen */
        display: flex; /* Für nebeneinanderliegende Buttons */
        gap: 10px; /* Abstand zwischen den Buttons */
        /* top und right werden dynamisch per JavaScript gesetzt */
    }

    /* Anpassung für kleinere Bildschirme, falls die Buttons zu viel Platz einnehmen */
    @media (max-width: 768px) {
        #fixed-buttons-container {
            flex-direction: column; /* Buttons untereinander auf kleinen Bildschirmen */
            gap: 5px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateButton = document.getElementById('generate-images-button');
    const togglePauseResumeButton = document.getElementById('toggle-pause-resume-button');
    const loadingSpinner = document.getElementById('loading-spinner');
    const progressText = document.getElementById('progress-text');
    const missingImagesList = document.getElementById('missing-images-list');
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

    // Elemente für die Positionierung der Buttons
    // Annahme: Es gibt ein <main id="content" class="content"> Element, das das <article> umschließt.
    // Falls nicht vorhanden, wird ein Fallback auf die Viewport-Position verwendet.
    const mainContent = document.getElementById('content');
    const fixedButtonsContainer = document.getElementById('fixed-buttons-container');

    /**
     * Setzt die feste Position des Button-Containers basierend auf dem Main-Content-Element.
     * Wird beim Laden der Seite und bei Größenänderung des Fensters aufgerufen.
     */
    function setFixedButtonPosition() {
        if (mainContent && fixedButtonsContainer) {
            const mainRect = mainContent.getBoundingClientRect();
            // Berechne die absolute Top-Position im Viewport:
            // Top des Main-Elements + gewünschter Abstand (18px)
            const calculatedTop = mainRect.top + 18;
            // Berechne die absolute Right-Position im Viewport:
            // Breite des Viewports - (Rechte Kante des Main-Elements + gewünschter Abstand (24px))
            const calculatedRight = window.innerWidth - mainRect.right + 24;

            fixedButtonsContainer.style.top = `${calculatedTop}px`;
            fixedButtonsContainer.style.right = `${calculatedRight}px`;
        } else if (fixedButtonsContainer) {
            console.warn("Main content element with ID 'content' not found. Fixed buttons might not be positioned correctly. Falling back to viewport top-right.");
            // Fallback: Wenn das Main-Element nicht gefunden wird, positioniere relativ zum Viewport
            fixedButtonsContainer.style.top = `18px`;
            fixedButtonsContainer.style.right = `24px`;
        }
    }

    // Funktion zum Aktualisieren des Button-Zustands (Text und Sichtbarkeit)
    function updateButtonState() {
        if (remainingIds.length === 0 && createdCount + errorCount === initialMissingIds.length) { // Generierung abgeschlossen
            generateButton.style.display = 'inline-block';
            generateButton.disabled = false;
            togglePauseResumeButton.style.display = 'none';
        } else if (remainingIds.length === initialMissingIds.length && createdCount === 0 && errorCount === 0) { // Initialer Zustand, nichts gestartet
            generateButton.style.display = 'inline-block';
            generateButton.disabled = false;
            togglePauseResumeButton.style.display = 'none';
        }
        else { // Generierung ist aktiv oder pausiert
            generateButton.style.display = 'none'; // Generieren-Button ausblenden, sobald gestartet
            togglePauseResumeButton.style.display = 'inline-block';
            if (isPaused) {
                togglePauseResumeButton.textContent = 'Generierung fortsetzen';
            } else {
                togglePauseResumeButton.textContent = 'Generierung pausieren';
            }
        }
    }

    // Initialen Zustand der Buttons beim Laden der Seite setzen
    updateButtonState(); // Rufe die Funktion auf, um den korrekten Startzustand zu setzen
    setFixedButtonPosition(); // Setze die initiale Position der Buttons

    // Recalculate only on resize, not on scroll, to keep them truly "fixed" on screen
    window.addEventListener('resize', setFixedButtonPosition);

    if (generateButton) {
        generateButton.addEventListener('click', function() {
            if (initialMissingIds.length === 0) { // Prüfe initialMissingIds, da remainingIds geleert wird
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
            isPaused = false; // Sicherstellen, dass der Status nicht pausiert ist

            updateButtonState(); // Buttons anpassen (Generieren aus, Pause an)
            processNextImage();
        });
    }

    if (togglePauseResumeButton) {
        togglePauseResumeButton.addEventListener('click', function() {
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

                if (missingImagesList) {
                    const listItem = missingImagesList.querySelector(`li`);
                    if (listItem && listItem.textContent.includes(data.comicId)) {
                        listItem.remove();
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
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
}
?>
