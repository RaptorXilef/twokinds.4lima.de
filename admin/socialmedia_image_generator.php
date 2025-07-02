<?php
/**
 * Dies ist die Administrationsseite für den Social Media Bild-Generator.
 * Sie überprüft, welche Comic-Social-Media-Bilder fehlen und bietet die Möglichkeit, diese zu erstellen.
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
$socialMediaDir = __DIR__ . '/../assets/comic_socialmedia/'; // Neues Verzeichnis für Social Media Bilder

// Stelle sicher, dass die GD-Bibliothek geladen ist.
if (!extension_loaded('gd')) {
    $gdError = "FEHLER: Die GD-Bibliothek ist nicht geladen. Social Media Bilder können nicht generiert werden. Bitte PHP-Konfiguration prüfen.";
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
 * @param string $socialMediaDir Pfad zum Social Media Bild-Verzeichnis.
 * @return array Eine Liste vorhandener Social Media Bild-IDs (Dateinamen ohne Erweiterung).
 */
function getExistingSocialMediaImageIds(string $socialMediaDir): array {
    $imageIds = [];
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Prüfe auf gängige Bild-Erweiterungen

    if (is_dir($socialMediaDir)) {
        $files = scandir($socialMediaDir);
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
 * Generiert fehlende Social Media Vorschaubilder aus Quellbildern.
 * Zielgröße: 1200x630 Pixel (Open Graph Standard).
 * @param array $missingComicIds Die IDs der Comics, für die Social Media Bilder erstellt werden sollen.
 * @param string $lowresDir Pfad zum lowres-Verzeichnis.
 * @param string $hiresDir Pfad zum hires-Verzeichnis.
 * @param string $socialMediaDir Pfad zum Social Media Bild-Verzeichnis.
 * @return array Ein assoziatives Array mit 'created' (erfolgreich erstellte Pfade) und 'errors' (Fehlermeldungen).
 */
function generateSocialMediaImages(array $missingComicIds, string $lowresDir, string $hiresDir, string $socialMediaDir): array {
    $createdImages = [];
    $errors = [];

    // Erstelle den Social Media Bild-Ordner, falls er nicht existiert.
    if (!is_dir($socialMediaDir)) {
        if (!mkdir($socialMediaDir, 0755, true)) {
            $errors[] = "Fehler: Social Media Verzeichnis '$socialMediaDir' konnte nicht erstellt werden. Bitte Berechtigungen prüfen.";
            return ['created' => [], 'errors' => $errors];
        }
    } elseif (!is_writable($socialMediaDir)) {
        $errors[] = "Fehler: Social Media Verzeichnis '$socialMediaDir' ist nicht beschreibbar. Bitte Berechtigungen prüfen.";
        return ['created' => [], 'errors' => $errors];
    }

    // Definiere die Zielabmessungen für Social Media Bilder (Open Graph Standard)
    $targetWidth = 1200;
    $targetHeight = 630;

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
                    continue 2; // Äußere Schleife fortsetzen
            }

            if (!$sourceImage) {
                $errors[] = "Fehler beim Laden des Bildes für Comic-ID '$comicId' von '$sourceImagePath'.";
                continue;
            }

            // Berechne die Abmessungen, um das Bild auf dem Ziel-Canvas zu zentrieren, ohne es hochzuskalieren.
            $newWidth = $width;
            $newHeight = $height;

            // Nur herunterskalieren, wenn das Bild größer ist als die Zielabmessungen
            if ($width > $targetWidth || $height > $targetHeight) {
                $scaleFactor = min($targetWidth / $width, $targetHeight / $height);
                $newWidth = $width * $scaleFactor;
                $newHeight = $height * $scaleFactor;
            }

            // Berechne Offsets, um das Bild auf dem neuen Canvas zu zentrieren
            $offsetX = ($targetWidth - $newWidth) / 2;
            $offsetY = ($targetHeight - $newHeight) / 2;

            // Erstelle ein neues True-Color-Bild für die Social Media Vorschau
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

            // Bild auf die neue Größe und Position resamplen und kopieren
            if (!imagecopyresampled($tempImage, $sourceImage, (int)$offsetX, (int)$offsetY, 0, 0,
                                   (int)$newWidth, (int)$newHeight, $width, $height)) {
                $errors[] = "Fehler beim Resampling des Bildes für Comic-ID '$comicId'.";
                imagedestroy($sourceImage);
                imagedestroy($tempImage);
                continue;
            }

            // Bild als JPG speichern (für Open Graph empfohlen, wegen Konsistenz und Dateigröße)
            $socialMediaImagePath = $socialMediaDir . $comicId . '.jpg';
            if (imagejpeg($tempImage, $socialMediaImagePath, 90)) { // 90% Qualität
                $createdImages[] = $socialMediaImagePath;
            } else {
                $errors[] = "Fehler beim Speichern des Social Media Bildes für Comic-ID '$comicId' nach '$socialMediaImagePath'.";
            }

            // Speicher freigeben
            imagedestroy($sourceImage);
            imagedestroy($tempImage);

        } catch (Exception $e) {
            $errors[] = "Ausnahme bei Comic-ID '$comicId': " . $e->getMessage();
        }
    }
    return ['created' => [], 'errors' => $errors];
}

// Variablen für die Anzeige initialisieren
$allComicIds = getExistingComicIds($lowresDir, $hiresDir);
$existingSocialMediaImageIds = getExistingSocialMediaImageIds($socialMediaDir);
$missingSocialMediaImages = findMissingSocialMediaImages($allComicIds, $existingSocialMediaImageIds);
$generationResults = null;

// Formularübermittlung verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_socialmedia_images') {
    if ($gdError === null) {
        $generationResults = generateSocialMediaImages($missingSocialMediaImages, $lowresDir, $hiresDir, $socialMediaDir);
        // Listen nach der Generierung aktualisieren
        $existingSocialMediaImageIds = getExistingSocialMediaImageIds($socialMediaDir);
        $missingSocialMediaImages = findMissingSocialMediaImages($allComicIds, $existingSocialMediaImageIds);
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
// Setze den Pfad relativ zum aktuellen Skript (admin/socialmedia_image_generator.php)
$socialMediaWebPath = '../assets/comic_socialmedia/';
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
            <p class="status-message status-orange">Es wurden keine Comic-Bilder in den Verzeichnissen `<?php echo htmlspecialchars($lowresDir); ?>` oder `<?php echo htmlspecialchars($hiresDir); ?>` gefunden.</p>
        <?php elseif (empty($missingSocialMediaImages)): ?>
            <p class="status-message status-green">Alle <?php echo count($allComicIds); ?> Social Media Bilder sind vorhanden.</p>
        <?php else: ?>
            <p class="status-message status-red">Es fehlen <?php echo count($missingSocialMediaImages); ?> Social Media Bilder.</p>
            <h3>Fehlende Social Media Bilder (IDs):</h3>
            <ul>
                <?php foreach ($missingSocialMediaImages as $id): ?>
                    <li><?php echo htmlspecialchars($id); ?></li>
                <?php endforeach; ?>
            </ul>
            <form action="" method="POST">
                <button type="submit" name="action" value="generate_socialmedia_images" <?php echo $gdError ? 'disabled' : ''; ?>>Fehlende Social Media Bilder erstellen</button>
            </form>
        <?php endif; ?>

        <?php if ($generationResults !== null): ?>
            <h2 style="margin-top: 20px;">Ergebnisse der Social Media Bild-Generierung</h2>
            <?php if (!empty($generationResults['created'])): ?>
                <?php
                // Anzeigemodus basierend auf der Anzahl der erstellten Bilder bestimmen
                $displayAsVisual = count($generationResults['created']) < 40; // Grenze bei 40 Bildern
                ?>

                <?php if ($displayAsVisual): ?>
                    <p class="status-message status-green"><?php echo count($generationResults['created']); ?> Social Media Bilder erfolgreich erstellt:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; padding-bottom: 20px;">
                        <?php foreach ($generationResults['created'] as $path):
                            $comicId = basename($path, '.jpg');
                            $imageUrl = $socialMediaWebPath . $comicId . '.jpg?' . time(); // Cache-Buster
                        ?>
                            <div style="text-align: center; border: 1px solid #ccc; padding: 5px; border-radius: 8px; width: 240px; height: 126px; display: flex; flex-direction: column; justify-content: center; align-items: center; box-sizing: border-box; overflow: hidden;">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Social Media Bild <?php echo htmlspecialchars($comicId); ?>" style="display: block; max-width: 100%; max-height: calc(100% - 20px); object-fit: contain; border-radius: 4px; margin-bottom: 5px;">
                                <span style="word-break: break-all; font-size: 0.8em;"><?php echo htmlspecialchars($comicId); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="status-message status-green"><?php echo count($generationResults['created']); ?> Social Media Bilder erfolgreich erstellt:</p>
                    <ul>
                        <?php foreach ($generationResults['created'] as $path): ?>
                            <li><?php echo htmlspecialchars(basename($path)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <p class="status-message status-orange">Es wurden keine neuen Social Media Bilder erstellt.</p>
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
