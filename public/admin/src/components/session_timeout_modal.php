<?php
/**
 * Stellt das Modal für den Session-Timeout und die zugehörige Logik bereit.
 * Dieses Skript wird nur für angemeldete Administratoren geladen.
 *
 * @file      ROOT/public/admin/src/components/session_timeout_modal.php
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

// Die $nonce und der CSRF-Token werden in der admin_init.php definiert und sind hier verfügbar.
$nonce = $nonce ?? '';
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!-- Session-Timeout Modal -->
<div id="sessionTimeoutModal" class="session-timeout-modal">
    <div class="modal-content">
        <h2>Sitzung läuft bald ab</h2>
        <p>Ihre Sitzung wird in <span id="sessionTimeoutCountdown">60</span> Sekunden aufgrund von Inaktivität beendet.
        </p>
        <p>Möchten Sie angemeldet bleiben?</p>
        <div class="modal-buttons">
            <button id="stayLoggedInBtn" class="button">Angemeldet bleiben</button>
            <button id="logoutBtn" class="button delete">Jetzt ausloggen</button>
        </div>
    </div>
</div>

<!-- Styles für das Session-Timeout Modal -->
<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    .session-timeout-modal {
        display: none;
        position: fixed;
        z-index: 10001;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        font-family: "Open Sans", "Lucida Grande", Arial, sans-serif;
    }

    .session-timeout-modal .modal-content {
        background-color: #fefefe;
        padding: 25px;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    body.theme-night .session-timeout-modal .modal-content {
        background-color: #00425c;
        border: 1px solid #007bb5;
        color: #fff;
    }

    .session-timeout-modal .modal-content h2 {
        margin-top: 0;
        font-size: 1.5em;
    }

    .session-timeout-modal .modal-buttons {
        margin-top: 25px;
        display: flex;
        justify-content: center;
        gap: 15px;
    }
</style>

<?php
// Pfad und Cache-Buster für das JavaScript (NEUE METHODE)
$sessionTimeoutJsFile = 'session_timeout.js';
$sessionTimeoutJsPathOnServer = DIRECTORY_PUBLIC_ADMIN_JS . DIRECTORY_SEPARATOR . $sessionTimeoutJsFile;
$sessionTimeoutJsWebUrl = Path::getAdminJsUrl($sessionTimeoutJsFile);
$cacheBuster = file_exists($sessionTimeoutJsPathOnServer) ? '?c=' . filemtime($sessionTimeoutJsPathOnServer) : '';

// Übergebe den CSRF-Token als globale JavaScript-Variable, bevor das Skript geladen wird.
echo "<script nonce=\"" . htmlspecialchars($nonce) . "\">window.csrfToken = '" . htmlspecialchars($csrfToken) . "';</script>";
echo "<script nonce=\"" . htmlspecialchars($nonce) . "\" type='text/javascript' src='" . htmlspecialchars($sessionTimeoutJsWebUrl . $cacheBuster) . "'></script>";
?>