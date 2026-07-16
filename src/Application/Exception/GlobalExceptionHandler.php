<?php
declare(strict_types=1);

namespace App\Application\Exception;

use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\ErrorLoggerInterface;

/**
 * Zentraler Exception Handler für die Anwendung.
 *
 * Fängt ungeprüfte Ausnahmen sowie klassische PHP-Fehler ab, loggt diese
 * revisionssicher und gibt eine nutzerfreundliche HTML- oder JSON-Fehlerseite zurück.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class GlobalExceptionHandler
{
    public function __construct(
        private ConfigInterface $config,
        private ErrorLoggerInterface $logger,
    ) {
        // Preload ins Memory, falls Exception während eines Datei-Updates auftritt
        \class_exists(JsonResponse::class);
    }

    /**
     * Klinkt den Handler global in den PHP-Lebenszyklus ein.
     * Verwandelt auch klassische PHP-Warnungen und -Fehler in fangbare Exceptions.
     */
    public function register(): void
    {
        \set_exception_handler([$this, 'handleException']);

        // Verwandelt auch klassische PHP-Warnungen/Fehler in Exceptions, damit sie geloggt werden
        \set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (! (\error_reporting() & $errno)) {
                return false;
            }

            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
    }

    /**
     * Zentraler Auffangkorb für alle Exceptions und fatalen Fehler.
     *
     * @param \Throwable $exception Die geworfene Ausnahme.
     */
    public function handleException(\Throwable $exception): void
    {
        // 1. Fehler revisionssicher loggen
        $this->logger->logThrowable($exception);

        // 2. Prüfen, ob wir im Dev-Modus sind (dann wollen wir die echten Fehler sehen!)
        $isDev = (bool) $this->config->get('admin_dev_mode', false);

        // FIX: Nur ECHTE API-Calls (JSON Accept/Content-Type oder /api/ Route) als JSON beantworten, keine normalen HTML-Formulare!
        $isApi = \str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')
              || (isset($_SERVER['HTTP_ACCEPT']) && \str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
              || (isset($_SERVER['CONTENT_TYPE']) && \str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

        if ($isApi) {
            $msg = $isDev ? $exception->getMessage() : 'Ein interner Serverfehler ist aufgetreten.';
            // FIX: Senden erzwingen, um HTML-Rückgaben im API-Layer abzublocken
            JsonResponse::error($msg, 500)->send();
        }

        // HTML-Fehlerseite für normale Browser-Nutzer
        $this->renderErrorPage($exception, $isDev);
    }

    /**
     * Rendert eine formatierte HTML-Fehlerseite für Endnutzer oder Entwickler.
     *
     * @param \Throwable $exception Die aufgetretene Ausnahme.
     * @param bool       $isDev     Gibt an, ob der Stacktrace (Dev-Mode) angezeigt werden darf.
     */
    private function renderErrorPage(\Throwable $exception, bool $isDev): void
    {
        if (! \headers_sent()) {
            @\http_response_code(500);
        }

        $vereinsName  = \htmlspecialchars((string) $this->config->get('vereins_name', 'KGA'));
        $errorTitle   = 'Ups! Etwas ist schiefgelaufen';
        $errorMessage = 'Das System hat einen unerwarteten Fehler festgestellt. Keine Sorge, die Administratoren wurden automatisch benachrichtigt um das Problem zu beheben.';

        if ($isDev) {
            $errorTitle   = \sprintf('Dev-Mode: %s', \get_class($exception));
            $errorMessage = \sprintf(
                "<strong>Fehler:</strong> %s<br><br><strong>Datei:</strong> %s:%d<br><br><strong>Stacktrace:</strong><pre style='background:#f4f4f4; padding:10px; overflow-x:auto; font-size:12px;'>%s</pre>",
                \htmlspecialchars($exception->getMessage()),
                \htmlspecialchars($exception->getFile()),
                $exception->getLine(),
                \htmlspecialchars($exception->getTraceAsString()),
            );
        }

        ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Systemfehler - <?php echo $vereinsName; ?></title>
    <style>
        body { background:#f8fafc; font-family:sans-serif; display:flex; justify-content:center; padding:40px 20px; }
        .error-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 600px; width: 100%; border-top: 5px solid #e74c3c; }
        h1 { color:#c0392b; margin-top:0; }
        p { line-height: 1.6; color:#34495e; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:6px; font-weight:bold; }
        .btn:hover { background:#2980b9; }
    </style>
</head>
<body>
    <div class="error-card">
        <h1>🛑 <?php echo $errorTitle; ?></h1>
        <p><?php echo $errorMessage; ?></p>
        <a href="index.php" class="btn">Zur Startseite</a>
    </div>
</body>
</html>
        <?php
        exit;
    }
}
