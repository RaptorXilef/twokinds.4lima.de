<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Infrastructure\System\SystemInfoService;
use RuntimeException;

\uses()->group('infrastructure', 'system', 'info');

\it('returns version from package.json', function (): void {
    $config = $this->createStub(ConfigInterface::class);
    $jsonHelper = $this->createStub(JsonHelperInterface::class);

    $jsonHelper->method('read')->willReturn(['version' => '5.2.1']);

    $service = new SystemInfoService($config, $jsonHelper);
    \expect($service->getCurrentVersion())->toBe('5.2.1')
        ->and($service->getChangelog())->toBe('');
})->covers(SystemInfoService::class);

\it('falls back to 1.0.0 if package.json is missing or invalid', function (): void {
    $config = $this->createStub(ConfigInterface::class);
    $jsonHelper = $this->createStub(JsonHelperInterface::class);

    $jsonHelper->method('read')->willThrowException(new RuntimeException('File not found'));

    $service = new SystemInfoService($config, $jsonHelper);
    \expect($service->getCurrentVersion())->toBe('1.0.0');
})->covers(SystemInfoService::class);
