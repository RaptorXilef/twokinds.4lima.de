<?php
/**
 * Dies ist die Administrationsseite für den Thumbnail-Generator.
 * Sie überprüft, welche Comic-Thumbnails fehlen und bietet die Möglichkeit, diese zu erstellen.
 * Die Generierung erfolgt nun schrittweise über AJAX, um Speicherprobleme bei vielen Bildern zu vermeiden.
 */

// Starte die PHP-Sitzung. Notwendig für die Admin-Anmeldung.
session_start();

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
// Wenn nicht angemeldet, zur Login-Seite weiterleiten.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen und Verzeichnissen.
// Die 'assets'-Ordner liegen eine Ebene über 'admin'.
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$lowresDir = __DIR__ . '/../assets/comic_lowres/';
$hiresDir = __DIR__ . '/../assets/comic_hires/';
$thumbnailDir = __DIR__ . '/../assets/comic_thumbnails/'; // Verzeichnis für Thumbnails

// Stelle sicher, dass die GD-Bibliothek geladen ist.
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Thumbnails können nicht generiert werden. Bitte PHP-Konfiguration prüfen.";
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
 * Scannt das Thumbnail-Verzeichnis nach vorhandenen Bildern.
 * @param string $thumbnailDir Pfad zum Thumbnail-Verzeichnis.
 * @return array Eine Liste vorhandener Thumbnail-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingThumbnailIds(string $thumbnailDir): array {
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Prüfe auf gängige Bild-Erweiterungen

    if (is_dir($thumbnailDir)) {
        $files = scandir($thumbnailDir);
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
 * Vergleicht alle vorhandenen Comic-IDs mit bereits vorhandenen Thumbnail-IDs.
 * @param array $allComicIds Alle gefundenen Comic-IDs.
 * @param array $existingThumbnailIds Alle gefundenen Thumbnail-IDs.
 * @return array Eine Liste von Comic-IDs, für die Thumbnails fehlen.
 */
function findMissingThumbnails(array $allComicIds, array $existingThumbnailIds): array {
    return array_values(array_diff($allComicIds, $existingThumbnailIds));
}

/**
 * Generiert ein einzelnes Thumbnail aus einem Quellbild.
 * Zielgröße: 200x260 Pixel (Annahme, basierend auf typischen Comic-Thumbnail-Größen).
 * @param string $comicId Die ID des Comics, für das ein Thumbnail erstellt werden soll.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $thumbnailDir Pfad zum Thumbnail-Verzeichnis.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellter Pfad) und 'errors' (Fehlermeldungen).
 */
function generateThumbnail(string $comicId, string $lowresDir, string $hiresDir, string $thumbnailDir): array {
    $errors = [];
    $createdPath = '';

    // Erstelle den Thumbnail-Ordner, falls er nicht existiert.
    if (!is_dir($thumbnailDir)) {
        if (!mkdir($thumbnailDir, 0755, true)) {
            $errors[] = "Fehler: Thumbnail-Verzeichnis '$thumbnailDir' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.";
            return ['created' => $createdPath, 'errors' => $errors];
        }
    } elseif (!is_writable($thumbnailDir)) {
        $errors[] = "Fehler: Thumbnail-Verzeichnis '$thumbnailDir' ist nicht beschreibbar. Bitte Berechtigungen prüfen.";
        return ['created' => $createdPath, 'errors' => $errors];
    }

    // Definiere die Zielabmessungen für Thumbnails
    $targetWidth = 200;
    $targetHeight = 260;

    $sourceImagePath = '';
    $sourceImageExtension = '';

    // Priorisiere lowres-Bild, ansonsten suche nach hires-Bild
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($possibleExtensions as $ext) {
        $lowresPath = $lowresDir . $comicId . '.' . $ext;
        $hiresPath = $hiresDir . $comicId . '.' . $ext;

        if (file_exists($lowresPath)) {
            $sourceImagePath = $lowresPath;
            $sourceImageExtension = $ext;
            break;
        } elseif (file_exists($hiresPath)) {
            $sourceImagePath = $hiresPath;
            $sourceImageExtension = $ext;
            break;
        }
    }

    if (empty($sourceImagePath)) {
        $errors[] = "Quellbild für Comic-ID '$comicId' nicht gefunden in '$lowresDir' oder '$hiresDir'.";
        return ['created' => $createdPath, 'errors' => $errors];
    }

    try {
        // Bildinformationen abrufen
        $imageInfo = @getimagesize($sourceImagePath); // @ unterdrückt Warnungen
        if ($imageInfo === false) {
            $errors[] = "Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId).";
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
                return ['created' => $createdPath, 'errors' => $errors];
        }

        if (!$sourceImage) {
            $errors[] = "Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.";
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Berechne die Abmessungen, um das Bild proportional in die Zielgröße zu skalieren,
        // ohne es hochzuskalieren. Wenn das Bild kleiner ist, wird es zentriert.
        $scale = min($targetWidth / $width, $targetHeight / $height);
        $newWidth = $width * $scale;
        $newHeight = $height * $scale;

        // Erstelle ein neues True-Color-Bild für das Thumbnail
        $tempImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($tempImage === false) {
            $errors[] = "Fehler beim Erstellen des temporären Bildes für Comic-ID '$comicId'.";
            imagedestroy($sourceImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Fülle den Hintergrund mit Weiß für JPGs, bewahre Transparenz für PNG/GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($tempImage, false);
            imagesavealpha($tempImage, true);
            $transparent = imagecolorallocatealpha($tempImage, 255, 255, 255, 127);
            imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        } else {
            $white = imagecolorallocate($tempImage, 255, 255, 255);
            imagefilledrectangle($tempImage, 0, 0, $targetWidth, $targetHeight, $white);
        }

        // Berechne Offsets, um das Bild auf dem neuen Canvas zu zentrieren
        $offsetX = ($targetWidth - $newWidth) / 2;
        $offsetY = ($targetHeight - $newHeight) / 2;

        // Bild auf die neue Größe und Position resamplen und kopieren
        if (!imagecopyresampled($tempImage, $sourceImage, (int)$offsetX, (int)$offsetY, 0, 0,
                               (int)$newWidth, (int)$newHeight, $width, $height)) {
            $errors[] = "Fehler beim Resampling des Bildes für Comic-ID '$comicId'.";
            imagedestroy($sourceImage);
            imagedestroy($tempImage);
            return ['created' => $createdPath, 'errors' => $errors];
        }

        // Bild als JPG speichern (für Thumbnails empfohlen)
        $thumbnailImagePath = $thumbnailDir . $comicId . '.jpg';
        if (imagejpeg($tempImage, $thumbnailImagePath, 90)) { // 90% Qualität
            $createdPath = $thumbnailImagePath;
        } else {
            $errors[] = "Fehler beim Speichern des Thumbnails für Comic-ID '$comicId' nach '$thumbnailImagePath'.";
        }

        // Speicher freigeben
        imagedestroy($sourceImage);
        imagedestroy($tempImage);

    } catch (Exception $e) {
        $errors[] = "Ausnahme bei Comic-ID '$comicId': " . $e->getMessage();
    }
    return ['created' => $createdPath, 'errors' => $errors];
}

// --- AJAX-Anfrage-Handler ---
// Dieser Block wird nur ausgeführt, wenn eine POST-Anfrage mit der Aktion 'generate_single_thumbnail' gesendet wird.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_single_thumbnail') {
    header('Content-Type: application/json'); // Wichtig für JSON-Antwort
    $response = ['success' => false, 'message' => ''];

    // Prüfe, ob GD geladen ist, bevor Bildoperationen versucht werden.
    if (!extension_loaded('gd')) {
        $response['message'] = "FEHLER: Die GD-Bibliothek ist nicht geladen. Thumbnails können nicht generiert werden.";
        echo json_encode($response);
        exit;
    }

    $comicId = $_POST['comic_id'] ?? '';
    if (empty($comicId)) {
        $response['message'] = 'Keine Comic-ID für die Generierung angegeben.';
        echo json_encode($response);
        exit;
    }

    // Pfade für die einzelne Generierung
    $lowresDir = __DIR__ . '/../assets/comic_lowres/';
    $hiresDir = __DIR__ . '/../assets/comic_hires/';
    $thumbnailDir = __DIR__ . '/../assets/comic_thumbnails/';

    $result = generateThumbnail($comicId, $lowresDir, $hiresDir, $thumbnailDir);

    if (empty($result['errors'])) {
        $response['success'] = true;
        $response['message'] = 'Thumbnail für ' . $comicId . ' erfolgreich erstellt.';
        $response['imageUrl'] = '../assets/comic_thumbnails/' . $comicId . '.jpg?' . time(); // Cache-Buster
        $response['comicId'] = $comicId;
    } else {
        $response['message'] = 'Fehler bei der Erstellung für ' . $comicId . ': ' . implode(', ', $result['errors']);
    }
    echo json_encode($response);
    exit; // WICHTIG: Beende die Skriptausführung für AJAX-Anfragen hier!
}
// --- Ende AJAX-Anfrage-Handler ---


// --- Normaler Seitenaufbau (wenn keine AJAX-Anfrage vorliegt) ---
// Variablen für die Anzeige initialisieren
$allComicIds = getExistingComicIds($lowresDir, $hiresDir);
$existingThumbnailIds = getExistingThumbnailIds($thumbnailDir);
$missingThumbnails = findMissingThumbnails($allComicIds, $existingThumbnailIds);

// Gemeinsamen Header einbinden.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    // Fallback oder Fehlerbehandlung, falls Header nicht gefunden wird.
    echo "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><title>Fehler</title></head><body><h1>Fehler: Header nicht gefunden!</h1>";
}

// Basis-URL für die Bildanzeige bestimmen
// Setze den Pfad relativ zum aktuellen Skript (admin/thumbnail_generator.php)
$thumbnailWebPath = '../assets/comic_thumbnails/';
?>

<article>
    <header>
        <h1>Thumbnail-Generator</h1>
    </header>

    <div class="content-section">
        <?php if ($gdError): ?>
            <p class="status-message status-red"><?php echo htmlspecialchars($gdError); ?></p>
        <?php endif; ?>

        <h2>Status der Thumbnails</h2>
        <?php if (empty($allComicIds)): ?>
            <p class="status-message status-orange">Es wurden keine Comic-Bilder in den Verzeichnissen `<?php echo htmlspecialchars($lowresDir); ?>` oder `<?php echo htmlspecialchars($hiresDir); ?>` gefunden.</p>
        <?php elseif (empty($missingThumbnails)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Thumbnails sind vorhanden.</p>
        <?php else: ?>
            <p class="status-message status-red">Es fehlen <?php echo count($missingThumbnails); ?> Thumbnails.</p>
            <h3>Fehlende Thumbnails (IDs):</h3>
            <ul id="missing-thumbnails-list">
                <?php foreach ($missingThumbnails as $id): ?>
                    <li><?php echo htmlspecialchars($id); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" id="generate-thumbnails-button" <?php echo $gdError ? 'disabled' : ''; ?>>Fehlende Thumbnails erstellen</button>
        <?php endif; ?>

        <!-- Lade-Indikator und Fortschrittsanzeige -->
        <div id="loading-spinner" style="display: none; text-align: center; margin-top: 20px;">
            <div class="spinner"></div>
            <p id="progress-text">Generiere Thumbnails...</p>
        </div>

        <!-- Ergebnisse der Generierung -->
        <div id="generation-results-section" style="margin-top: 20px; display: none;">
            <h2 style="margin-top: 20px;">Ergebnisse der Thumbnail-Generierung</h2>
            <p id="overall-status-message" class="status-message"></p>
            <div id="created-thumbnails-container" class="thumbnail-grid">
                <!-- Hier werden die erfolgreich generierten Thumbnails angezeigt -->
            </div>
            <p class="status-message status-red" style="display: none;" id="error-header-message">Fehler bei der Generierung:</p>
            <ul id="generation-errors-list">
                <!-- Hier werden Fehler angezeigt -->
            </ul>
        </div>
    </div>
</article>

<!-- Modal für Nachrichten -->
<div id="message-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center;">
    <div style="background-color: #fefefe; margin: auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
        <p id="modal-message" style="font-size: 1.1em; margin-bottom: 20px;"></p>
        <button onclick="document.getElementById('message-modal').style.display='none'" style="padding: 10px 20px; border-radius: 5px; border: none; background-color: #007bff; color: white; cursor: pointer; font-size: 1em;">OK</button>
    </div>
</div>

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

    /* Thumbnail Grid Layout */
    .thumbnail-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
        padding-bottom: 20px;
    }
    .thumbnail-item {
        text-align: center;
        border: 1px solid #ccc;
        padding: 5px;
        border-radius: 8px;
        width: calc(25% - 7.5px); /* Für 4 Bilder pro Reihe mit 10px Abstand */
        min-width: 180px; /* Mindestbreite für Responsivität */
        height: 260px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-sizing: border-box;
        overflow: hidden;
    }
    .thumbnail-item img {
        display: block;
        max-width: 100%;
        max-height: calc(100% - 20px); /* Platz für Text unten */
        object-fit: contain;
        border-radius: 4px;
        margin-bottom: 5px;
    }
    .thumbnail-item span {
        word-break: break-all;
        font-size: 0.8em;
    }

    /* Responsive Anpassungen für den Thumbnail-Grid */
    @media (max-width: 1200px) {
        .thumbnail-item {
            width: calc(33.333% - 6.666px); /* 3 pro Reihe */
        }
    }
    @media (max-width: 800px) {
        .thumbnail-item {
            width: calc(50% - 5px); /* 2 pro Reihe */
        }
    }
    @media (max-width: 500px) {
        .thumbnail-item {
            width: 100%; /* 1 pro Reihe */
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateButton = document.getElementById('generate-thumbnails-button');
    const loadingSpinner = document.getElementById('loading-spinner');
    const progressText = document.getElementById('progress-text');
    const missingThumbnailsList = document.getElementById('missing-thumbnails-list');
    const createdThumbnailsContainer = document.getElementById('created-thumbnails-container');
    const generationResultsSection = document.getElementById('generation-results-section');
    const overallStatusMessage = document.getElementById('overall-status-message');
    const errorHeaderMessage = document.getElementById('error-header-message');
    const errorsList = document.getElementById('generation-errors-list');

    // Funktion zum Anzeigen einer benutzerdefinierten Modal-Nachricht
    function showMessage(message) {
        document.getElementById('modal-message').textContent = message;
        document.getElementById('message-modal').style.display = 'flex';
    }

    // Die Liste der fehlenden IDs, direkt von PHP übergeben
    const initialMissingIds = <?php echo json_encode($missingThumbnails); ?>;
    let remainingIds = [...initialMissingIds];
    let createdCount = 0;
    let errorCount = 0;

    if (generateButton) {
        generateButton.addEventListener('click', function() {
            if (remainingIds.length === 0) {
                showMessage('Es sind keine Thumbnails zu generieren.');
                return;
            }

            // UI zurücksetzen und Ladezustand anzeigen
            generateButton.disabled = true;
            loadingSpinner.style.display = 'block';
            generationResultsSection.style.display = 'block';
            overallStatusMessage.textContent = '';
            overallStatusMessage.className = 'status-message'; // Reset class
            createdThumbnailsContainer.innerHTML = '';
            errorsList.innerHTML = '';
            errorHeaderMessage.style.display = 'none'; // Hide error header initially

            createdCount = 0;
            errorCount = 0;

            processNextThumbnail();
        });
    }

    async function processNextThumbnail() {
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
                overallStatusMessage.textContent = `Alle ${createdCount} Thumbnails erfolgreich generiert!`;
                overallStatusMessage.className = 'status-message status-green';
            }
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

            const data = await response.json(); // JSON-Antwort parsen

            if (data.success) {
                createdCount++;
                // Füge das neue Thumbnail zur Anzeige hinzu
                const thumbnailDiv = document.createElement('div');
                thumbnailDiv.className = 'thumbnail-item';
                thumbnailDiv.innerHTML = `
                    <img src="${data.imageUrl}" alt="Thumbnail ${data.comicId}">
                    <span>${data.comicId}</span>
                `;
                createdThumbnailsContainer.appendChild(thumbnailDiv);

                // Entferne die ID aus der Liste der fehlenden Thumbnails (visuell)
                if (missingThumbnailsList) {
                    const listItem = missingThumbnailsList.querySelector(`li`); // find first li
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

        // Fahre mit dem nächsten Thumbnail fort (rekursiver Aufruf)
        processNextThumbnail();
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
