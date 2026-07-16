<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\ErrorLoggerInterface;
use App\Infrastructure\Storage\SafeJsonWriterTrait;

/**
 * Logger-Infrastruktur für Systemfehler.
 *
 * Schreibt geworfene Exceptions und fatale Fehler revisionssicher in eine lokale Datei.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class ErrorLogger implements ErrorLoggerInterface
{
    use SafeJsonWriterTrait;

    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * Schreibt ein Throwable (Exception/Error) formatiert in die system_error.log.
     * Erstellt den Ordner, falls er nicht existiert.
     *
     * @param \Throwable $throwable Der aufgetretene Fehler samt Stacktrace.
     */
    public function logThrowable(\Throwable $throwable): void
    {
        $logDir = $this->config->getStoragePath('logs');

        if (! \is_dir($logDir)) {
            @\mkdir($logDir, 0o755, true);
        }

        $logFile = $logDir . '/system_error.log';

        $timestamp = APP_REQUEST_TIME_STR;
        $message   = \sprintf(
            "[%s] [%s] %s in %s:%d\nStack Trace:\n%s\n%s\n",
            $timestamp,
            \get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString(),
            \str_repeat('=', 80),
        );

        $result = @\file_put_contents(
            $logFile,
            $message,
            \FILE_APPEND | \LOCK_EX,
        );
        if ($result === false) {
            throw new \RuntimeException('Kritischer Schreibfehler: system_error.log voll.');
        }
    }
}
