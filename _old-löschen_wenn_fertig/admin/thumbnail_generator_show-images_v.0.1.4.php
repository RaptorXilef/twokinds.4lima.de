<?php
/*
 * Dieses Skript generiert Thumbnails für Bilder in einem angegebenen Verzeichnis.
 * Es durchsucht das Verzeichnis nach PNG-, JPG- und GIF-Dateien, generiert für jedes Bild ein Thumbnail
 * und speichert es in einem separaten Thumbnail-Verzeichnis ab.
 * Wenn ein Thumbnail bereits vorhanden ist, wird es übersprungen.
 * Am Ende werden die erstellten Thumbnails als Bilder angezeigt.
 * Der Timeout des Skripts wird nicht berücksichtigt.
 */

session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt



// Verzeichnisse für Eingabebilder und Thumbnails definieren
$imageFolderForGeneratingPHPfiles = '../comic';
$thumbnailFolderForGeneratingThumbnailFiles = '../thumbnails/';

// Ausgabe des Titels
echo "<h1><strong style='color: green;'>Thumbnail-Generator gestartet</strong></h1>" . PHP_EOL;

// Eingabebilder im PNG-Format, JPG-Format und GIF-Format abrufen
$pngFiles = glob($imageFolderForGeneratingPHPfiles . '/*.png');
$gifFiles = glob($imageFolderForGeneratingPHPfiles . '/*.gif');
$jpgFiles = glob($imageFolderForGeneratingPHPfiles . '/*.jpg');

// Alle Eingabebilder in eine Liste zusammenführen
$files = array_merge($pngFiles, $jpgFiles, $gifFiles);
sort($files); // Sortieren der Dateien in alphabetischer Reihenfolge
$createdThumbnails = []; // Leere Liste für erstellte Thumbnails

// Schleife über alle Eingabebilder
foreach ($files as $file) {
    $filename = basename($file);
    $thumbnailFilename = $thumbnailFolderForGeneratingThumbnailFiles . $filename;

    // Überprüfen, ob das Thumbnail bereits existiert
    if (file_exists($thumbnailFilename)) {
        // Ausgabe der Meldung für übersprungenes Thumbnail
        /*echo "<p><strong style='color: red; font-style: italic;'>Überspringe das Generieren des Thumbnails für <a href='{$thumbnailFilename}' target='_blank'>{$filename}</a>, da es bereits existiert.</strong></p>" . PHP_EOL;*/ ////////////
        continue; // Mit der nächsten Iteration fortfahren
    }

    // Ausgabe der Meldung für generiertes Thumbnail
    echo "<p style='color: green;'>Generiere das Thumbnail für <a href='{$thumbnailFilename}' target='_blank'>{$filename}</a>.</p>" . PHP_EOL;
    
    // Funktion zum Erstellen des Thumbnails aufrufen
    createThumbnail($file, $thumbnailFilename, 187, 250);
    
    // Thumbnail zur Liste der erstellten Thumbnails hinzufügen
    $createdThumbnails[] = $thumbnailFilename;
}

// Ausgabe der Erfolgsmeldung
echo "<h2><strong style='color: green; font-weight: bold;'>Vorgang erfolgreich beendet</strong></h2>" . PHP_EOL;

// Überprüfen, ob Thumbnails erstellt wurden
if (!empty($createdThumbnails)) {
    // Ausgabe der Liste mit erstellten Thumbnails
    echo "<p>Erstellte Thumbnails:</p>" . PHP_EOL;
    sort($createdThumbnails); // Sortieren der Thumbnails in alphabetischer Reihenfolge
    foreach ($createdThumbnails as $thumbnail) {
        // Ausgabe des Thumbnails
        echo "<img src='{$thumbnail}' alt='Thumbnail' style='max-width: 200px; margin-right: 10px;'>" . PHP_EOL;
    }
}

// Funktion zum Erstellen des Thumbnails
function createThumbnail($sourceFile, $thumbnailFile, $thumbnailWidth, $thumbnailHeight) {
    // Informationen über das Eingabebild abrufen
    $imageInfo = getimagesize($sourceFile);
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $sourceType = $imageInfo[2];

    // Abhängig vom Bildtyp das Eingabebild laden
    switch ($sourceType) {
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourceFile);
            break;
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourceFile);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourceFile);
            break;
        default:
            return; // Nicht unterstützter Bildtyp
    }

    // Leeres Bild für das Thumbnail erstellen
    $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
    
    // Eingabebild in das Thumbnail kopieren und verkleinern
    imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $sourceWidth, $sourceHeight);

    // Thumbnail als JPEG speichern
    imagejpeg($thumbnailImage, $thumbnailFile);

    // Speicher freigeben
    imagedestroy($sourceImage);
    imagedestroy($thumbnailImage);
}



require_once('includes/design/footer.php');
?>
