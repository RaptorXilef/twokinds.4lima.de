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
        $path = \rtrim($this->config->get('root_path'), '/\\') . '/package.json';

        try {
            $data = $this->jsonHelper->read($path);

            return $data['version'] ?? '1.0.0';
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
