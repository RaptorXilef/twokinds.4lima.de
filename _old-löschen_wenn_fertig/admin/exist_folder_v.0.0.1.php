<?php
/*
 * Dieser Code überprüft zuerst, ob bestimmte Ordner existieren, und erstellt sie gegebenenfalls.
 * Anschließend wird überprüft, ob der Nutzer die Bestätigung gesendet hat.
 * Wenn die Bestätigung gesendet wurde, wird das Skript "firststart.php" eingebunden und weitere Schritte ausgeführt.
 * Wenn die Bestätigung nicht gesendet wurde, wird ein Bestätigungsformular angezeigt.
 */

session_start();
ob_start(); // Aktiviere den Output Buffering-Modus


// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt



$thumbnailsDirectory = '../thumbnails/';
$comicDirectory = '../comic/';
$adminDirectory = '../admin/';
$includeDirectory = '../includes/';

echo '<h1 style="color: orange; font-weight: bold;">Script für den ersten Start gestartet.</h1>';
echo "<br>";

// Prüfen, ob der Thumbnails-Ordner existiert
if (!file_exists($thumbnailsDirectory)) {
    // Ordner erstellen
    if (!mkdir($thumbnailsDirectory, 0777, true)) {
        die('Konnte den Thumbnails-Ordner nicht erstellen.');
    }
    echo 'Der Ordner "../thumbnails/" wurde erstellt.';
} else {
    echo 'Der Ordner "../thumbnails/" existiert bereits.';
}
echo "<br>";

// Prüfen, ob der Comic-Ordner existiert
if (!file_exists($comicDirectory)) {
    // Ordner erstellen
    if (!mkdir($comicDirectory, 0777, true)) {
        die('Konnte den Comic-Ordner nicht erstellen.');
    }
    echo 'Der Ordner "../comic/" wurde erstellt. Bitte übertrage die Comic-Bilder per FTP in diesen Ordner.';
} else {
    echo 'Der Ordner "../comic/" existiert bereits.';
}
echo "<br>";

// Prüfen, ob der Admin-Ordner existiert
if (!file_exists($adminDirectory)) {
    // Ordner erstellen
    if (!mkdir($adminDirectory, 0777, true)) {
        die('Konnte den Admin-Ordner nicht erstellen.');
    }
    echo 'Der Ordner "../admin/" wurde erstellt.';
} else {
    echo 'Der Ordner "../admin/" existiert bereits.';
}
echo "<br>";

// Prüfen, ob der Include-Ordner existiert
if (!file_exists($includeDirectory)) {
    // Ordner erstellen
    if (!mkdir($includeDirectory, 0777, true)) {
        die('Konnte den Include-Ordner nicht erstellen.');
    }
    echo 'Der Ordner "../includes/" wurde erstellt. Bitte übertrage die zu includierenden PHP-Dateien per FTP in diesen Ordner.';
} else {
    echo 'Der Ordner "../includes/" existiert bereits.';
}
echo "<br>";

// Überprüfen, ob der Nutzer die Bestätigung gesendet hat
if (isset($_POST['bestaetigung'])) {
    header("Refresh: 5; URL=index.php");
        echo '</br><p style="color: green;">Bestätigung angenommen. Sie werden auf die Adminstartseite weitergeleitet!</p>';
        require('includes/design/footer.php');
        ob_end_flush(); // Sendet den gespeicherten Output an den Browser und beendet den Output Buffering-Modus

    exit;
    // Weitere Schritte nach der Bestätigung des Nutzers
    // ...
} else {
    // Bestätigungsformular anzeigen
    echo '<form method="POST" action="">';
    echo '<input type="submit" name="bestaetigung" value="Bestätigen">';
    echo '</form>';
}



require_once('includes/design/footer.php');
ob_end_flush(); // Sendet den gespeicherten Output an den Browser und beendet den Output Buffering-Modus
?>
