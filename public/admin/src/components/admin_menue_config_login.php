<?php
/**
 * Dieses Skript enthält die Konfiguration für das Navigationsmenü im Admin-Bereich.
 * Es wird dynamisch in src/layout/header.php geladen, wenn sich der Benutzer im Admin-Bereich befindet.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
?>

<div class="sidebar-content">
  <!-- Menü-Navigation -->
  <nav id="menu" class="menu">
    <br><br><br><br>
    <a href="./index.php">Login</a>
    <br>
    <a href="../index.php">Zurück zum Comicbereich</a>
    <br>
    <br>
    <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT
        AUS</span></a>
  </nav>
  <!-- Menü Ende -->
</div>