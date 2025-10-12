<?php
/**
 * Dieses Skript enthält die Konfiguration für das Navigationsmenü im Admin-Bereich (Login-Seite).
 * Es wird dynamisch in src/layout/header.php geladen, wenn der Benutzer im Admin-Bereich, aber nicht eingeloggt ist.
 *
 * @file      ROOT/public/admin/src/components/admin_menue_config_login.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
?>

<div class="sidebar-content">
  <!-- Menü-Navigation -->
  <nav id="menu" class="menu">
    <br><br><br><br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . $dateiendungPHP; ?>">Login</a>
    <br>
    <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>">Zurück zum Comicbereich</a>
    <br>
    <br>
    <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT
        AUS</span></a>
  </nav>
  <!-- Menü Ende -->
</div>