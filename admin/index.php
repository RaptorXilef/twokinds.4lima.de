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

echo 'Willkommen ' . $_SESSION['username'];

echo '</br></br> <<------- Bitte wählen Sie eine Aktion im Menü auf der rechten Seite aus!';
echo '</br></br></br>';

require_once('includes/homepage-generators/adminbereich/anleitung.php');

/*
$files = glob($directory . '*.php');
foreach ($files as $file) {
    echo $file . '<br>';
}
*/


require_once('includes/design/footer.php');

?>
