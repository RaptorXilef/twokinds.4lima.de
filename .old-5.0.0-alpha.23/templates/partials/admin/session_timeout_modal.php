<?php

/**
 * Stellt das Modal für den Session-Timeout und die zugehörige Logik bereit.
 * Dieses Skript wird nur für angemeldete Administratoren geladen.
 *
 * @file      ROOT/templates/partials/admin/session_timeout_modal.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// Die $nonce und der CSRF-Token werden in der init_admin.php definiert und sind hier verfügbar.
$nonce = $nonce ?? '';
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- Session-Timeout Modal -->
<div id="sessionTimeoutModal" class="c-session-timeout">
    <div class="c-session-timeout__content">
        <h2 class="c-session-timeout__title">Sitzung läuft bald ab</h2>
        <p>Ihre Sitzung wird in <span id="sessionTimeoutCountdown">60</span> Sekunden aufgrund von Inaktivität beendet.
        </p>
        <p>Möchten Sie angemeldet bleiben?</p>
        <div class="c-session-timeout__actions">
            <button id="stayLoggedInBtn" class="button">Angemeldet bleiben</button>
            <button id="logoutBtn" class="button delete">Jetzt ausloggen</button>
        </div>
    </div>
</div>

<?php
// Pfad und Cache-Buster für das JavaScript (NEUE METHODE)
$sessionTimeoutJsFile = 'session_timeout.min.js';
$sessionTimeoutJsPathOnServer = DIRECTORY_PUBLIC_ADMIN_JS . DIRECTORY_SEPARATOR . $sessionTimeoutJsFile;
$sessionTimeoutJsWebUrl = Url::getAdminJsUrl($sessionTimeoutJsFile);
$cacheBuster = file_exists($sessionTimeoutJsPathOnServer) ? '?c=' . filemtime($sessionTimeoutJsPathOnServer) : '';

// Erzeuge die korrekte, öffentliche URL zum neuen API-Endpunkt.
$keepAliveUrl = DIRECTORY_PUBLIC_ADMIN_URL . '/api/?action=keep_alive';

// Übergebe die URL und den CSRF-Token als globale JavaScript-Variablen, bevor das Skript geladen wird.
echo "<script nonce=\"" . htmlspecialchars($nonce) . "\">";
echo "window.csrfToken = '" . htmlspecialchars($csrfToken) . "';";
echo "window.keepAliveUrl = '" . htmlspecialchars($keepAliveUrl) . "';";
echo "</script>";
echo "<script nonce=\"" . htmlspecialchars($nonce) . "\" type='text/javascript' src='" . htmlspecialchars($sessionTimeoutJsWebUrl . $cacheBuster) . "'></script>";
?>
