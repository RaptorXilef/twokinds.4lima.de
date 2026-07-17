<?php

/**
 * Anzeige der Wartungsseite
 *
 * Path: Path: public/maintenance.php
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */

declare(strict_types=1);

// 1. Falls das Script über den Bootstrapper (app.php) läuft, ist $settings schon da.
// Falls nicht (Direktaufruf), laden wir sie hier sicherheitshalber.
if (! isset($settings)) {
    $settings = require __DIR__ . '/../config/config.php';
    if (\file_exists(__DIR__ . '/../config/config.local.php')) {
        $localSettings = require __DIR__ . '/../config/config.local.php';
        $settings      = \array_replace_recursive($settings, $localSettings);
    }
}

// 2. Fallback-Logik für base_url, falls sie im Array fehlt (wichtig für Ressourcen)
if (empty($settings['base_url'])) {
    // Fallback auf relativen Pfad, niemals HTTP_HOST vertrauen
    // Und wir ermitteln den Pfad zum Root-Verzeichnis
    $scriptPath           = \str_replace('\\', '/', \dirname((string) $_SERVER['SCRIPT_NAME']));
    $rootPath             = \rtrim($scriptPath, '/public');
    $settings['base_url'] = \rtrim($rootPath, '/') . '/';
}

$vereinsName = $settings['vereins_name'] ?? 'KGA'; // TODO noch auf twokinds.4lima.de anpassen!

// Suche Logo
$logoFile = null;
foreach (['webp', 'png', 'jpg'] as $ext) {
    // Sicherere Pfadprüfung für Logo
    $localPath = __DIR__ . \DIRECTORY_SEPARATOR . 'assets' . \DIRECTORY_SEPARATOR . 'img' .
        \DIRECTORY_SEPARATOR . 'logo' . \DIRECTORY_SEPARATOR . "logo.$ext";
    if (\file_exists($localPath)) {
        $logoFile = "assets/img/logo/logo.$ext";

        break;
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Wartungsarbeiten - <?php echo \htmlspecialchars($vereinsName); ?></title>

    <link rel="stylesheet"
          href="<?php echo $settings['base_url']; ?>assets/css/main.min.css">

    <style>
    body {
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
        font-family: sans-serif;
    }

    .c-maintenance-card {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        text-align: center;
        max-width: 500px;
        border-top: 5px solid #f59e0b;
    }

    .c-icon-large {
        font-size: 50px;
        margin-bottom: 20px;
        display: block;
    }

    .c-logo {
        max-width: 200px;
        margin-bottom: 20px;
    }

    </style>
</head>

<body>
    <div class="c-maintenance-card">
        <?php if ($logoFile) { ?>
            <img src="<?php echo $settings['base_url'] . $logoFile; ?>"
                 class="c-logo"
                 alt="Logo">
        <?php } ?>

        <span class="c-icon-large">
            <img src="<?php echo $settings['base_url']; ?>assets/img/icons/nav-tools.webp"
                 class="c-icon"
                 alt=""
                 style="width: 1.5em; height: 1.5em;">
        </span>

        <h1>Kurze Pause!</h1>
        <p style="color: #64748b; line-height: 1.6;">
            Wir aktualisieren gerade das System für die
            <strong><?php echo \htmlspecialchars($vereinsName); ?></strong>,
            um Ihnen den bestmöglichen Service zu bieten.
        </p>

        <?php if (! empty($settings['maintenance_mode_admin'])) { ?>
            <div style="display:inline-block; margin-top: 15px; padding: 5px 15px; background: #fee2e2;
                        color: #991b1b; border-radius: 20px; font-size: 0.75rem; font-weight: bold;
                        text-transform: uppercase;">
                Vollständige Systemwartung
            </div>
        <?php } ?>

        <p style="margin-top: 20px; font-weight: bold; color: #1e293b;">
            In Kürze sind wir wieder für Sie da.
        </p>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 0.85rem;
                    color: #94a3b8;">
            Vielen Dank für Ihr Verständnis.
        </div>
    </div>
</body>

</html>
