<?php
session_start();

// �berpr�fen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt der index.php

echo 'Willkommen ' . $_SESSION['username'];


echo '</br> <<------- Bitte w�hlen Sie eine Aktion im Men� auf der rechten Seite aus!';
echo '</br></br></br>';



require_once('includes/design/footer.php');

?>
