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

    /**
     * @return array<string, mixed>
     */
    public function getMailSettings(): array
    {
        $mail = $this->get('mail', []);

        if (!\is_array($mail)) {
            return [];
        }

        /** @var array<string, mixed> $mail */
        return $mail;
    }

    public function isTestMode(): bool
    {
        return (bool) $this->get('test_mode', true);
    }

    public function getBaseUrl(): string
    {
        $configured = $this->get('base_url');
        if (\is_string($configured) && $configured !== '') {
            return \rtrim($configured, '/') . '/';
        }

        if ($this->get('is_local_env', false) === true) {
            $protocolRaw = $this->get('server_protocol', 'http://');
            $protocol = \is_string($protocolRaw) ? $protocolRaw : 'http://';

            $hostRaw = $this->get('server_host', 'localhost');
            $host = \is_string($hostRaw) ? $hostRaw : 'localhost';

            $scriptRaw = $this->get('server_script', '');
            $script = \is_string($scriptRaw) ? $scriptRaw : '';

            $path = \rtrim(\dirname($script), '/\\');
            $path = \str_replace('/api', '', $path);

            return $protocol . $host . $path . '/';
        }

        throw new RuntimeException('Sicherheits-Abbruch: "base_url" ist in der config/config.php nicht gesetzt! Host-Header-Fallback ist deaktiviert.');
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
