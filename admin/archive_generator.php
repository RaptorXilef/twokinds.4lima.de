<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt



ini_set('display_errors', 1);
error_reporting(E_ALL);


// Die Variable $archiveNumberInput wird aus dem URL-Parameter ausgelesen.
$archiveNumberIdInput = $_GET['archiveNumberIdInput'];

// Dateien laden und Funktionen ausführen
        require('includes/homepage-generators/archive/config.php');
        require('includes/homepage-generators/archive/variables-' . $archiveNumberInput . '.php');
        require('includes/homepage-generators/archive/main-function.php');



$archiveNumberIdInputSubOne = $archiveNumberIdInput - 1;
$archiveNumberIdInputAddOne = $archiveNumberIdInput + 1;

echo '</br></br>>><a href="archive_generator.php?archiveNumberIdInput=' . $archiveNumberIdInputSubOne . '" alt="restart">Weiter mit Nr: ' . $archiveNumberIdInputSubOne . '?</a>' . '<< ---- >>' .'<a href="archive_generator.php?archiveNumberIdInput=' . $archiveNumberIdInputAddOne . '" alt="restart">Weiter mit Nr: ' . $archiveNumberIdInputAddOne . '?</a><<';


echo '</br></br><a href="archive_generator_start.php" alt="restart">Restart?</a>';



?>
</br></br>
<h2>Existierende Generatoren:</h2>
</br>
<?php
$ausgabeOrdnerinhaltArchivOrdner = 'includes/homepage-generators/archive';

// Scandir-Funktion zum Auslesen des Ordners
$ausgabeOrdnerinhaltArchivVerzeichnis = scandir($ausgabeOrdnerinhaltArchivOrdner);

// Iteriere über die Dateien im Ordner
foreach ($ausgabeOrdnerinhaltArchivVerzeichnis as $ausgabeOrdnerinhaltArchivDatei) {
  // Ignoriere "." und ".." (Standardordner in einem Verzeichnis)
  if ($ausgabeOrdnerinhaltArchivDatei != "." && $ausgabeOrdnerinhaltArchivDatei != "..") {
    echo $ausgabeOrdnerinhaltArchivDatei . "<br>"; // Gib den Dateinamen aus
  }
}




require_once('includes/design/footer.php');
?>