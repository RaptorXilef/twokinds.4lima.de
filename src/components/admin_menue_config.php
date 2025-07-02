<?php
/**
 * Dieses Skript enthält die Konfiguration für das Navigationsmenü im Admin-Bereich.
 * Es wird dynamisch in src/layout/header.php geladen, wenn sich der Benutzer im Admin-Bereich befindet.
 */
?>
<a href="./index.php">Dashboard</a>
<a href="./index.php#manage-users">Benutzer verwalten</a>
</br>
<a href="./initial_setup.php">Webseiten-Ersteinrichtung</a>
</br>
<a href="./comic_generator.php">Comic-Seiten-Generator</a>
</br>
<!-- Weitere Admin-Links können hier später hinzugefügt werden -->
<a href="?action=logout">Logout</a>
