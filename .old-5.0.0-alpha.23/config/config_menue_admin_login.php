<?php

/**
 * Konfigurationsdatei für das Navigationsmenü im Admin-Bereich (Login-Seite).
 *
 * @file      ROOT/config/config_menue_admin_login.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     4.0.0 Umstellung auf dynamische Pfade.
 * @since     5.0.0 Refactor: Entfernung von <br>, Umstellung auf neuen Design-Standard.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
$nonce = $nonce ?? '';
$dateiendungPHP = $dateiendungPHP ?? '.php';
?>

<div class="sidebar-content page-login">
    <nav id="menu" class="menu">

        <span class="menu-label">Administration</span>
        <a href="<?= DIRECTORY_PUBLIC_ADMIN_URL . $dateiendungPHP; ?>">Login</a>

        <span class="menu-label">Navigation</span>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>">Comic-Bereich</a>

        <a id="toggle_lights" class="theme jsdep" href="#">
            <span class="themelabel">Theme</span>
            <span class="themename">LICHT AUS</span>
        </a>

    </nav>
</div>
