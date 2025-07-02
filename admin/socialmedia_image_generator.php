<?php
/**
 * Dies ist die Administrationsseite für den Social Media Bild-Generator.
 * Sie überprüft, welche Social Media Bilder fehlen und bietet die Möglichkeit, diese zu erstellen.
 * Die Generierung erfolgt nun schrittweise über AJAX, um Speicherprobleme bei vielen Bildern zu vermeiden.
 */

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();

// Erhöhe das PHP-Speicherlimit, um Probleme mit großen Bildern zu vermeiden.
// Dies ist oft die Ursache für "Unexpected end of JSON input" bei Bildoperationen.
ini_set('memory_limit', '1G'); // Kann bei Bedarf weiter erhöht werden (z.B. '1G' für 1 Gigabyte)

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
$lowresDir = __DIR__ . '/../assets/comic_lowres/'; // Quellverzeichnis für Comic-Bilder
$hiresDir = __DIR__ . '/../assets/comic_hires/';   // Optionales Quellverzeichnis für Hi-Res Comic-Bilder
$socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/'; // Zielverzeichnis für Social Media Bilder

// Stelle sicher, dass die GD-Bibliothek geladen ist.
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Social Media Bilder können nicht generiert werden. Bitte PHP-Konfiguration prüfen.";
    error_log("GD-Bibliothek nicht geladen in socialmedia_image_generator.php");
} else {
    $gdError = null;
}

/**
 * Scannt die Comic-Verzeichnisse nach vorhandenen Comic-Bildern.
 * Priorisiert Bilder im lowres-Ordner.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @return array Eine Liste eindeutiger Comic-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingComicIds(string $lowresDir, string $hiresDir): array {
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
 * @param array $existingSocialMediaImageIds Alle gefundenen Social Media Bild-IDs.
 * @return array Eine Liste von Comic-IDs, für die Social Media Bilder fehlen.
 */
function findMissingSocialMediaImages(array $allComicIds, array $existingSocialMediaImageIds): array {
    return array_values(array_diff($allComicIds, $existingSocialMediaImageIds));
}

/**
 * Generiert ein einzelnes Social Media Bild aus einem Quellbild.
 * Zielgröße: 1200x630 Pixel (typisches Open Graph Bildformat).
 * Das Bild wird zentriert und proportional skaliert, ohne es hochzuskalieren.
 * @param string $comicId Die ID des Comics, für das ein Social Media Bild erstellt werden soll.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $socialMediaImageDir Pfad zum Social Media Bild-Verzeichnis.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellter Pfad) und 'errors' (Fehlermeldungen).
 */
function generateSocialMediaImage(string $comicId, string $lowresDir, string $hiresDir, string $socialMediaImageDir): array {
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
            break;
        } elseif (file_exists($lowresPath)) {
            $sourceImagePath = $lowresPath;
            $sourceImageExtension = $ext;
            break;
        }
    }

    if (empty($sourceImagePath)) {
        $errors[] = "Quellbild für Comic-ID '$comicId' nicht gefunden in '$hiresDir' oder '$lowresDir'.";
        error_log("Quellbild für Comic-ID '$comicId' nicht gefunden.");
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

        // Berechne die Abmessungen, um das Bild proportional in die Zielgröße zu skalieren,
        // ohne es hochzuskalieren. Wenn das Bild kleiner ist, wird es zentriert.
        $scale = min($targetWidth / $width, $targetHeight / $height);
        $newWidth = $width * $scale;
        $newHeight = $height * $scale;

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

        // Berechne Offsets, um das Bild auf dem neuen Canvas zu zentrieren
        $offsetX = ($targetWidth - $newWidth) / 2;
        $offsetY = ($targetHeight - $newHeight) / 2;

        // Bild auf die neue Größe und Position resamplen und kopieren
        if (!imagecopyresampled($tempImage, $sourceImage, (int)$offsetX, (int)$offsetY, 0, 0,
                               (int)$newWidth, (int)$newHeight, $width, $height)) {
            $errors[] = "Fehler beim Resampling des Bildes für Comic-ID '$comicId'.";
            error_log("Fehler beim Resampling des Bildes für Comic-ID '$comicId'.");
            imagedestroy($sourceImage);
            imagedestroy($tempImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Bild als JPG speichern (für Social Media empfohlen)
        // PNGs können sehr groß sein, JPG ist effizienter für Social Media
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
    $lowresDir = __DIR__ . '/../assets/comic_lowres/';
    $hiresDir = __DIR__ . '/../assets/comic_hires/';
    $socialMediaImageDir = __DIR__ . '/../assets/comic_socialmedia/';

    $result = generateSocialMediaImage($comicId, $lowresDir, $hiresDir, $socialMediaImageDir);

    if (empty($result['errors'])) {
        $response['success'] = true;
        $response['message'] = 'Social Media Bild für ' . $comicId . ' erfolgreich erstellt.';
        $response['imageUrl'] = '../assets/comic_socialmedia/' . $comicId . '.jpg?' . time(); // Korrigierter Pfad
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
        // Fallback, falls json_encode fehlschlägt (sehr unwahrscheinlich, wenn $response ein einfaches Array ist)
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
$allComicIds = getExistingComicIds($lowresDir, $hiresDir);
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
        <h1>Social Media Bild-Generator</h1>
    </header>

    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
        <?php endif; ?>

        <h2>Status der Social Media Bilder</h2>
        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Es wurden keine Comic-Bilder in den Verzeichnissen `<?php echo htmlspecialchars($lowresDir); ?>` oder `<?php echo htmlspecialchars($hiresDir); ?>` gefunden, die als Basis dienen könnten.</p>
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
            <button type="button" id="generate-images-button" <?php echo $gdError ? 'disabled' : ''; ?>>Fehlende Social Media Bilder erstellen</button>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateButton = document.getElementById('generate-images-button');
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

    if (generateButton) {
        generateButton.addEventListener('click', function() {
            if (remainingIds.length === 0) {
                console.log('No social media images to generate.');
                return;
            }

            // UI zurücksetzen und Ladezustand anzeigen
            generateButton.disabled = true;
            loadingSpinner.style.display = 'block';
            generationResultsSection.style.display = 'block';
            overallStatusMessage.textContent = '';
            overallStatusMessage.className = 'status-message'; // Reset class
            createdImagesContainer.innerHTML = '';
            errorsList.innerHTML = '';
            errorHeaderMessage.style.display = 'none'; // Hide error header initially

            createdCount = 0;
            errorCount = 0;

            processNextImage();
        });
    }

    async function processNextImage() {
        if (remainingIds.length === 0) {
            // Alle Bilder verarbeitet
            loadingSpinner.style.display = 'none';
            generateButton.disabled = false;
            progressText.textContent = `Generierung abgeschlossen. ${createdCount} erfolgreich, ${errorCount} Fehler.`;

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

            // Versuche, die Antwort als Text zu lesen, falls JSON-Parsing fehlschlägt
            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                const responseText = await response.text();
                throw new Error(`Failed to parse JSON for ${currentId}: ${jsonError.message}. Response was: ${responseText.substring(0, 200)}...`);
            }


            if (data.success) {
                createdCount++;
                // Füge das neue Bild zur Anzeige hinzu
                const imageDiv = document.createElement('div');
                imageDiv.className = 'image-item';
                imageDiv.innerHTML = `
                    <img src="${data.imageUrl}" alt="Social Media Bild ${data.comicId}">
                    <span>${data.comicId}</span>
                `;
                createdImagesContainer.appendChild(imageDiv);

                // Entferne die ID aus der Liste der fehlenden Bilder (visuell)
                if (missingImagesList) {
                    const listItem = missingImagesList.querySelector(`li`); // find first li
                    if (listItem && listItem.textContent.includes(data.comicId)) { // check if it contains the comicId
                        listItem.remove();
                    }
                }

            } else {
                errorCount++;
                const errorItem = document.createElement('li');
                errorItem.textContent = `Fehler für ${currentId}: ${data.message}`;
                errorsList.appendChild(errorItem);
                errorHeaderMessage.style.display = 'block'; // Zeige den Fehler-Header an
            }
        } catch (error) {
            errorCount++;
            const errorItem = document.createElement('li');
            errorItem.textContent = `Netzwerkfehler oder unerwartete Antwort für ${currentId}: ${error.message}`;
            errorsList.appendChild(errorItem);
            errorHeaderMessage.style.display = 'block'; // Zeige den Fehler-Header an
        }

        // Fahre mit dem nächsten Bild fort (rekursiver Aufruf)
        processNextImage();
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
