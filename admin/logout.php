<?php
session_start();

// Sitzung beenden und zur login.php weiterleiten
session_destroy();
header('Location: login.php');
exit();
?>
