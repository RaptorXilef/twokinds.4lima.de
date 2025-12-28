<?php

/**
 * Navigationsmenü für den Admin-Bereich.
 *
 * @file      ROOT/config/config_menue_admin.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @since     5.0.0 refactor: Struktur modernisiert, Labels zur Gruppierung eingeführt.
 */

$nonce = $nonce ?? '';
?>

<div class="sidebar-content admin-sidebar">
  <div id="session-timer-display" class="session-timer">
    <i class="fas fa-stopwatch"></i>
    <div class="timer-text">
        <span>Logout in:</span>
        <strong id="session-timer-countdown">10:00</strong>
    </div>
  </div>

  <nav id="menu" class="menu">
    <div class="menu-group">
        <span class="menu-label">System & User</span>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup' . $dateiendungPHP; ?>">Initial Setup</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_user' . $dateiendungPHP; ?>">Benutzer verwalten</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_login' . $dateiendungPHP; ?>">Profil & Sicherheit</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_reports' . $dateiendungPHP; ?>">Fehlermeldungen</a>
    </div>

    <div class="menu-group">
        <span class="menu-label">Inhalte editieren</span>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_comic' . $dateiendungPHP; ?>">Comic Editor</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_charaktere' . $dateiendungPHP; ?>">Charakter Editor</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_archiv' . $dateiendungPHP; ?>">Archiv Editor</a>
    </div>

    <div class="menu-group">
        <span class="menu-label">Generatoren</span>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_comic' . $dateiendungPHP; ?>">Seiten-Generator</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_thumbnail' . $dateiendungPHP; ?>">Thumbnails</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_image_socialmedia' . $dateiendungPHP; ?>">Social Preview</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_rss' . $dateiendungPHP; ?>">RSS Feed</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/generator_sitemap' . $dateiendungPHP; ?>">Sitemap</a>
    </div>

    <div class="menu-group">
        <span class="menu-label">Wartung</span>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/upload_image' . $dateiendungPHP; ?>">Bild-Upload</a>
        <a href="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting' . $dateiendungPHP; ?>">Cache-Management</a>
    </div>

    <div class="menu-footer">
        <a href="?action=logout&token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" class="logout-link">Abmelden</a>
        <a id="toggle_lights" class="theme-toggle jsdep" href="#">
            <span class="themelabel">Design: </span>
            <span class="themename">LICHT AUS</span>
        </a>
    </div>
  </nav>
</div>
