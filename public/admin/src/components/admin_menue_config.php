<?php
/**
 * Dieses Skript enthält die Konfiguration für das Navigationsmenü im Admin-Bereich.
 * Es wird dynamisch in header.php geladen, wenn sich der Benutzer im Admin-Bereich befindet.
 *
 * @file      ROOT/public/admin/src/components/admin_menue_config.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und URL-Konstanten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Die Variable $nonce wird in admin_init.php definiert und ist hier verfügbar.
$nonce = $nonce ?? '';
?>

<div class="sidebar-content">
  <!-- NEU: Session-Timer-Anzeige -->
  <div id="session-timer-display" class="session-timer">
    <i class="fas fa-stopwatch"></i>
    <span>Auto-Logout in:</span>
    <strong id="session-timer-countdown">10:00</strong>
  </div>

  <!-- Menü-Navigation -->
  <nav id="menu" class="menu">
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_user' . $dateiendungPHP; ?>">Benutzer
      verwalten</a>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_login' . $dateiendungPHP; ?>">Eigene Anmeldedaten
      ändern</a>
    </br>
    <a
      href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup' . $dateiendungPHP; ?>">Webseiten-Ersteinrichtung</a>
    </br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/upload_image' . $dateiendungPHP; ?>">Bild-Upload</a>
    <a
      href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_thumbnail' . $dateiendungPHP; ?>">Thumbnail-Generator</a>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_image_socialmedia' . $dateiendungPHP; ?>">Social
      Media Vorschau Generator</a>
    <a
      href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting' . $dateiendungPHP; ?>">Bild-Cache-Generator</a>
    </br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_charaktere' . $dateiendungPHP; ?>">Charakter
      Daten Editor</a>
    </br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_comic' . $dateiendungPHP; ?>">Comic Daten
      Editor</a>
    <a
      href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_comic' . $dateiendungPHP; ?>">Comic-Seiten-Generator</a>
    </br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_rss' . $dateiendungPHP; ?>">RSS-Generator</a>
    </br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_archiv' . $dateiendungPHP; ?>">Archiv Daten
      Editor</a>
    </br>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_sitemap' . $dateiendungPHP; ?>">Sitemap
      Editor</a>
    <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_sitemap' . $dateiendungPHP; ?>">Sitemap
      Generator</a>
    </br>
    <!-- CSRF-Token zum Logout-Link hinzugefügt -->
    <a href="?action=logout&token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">Logout</a>
    <br>
    <br>
    <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT
        AUS</span></a>
  </nav>
  <!-- Menü Ende -->
</div>

<!-- NEU: CSS für den Session-Timer mit CSP-Nonce -->
<style nonce="<?php echo htmlspecialchars($nonce); ?>">
  .session-timer {
    background-color: #00425c;
    color: #fff;
    padding: 10px;
    text-align: center;
    font-size: 0.9em;
    border-radius: 5px;
    margin-bottom: 15px;
  }

  .session-timer i {
    margin-right: 8px;
  }

  body.theme-night .session-timer {
    background-color: #002B3C;
    border: 1px solid #2a6177;
  }
</style>