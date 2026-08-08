<?php

/**
 * Ausfallsichere Anzeige der Wartungsseite für TwoKinds
 *
 * Path: public/maintenance.php
 */

declare(strict_types=1);

// 1. Sichere Base-URL Ermittlung (ohne Abhängigkeit von der Datenbank oder komplexen Configs)
$scriptPath = \str_replace('\\', '/', \dirname((string) $_SERVER['SCRIPT_NAME']));
$rootPath = \rtrim($scriptPath, '/public');
$baseUrl = \rtrim($rootPath, '/') . '/';

// 2. Klären, ob wir im Admin-Wartungsmodus sind (falls die Config existiert)
$isAdminMaintenance = false;
$configFile = __DIR__ . '/../config/config.php';
if (\file_exists($configFile)) {
    $settings = require $configFile;
    $isAdminMaintenance = !empty($settings['maintenance_mode_admin']);
}

// 3. Fallback Logo-Pfad (Prüft, ob ein spezifisches Logo da ist)
$logoPath = $baseUrl . 'appleicon.png'; // Fallback auf den TK-Button, den du im SCSS referenziert hast
$localLogoCheck = __DIR__ . '/appleicon.png'; // führenden / nicht vergessen!
$hasLogo = \file_exists($localLogoCheck);

// 4. HTTP-Statuscode setzen, damit Suchmaschinen wissen, dass die Seite nur temporär offline ist
if (!\headers_sent()) {
    \http_response_code(503);
    \header('Retry-After: 3600'); // Suchmaschinen sollen in 1 Stunde wiederkommen
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Wartungsarbeiten - Twokinds auf Deutsch</title>

    <style>
    body {
        background: #002b3c;
        /* TwoKinds Night Theme BG als Fallback */
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
        font-family: 'Open Sans', 'Lucida Grande', arial, sans-serif;
        color: #fff;
    }

    .c-maintenance-card {
        background: #00425c;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        text-align: center;
        max-width: 500px;
        border-top: 5px solid #00add9;
        /* TwoKinds Link Color */
        border-bottom: 5px solid #00add9;
    }

    .c-icon-large {
        font-size: 50px;
        margin-bottom: 20px;
        display: block;
    }

    .c-logo {
        max-width: 200px;
        margin-bottom: 20px;
        border-radius: 8px;
    }

    h1 {
        color: #fff;
        margin-top: 0;
    }

    .status-badge {
        display: inline-block;
        margin-top: 15px;
        padding: 5px 15px;
        background: #5a1e1e;
        color: #fce4e4;
        border: 1px solid #5a1e1e;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
    }

    </style>
</head>

<body>
    <div class="c-maintenance-card">
        <?php if ($hasLogo) { ?>
            <img src="<?php echo \htmlspecialchars($logoPath); ?>"
                 class="c-logo"
                 alt="Twokinds Logo">
        <?php } ?>

        <span class="c-icon-large">⚙️</span>

        <h1>Kurze Pause!</h1>
        <p style="color: #ccc; line-height: 1.6;">
            Wir aktualisieren gerade das System für <br>
            <strong>Twokinds auf Deutsch</strong>,<br>
            um dir das bestmögliche Leseerlebnis zu bieten.
        </p>
        <?php if ($isAdminMaintenance) { ?>
            <div class="status-badge">Vollständige Systemwartung (Admin-Modus)</div>
        <?php } ?>
        <p style="margin-top: 25px; font-weight: bold; color: #81dbfe;">In Kürze sind wir wieder für dich da!</p>
        <div
             style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #2a6177; font-size: 0.85rem; color: #777;">
            Vielen Dank für deine Geduld.
        </div>
    </div>
</body>

</html>
