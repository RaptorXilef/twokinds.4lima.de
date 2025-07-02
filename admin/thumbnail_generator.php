<?php
/**
 * Dies ist die Administrationsseite für den Thumbnail-Generator.
 * Sie überprüft, welche Comic-Thumbnails fehlen und bietet die Möglichkeit, diese zu erstellen.
 * Nach der Erstellung werden die neu erstellten Bilder entweder als Bilder oder als Textliste angezeigt,
 * abhängig von der Anzahl der fehlenden Bilder (weniger als 40 = visuell, 40 oder mehr = Text).
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
 * Generiert fehlende Thumbnails aus Quellbildern.
 * Zielgröße: 200x260 Pixel (Annahme, basierend auf typischen Comic-Thumbnail-Größen).
 * @param array $missingComicIds Die IDs der Comics, für die Thumbnails erstellt werden sollen.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $thumbnailDir Pfad zum Thumbnail-Verzeichnis.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellte Pfade) und 'errors' (Fehlermeldungen).
 */
function generateThumbnails(array $missingComicIds, string $lowresDir, string $hiresDir, string $thumbnailDir): array {
    $createdImages = [];
    $errors = [];

    // Erstelle den Thumbnail-Ordner, falls er nicht existiert.
    if (!is_dir($thumbnailDir)) {
        if (!mkdir($thumbnailDir, 0755, true)) {
            $errors[] = "Fehler: Thumbnail-Verzeichnis '$thumbnailDir' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.";
            return ['created' => [], 'errors' => $errors];
        }
    } elseif (!is_writable($thumbnailDir)) {
        $errors[] = "Fehler: Thumbnail-Verzeichnis '$thumbnailDir' ist nicht beschreibbar. Bitte Berechtigungen prüfen.";
        return ['created' => [], 'errors' => $errors];
    }

    // Definiere die Zielabmessungen für Thumbnails
    $targetWidth = 200;
    $targetHeight = 260;

    foreach ($missingComicIds as $comicId) {
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
            continue;
        }

        try {
            // Bildinformationen abrufen
            $imageInfo = @getimagesize($sourceImagePath); // @ unterdrückt Warnungen
            if ($imageInfo === false) {
                $errors[] = "Kann Bildinformationen für '$sourceImagePath' nicht abrufen (Comic-ID: $comicId).";
                continue;
            }
            list($width, $height, $type) = $imageInfo;

            $sourceImage = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $sourceImage = @imagecreatefromjpeg($sourceImagePath); // @ unterdrückt Warnungen
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = @imagecreatefrompng($sourceImagePath); // @ unterdrückt Warnungen (z.B. iCCP-Profil)
                    break;
                case IMAGETYPE_GIF:
                    $sourceImage = @imagecreatefromgif($sourceImagePath); // @ unterdrückt Warnungen
                    break;
                default:
                    $errors[] = "Nicht unterstütztes Bildformat für Comic-ID '$comicId': " . $sourceImageExtension . ". Erwartet: JPG, PNG, GIF.";
                    continue 2; // Äußere Schleife fortsetzen
            }

            if (!$sourceImage) {
                $errors[] = "Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.";
                continue;
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
                continue;
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
                continue;
            }

            // Bild als JPG speichern (für Thumbnails empfohlen)
            $thumbnailImagePath = $thumbnailDir . $comicId . '.jpg';
            if (imagejpeg($tempImage, $thumbnailImagePath, 90)) { // 90% Qualität
                $createdImages[] = $thumbnailImagePath;
            } else {
                $errors[] = "Fehler beim Speichern des Thumbnails für Comic-ID '$comicId' nach '$thumbnailImagePath'.";
            }

            // Speicher freigeben
            imagedestroy($sourceImage);
            imagedestroy($tempImage);

        } catch (Exception $e) {
            $errors[] = "Ausnahme bei Comic-ID '$comicId': " . $e->getMessage();
        }
    }
    return ['created' => $createdImages, 'errors' => $errors];
}

// Variablen für die Anzeige initialisieren
$allComicIds = getExistingComicIds($lowresDir, $hiresDir);
$existingThumbnailIds = getExistingThumbnailIds($thumbnailDir);
$missingThumbnails = findMissingThumbnails($allComicIds, $existingThumbnailIds);
$generationResults = null;

// Formularübermittlung verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_thumbnails') {
    if ($gdError === null) {
        $generationResults = generateThumbnails($missingThumbnails, $lowresDir, $hiresDir, $thumbnailDir);
        // Listen nach der Generierung aktualisieren
        $existingThumbnailIds = getExistingThumbnailIds($thumbnailDir);
        $missingThumbnails = findMissingThumbnails($allComicIds, $existingThumbnailIds);
    } else {
        $generationResults = ['created' => [], 'errors' => [$gdError]];
    }
}

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
            <ul>
                <?php foreach ($missingThumbnails as $id): ?>
                    <li><?php echo htmlspecialchars($id); ?></li>
                <?php endforeach; ?>
            </ul>
            <form action="" method="POST">
                <button type="submit" name="action" value="generate_thumbnails" <?php echo $gdError ? 'disabled' : ''; ?>>Fehlende Thumbnails erstellen</button>
            </form>
        <?php endif; ?>

        <?php if ($generationResults !== null): ?>
            <h2 style="margin-top: 20px;">Ergebnisse der Thumbnail-Generierung</h2>
            <?php if (!empty($generationResults['created'])): ?>
                <?php
                // Anzeigemodus basierend auf der Anzahl der erstellten Bilder bestimmen
                $displayAsVisual = count($generationResults['created']) < 40; // Grenze bei 40 Bildern
                ?>

                <?php if ($displayAsVisual): ?>
                    <p class="status-message status-green"><?php echo count($generationResults['created']); ?> Thumbnails erfolgreich erstellt:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; padding-bottom: 20px;">
                        <?php foreach ($generationResults['created'] as $path):
                            $comicId = basename($path, '.jpg');
                            $imageUrl = $thumbnailWebPath . $comicId . '.jpg?' . time(); // Cache-Buster
                        ?>
                            <div style="text-align: center; border: 1px solid #ccc; padding: 5px; border-radius: 8px; width: calc(25% - 7.5px); min-width: 180px; height: 260px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; overflow: hidden;">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Thumbnail <?php echo htmlspecialchars($comicId); ?>" style="display: block; max-width: 100%; max-height: calc(100% - 20px); object-fit: contain; border-radius: 4px; margin-bottom: 5px;">
                                <span style="word-break: break-all; font-size: 0.8em;"><?php echo htmlspecialchars($comicId); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="status-message status-green"><?php echo count($generationResults['created']); ?> Thumbnails erfolgreich erstellt:</p>
                    <ul>
                        <?php foreach ($generationResults['created'] as $path): ?>
                            <li><?php echo htmlspecialchars(basename($path)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <p class="status-message status-orange">Es wurden keine neuen Thumbnails erstellt.</p>
            <?php endif; ?>

            <?php if (!empty($generationResults['errors'])): ?>
                <p class="status-message status-red">Fehler bei der Generierung:</p>
                <ul>
                    <?php foreach ($generationResults['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</article>

<?php
// Gemeinsamen Footer einbinden.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
}
?>
