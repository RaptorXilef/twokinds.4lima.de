<?php
/**
 * Stellt das Modal für den Session-Timeout und die zugehörige Logik bereit.
 * Dieses Skript wird nur für angemeldete Administratoren geladen.
 */

// Die $baseUrl wird in der header.php definiert und sollte hier verfügbar sein.
if (!isset($baseUrl)) {
    // Fallback, falls $baseUrl nicht gesetzt ist (sollte nicht passieren)
    $baseUrl = '/'; // Annahme des Stammverzeichnisses
    error_log("WARNUNG: \$baseUrl war in session_timeout_modal.php nicht gesetzt.");
}
// Die $nonce wird in der admin_init.php definiert.
$nonce = $nonce ?? '';
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
        /* Höher als der Cookie-Banner und andere Elemente */
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
// Pfad und Cache-Buster für das JavaScript
// __DIR__ ist hier /admin/src/components/, also gehen wir eine Ebene hoch zu /admin/src/ und dann in /js/
$sessionTimeoutJsPath = $baseUrl . 'admin/src/js/session_timeout.js?c=' . filemtime(__DIR__ . '/../js/session_timeout.js');

// Übergebe den CSRF-Token an das JavaScript in einer globalen Variable
echo "<script nonce=\"" . htmlspecialchars($nonce) . "\">window.csrfToken = '" . htmlspecialchars($_SESSION['csrf_token'] ?? '') . "';</script>";
// Lade das eigentliche Timeout-Skript
echo "<script nonce=\"" . htmlspecialchars($nonce) . "\" type='text/javascript' src='" . htmlspecialchars($sessionTimeoutJsPath) . "'></script>";
?>