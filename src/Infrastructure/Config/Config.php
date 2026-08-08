<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Contracts\Config\ConfigInterface;
use RuntimeException;

final readonly class Config implements ConfigInterface
{
    /**
     * @param array<string, mixed> $settings Das rohe, zusammengeführte Konfigurations-Array.
     */
    public function __construct(
        private array $settings,
    ) {
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

    public function getMailSettings(): array
    {
        return (array) $this->get('mail', []);
    }

    public function isTestMode(): bool
    {
        return (bool) $this->get('test_mode', true);
    }

    public function getBaseUrl(): string
    {
        $configured = $this->get('base_url');
        if (!empty($configured)) {
            return \rtrim((string) $configured, '/') . '/';
        }

        if ($this->get('is_local_env', false)) {
            $protocol = $this->get('server_protocol', 'http://');
            $host = $this->get('server_host', 'localhost');
            $path = \rtrim(\dirname($this->get('server_script', '')), '/\\');
            $path = \str_replace('/api', '', $path);

            return $protocol . $host . $path . '/';
        }

        throw new RuntimeException('Sicherheits-Abbruch: "base_url" ist in der config/config.php nicht gesetzt! Host-Header-Fallback ist deaktiviert.');
    }

    public function getStoragePath(string $fileName): string
    {
        return \rtrim((string) $this->get('root_path'), '/\\') . '/' .
               \ltrim((string) $this->get('storage_path_prefix'), '/\\') . $fileName;
    }
}
