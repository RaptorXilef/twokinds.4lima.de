<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;
use Throwable;

final readonly class SystemInfoService implements SystemInfoInterface
{
    public function __construct(
        private ConfigInterface $config,
        private JsonHelperInterface $jsonHelper,
    ) {
    }

    public function getCurrentVersion(): string
    {
        $rootRaw = $this->config->get('root_path', '');
        $root = \is_string($rootRaw) ? $rootRaw : '';
        $path = \rtrim($root, '/\\') . '/package.json';

        try {
            $data = $this->jsonHelper->read($path);

            $version = $data['version'] ?? '1.0.0';

            return \is_string($version) ? $version : '1.0.0';
        } catch (Throwable) {
            return '1.0.0'; // Fallback, falls Datei kurzzeitig nicht lesbar ist
        }
    }

    public function getChangelog(): string
    {
        // Später für das Admin-Dashboard implementierbar
        return '';
    }
}
