<?php
/**
 * Dieses Skript enthält die Konfiguration für das Navigationsmenü im Admin-Bereich.
 * Es wird dynamisch in src/layout/header.php geladen, wenn sich der Benutzer im Admin-Bereich befindet.
 */

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
    <a href="./management_user.php">Benutzer verwalten</a>
    <a href="./management_login.php">Eigene Anmeldedaten ändern</a>
    </br>
    <a href="./initial_setup.php">Webseiten-Ersteinrichtung</a>
    </br>
    <a href="./upload_image.php">Bild-Upload</a>
    <a href="./generator_thumbnail.php">Thumbnail-Generator</a>
    <a href="./generator_image_socialmedia.php">Social Media Vorschau Generator</a>
    <!--<a href="./generator_image_socialmedia.php">Social Media Bild-Generator</a>-->
    <a href="./build_image_cache_and_busting.php">Bild-Cache-Generator</a>
    <br>
    <a href="./data_editor_comic.php">Comic Daten Editor</a>
    <a href="./generator_comic.php">Comic-Seiten-Generator</a>
    </br>
    <a href="./generator_rss.php">RSS-Generator</a>
    </br>
    <a href="./data_editor_archiv.php">Archiv Daten Editor</a>
    </br>
    <a href="./data_editor_sitemap.php">Sitemap Editor</a>
    <a href="./generator_sitemap.php">Sitemap Generator</a>
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
    margin-bottom: 10px;
    border-radius: 5px;
    text-align: center;
    font-size: 0.9em;
  }

  body.theme-night .session-timer {
    background-color: #002b3c;
  }

  .session-timer i {
    margin-right: 8px;
  }

  .session-timer strong {
    margin-left: 5px;
    font-family: 'Courier New', Courier, monospace;
    font-size: 1.1em;
  }
</style>