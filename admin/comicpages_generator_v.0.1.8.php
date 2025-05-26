<?php
/*
 * Dieses Skript generiert Comicseiten in PHP-Dateien.
 * Es durchsucht ein angegebenes Verzeichnis nach Bilddateien (PNG, JPG, GIF),
 * generiert für jede Bilddatei eine PHP-Datei mit entsprechendem Inhalt und Navigation,
 * und speichert die erstellten PHP-Dateien in einem Ausgabeordner ab.
 * Es erstellt auch eine index.php-Datei, die auf die zuletzt generierte PHP-Datei verweist.
 * Der Dateiname der Bilddatei bestimmt das Datum der Comicseite.
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


$includesFolderForComicpages = './includes/'; // Pfad zu den Include-Dateien (relativ zum Stammverzeichnis)
$designFolderForComicpages = $includesFolderForComicpages . 'design/'; // Pfad zu den Design-Dateien header und footer (relativ zum Stammverzeichnis)
$imageFolderForComicpages = './comic/'; // Pfad zu den Bildern in den generierten PHP-Dateien (relativ zum Stammverzeichnis)
$imageFolderForComicpagesHires = './comic_hires/';
$imageFolderForGeneratingPHPfiles = '../comic/'; // Pfad zu den Bilddateien (relativ zum Stammverzeichnis)
$outputFolderForGeneratingPHPfiles = '../'; // Pfad zum Ausgabeordner für die generierten PHP-Dateien (relativ zum Stammverzeichnis)

$images = array_merge(glob($imageFolderForGeneratingPHPfiles . '*.png'), glob($imageFolderForGeneratingPHPfiles . '*.jpg'), glob($imageFolderForGeneratingPHPfiles . '*.gif')); // Liest alle PNG-, GIF- und JPG-Dateien im Ordner
sort($images); // Sortiert die Bilder alphabetisch

echo "<h1 style='font-weight: bold;'>Comicnamen eingeben bei:</h1>";
echo '<p>includes/comicnamen.php</p>';

echo "<h1 style='color: green; font-weight: bold;'>Comicseiten-Generator gestartet</h1>";

$createdPhpFiles = array(); // Array zum Speichern der erstellten PHP-Dateien

// Lösche die Dateien index.php und die alphabetisch letzte ZAHL.php
$indexPhpFile = $outputFolderForGeneratingPHPfiles . 'index.php';
if (file_exists($indexPhpFile)) {
    unlink($indexPhpFile);
    echo "<p>Die Datei index.php wurde erfolgreich gelöscht.</p>";
}

$lastNumberedPhpFile = '';
$numberedPhpFiles = array_filter(glob($outputFolderForGeneratingPHPfiles . '[0-9]*.php'), 'is_file');
if (!empty($numberedPhpFiles)) {
    rsort($numberedPhpFiles);
    $lastNumberedPhpFile = reset($numberedPhpFiles);
    unlink($lastNumberedPhpFile);
    echo "<p>Die Datei $lastNumberedPhpFile wurde erfolgreich gelöscht.</p>";
}

foreach ($images as $image) {
    $imageName = basename($image);
    $phpFileName = $outputFolderForGeneratingPHPfiles . str_replace(array('.png', '.jpg', '.gif'), '.php', $imageName);

    if (file_exists($phpFileName)) {
        continue; // Wenn die PHP-Datei bereits existiert, überspringe sie und fahre mit der nächsten Datei fort
    }

    $previousPhpFile = '';
    $nextPhpFile = '';
    $index = array_search($image, $images);

    if ($index > 0) {
        $previousImage = $images[$index - 1];
        $previousPhpFile = str_replace(array('.png', '.jpg', '.gif'), '.php', basename($previousImage));
    }

    if ($index < count($images) - 1) {
        $nextImage = $images[$index + 1];
        $nextPhpFile = str_replace(array('.png', '.jpg', '.gif'), '.php', basename($nextImage));
    }

    $date = DateTime::createFromFormat('Ymd', substr($imageName, 0, 8));
    $formattedDate = $date->format('d.m.Y');

    $comicNameOutput = '$comicNameInput' . substr($imageName, 0, 8);
    $comicTypOutput = '$comicTypInput' . substr($imageName, 0, 8);

    $imageNamePng = str_replace(".png", ".jpg", $imageName);
    
    $phpContent = <<<PHP
<?php include '{$includesFolderForComicpages}comicnamen.php'; ?>
<?php include '{$designFolderForComicpages}comicpages_header.php'; ?>


<?php echo $comicTypOutput; ?>
<?php /*ÜBERSCHRIFT DER SEITE*/ ?>{$formattedDate}:
<?php echo $comicNameOutput; ?><?php include '{$designFolderForComicpages}comicpages_body_01.php'; ?>
<?php /*URL BILD*/ ?><a href="{$imageFolderForComicpagesHires}{$imageNamePng}" target="_blank"><img src="{$imageFolderForComicpages}{$imageName}" title="<?php echo $comicNameOutput; ?>" alt="Comic Page: <?php echo $comicNameOutput; ?>" width="825"></a>
<?php include '{$designFolderForComicpages}comicpages_body_02.php'; ?>
<?php /*URL Vorheriges*/ ?><a href="{$previousPhpFile}" class="navarrow navprev">
<?php include '{$designFolderForComicpages}comicpages_body_03.php'; ?>
<?php /*URL Nächstes*/ ?><a href="{$nextPhpFile}" class="navarrow navnext">

<?php include '{$designFolderForComicpages}comicpages_body_04.php'; ?>
<?php include '{$designFolderForComicpages}comicpages_footer.php'; ?>
PHP;

    file_put_contents($phpFileName, $phpContent);

    $createdPhpFiles[] = $phpFileName; // Füge den Dateinamen zur Liste der erstellten PHP-Dateien hinzu
}

echo "<h2 style='color: orange; font-weight: bold;'>Erstellte PHP-Dateien:</h2>\n";
echo "<ul>\n";
foreach ($createdPhpFiles as $createdPhpFile) {
    echo "<li><a href='{$createdPhpFile}' target='_blank'>$createdPhpFile</a></li>\n";
}
echo "</ul>\n";

echo "<h2 style='color: green; font-weight: bold;'>Vorgang erfolgreich beendet</h2>";

// Erstellt index.php mit dem Inhalt der letzten generierten PHP-Datei
if (!empty($createdPhpFiles)) {
    $lastPhpFile = end($createdPhpFiles);
    $lastPhpFileRelativePath = str_replace('../', '', $lastPhpFile);
    $indexPhpFile = $outputFolderForGeneratingPHPfiles . 'index.php';

    if (!file_exists($indexPhpFile)) {
        $indexPhpContent = "<?php include '{$lastPhpFileRelativePath}'; ?>";
        file_put_contents($indexPhpFile, $indexPhpContent);
        echo "<p>Die Datei index.php wurde erstellt.</p>";
    }
}

require('includes/design/footer.php');
?>
