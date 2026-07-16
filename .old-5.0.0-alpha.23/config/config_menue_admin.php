<?php

/**
 * Konfigurationsdatei für das Navigationsmenü im Admin-Bereich.
 *
 * @file      ROOT/config/config_menue_admin.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     4.1.0 - Initialversion.
 * @since     5.0.0 - Refactor: Design-Standard v5.3.0, Zwischenüberschriften & Session-Timer Fix.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
$nonce = $nonce ?? '';
$dateiendungPHP = $dateiendungPHP ?? '.php';
?>

<div class="sidebar-content">

    <div id="session-timer-display" class="session-timer">
        <i class="fas fa-stopwatch"></i>
        <span>Auto-Logout in:</span>
        <strong id="session-timer-countdown">10:00</strong>
    </div>

    <nav id="menu" class="menu">

        <span class="menu-label">System & Berichte</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup' . $dateiendungPHP; ?>">System-Prüfung</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/management_reports' . $dateiendungPHP; ?>">Fehlerberichte</a>



        <span class="menu-label">Charakter-Daten</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_charaktere' . $dateiendungPHP; ?>">Charakter-Editor</a>

        <span class="menu-label">Comic-Redaktion</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_comic' . $dateiendungPHP; ?>">Comic-Editor</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/generator_comic' . $dateiendungPHP; ?>">Seiten-Erstellung</a>

        <span class="menu-label">Archiv-Pflege</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_archiv' . $dateiendungPHP; ?>">Archiv-Editor</a>

        <span class="menu-label">Media-Werkzeuge</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/upload_image' . $dateiendungPHP; ?>">Bilder-Upload</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/generator_thumbnail' . $dateiendungPHP; ?>">Thumbnail-Generator</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/generator_image_socialmedia' . $dateiendungPHP; ?>">Social-Vorschau</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/build_image_cache_and_busting' . $dateiendungPHP; ?>">Cache-Verwaltung</a>

        <span class="menu-label">Optimierung & Feeds</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/data_editor_sitemap' . $dateiendungPHP; ?>">Sitemap-Editor</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/generator_sitemap' . $dateiendungPHP; ?>">Sitemap-Update</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/generator_rss' . $dateiendungPHP; ?>">RSS-Feed-Update</a>

        <span class="menu-label">Konto</span>
        <a class="logout-link" href="?action=logout&token=<?= htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <i class="fas fa-sign-out"></i> Abmelden
        </a>

        <span class="menu-label">Verwaltung</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/management_login' . $dateiendungPHP; ?>">Eignes Profil</a>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . '/management_user' . $dateiendungPHP; ?>">Zugangsrechte</a>

        <a id="toggle_lights" class="theme jsdep" href="#">
            <span class="themelabel">Design</span>
            <span class="themename">LICHT AUS</span>
        </a>

    </nav>
</div>
