<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Contracts\Config\ConfigInterface;

final readonly class Config implements ConfigInterface
{
    /**
     * @param array<string, mixed> $settings Das rohe, zusammengeführte Konfigurations-Array.
     */
    public function __construct(private array $settings)
    {
    }

    /**
     * Holt einen Wert direkt aus dem Einstellungs-Array.
     *
     * (Der wichtigste universelle Getter)
     *
     * @param string $key Der exakte Array-Schlüssel.
     * @param mixed $default Fallback bei Nichtexistenz.
     *
     * @return mixed Der gespeicherte Wert.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMailSettings(): array
    {
        $mail = $this->get('mail', []);
        if (!\is_array($mail)) {
            return [];
        }

        /** @var array<string, mixed> $mailArray */
        $mailArray = $mail;

        return $mailArray;
    }

    public function isTestMode(): bool
    {
        return $this->get('test_mode', true) === true;
    }

    public function getBaseUrl(): string
    {
        // 1. Wenn explizit in der Config gesetzt, diese bevorzugen
        $configured = $this->get('base_url');
        if (\is_string($configured) && $configured !== '') {
            return \rtrim($configured, '/');
        }

        // 2. CLI-Modus (Kommandozeile/Cronjobs) erkennen
        // @phpstan-ignore-next-line
        $isCli = \php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST']);
        if ($isCli) {
            $fallbackRaw = $this->get('cli_fallback_url', 'http://localhost');
            $fallback = \is_string($fallbackRaw) ? $fallbackRaw : 'http://localhost';

            return \rtrim($fallback, '/');
        }

        // 3. Regulärer Web-Aufruf: Protokoll und Host dynamisch auslesen
        // @phpstan-ignore-next-line
        $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            // phpcs:ignore Generic.Files.LineLength.TooLong
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'); // @phpstan-ignore-line

        $protocol = $isSecure ? 'https' : 'http';

        // @phpstan-ignore-next-line
        $hostRaw = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = \is_string($hostRaw) ? $hostRaw : 'localhost';

        return $protocol . '://' . $host;
    }

    public function getStoragePath(string $fileName): string
    {
        $rootRaw = $this->get('root_path', '');
        $root = \is_string($rootRaw) ? $rootRaw : '';

        $prefixRaw = $this->get('storage_path_prefix', '');
        $prefix = \is_string($prefixRaw) ? $prefixRaw : '';

        return \rtrim($root, '/\\') . '/' . \ltrim($prefix, '/\\') . $fileName;
    }
}
