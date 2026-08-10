<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Infrastructure\System\GoogleAnalyticsClient;

\uses()->group('infrastructure', 'system', 'analytics');

\it('aborts tracking if credentials are missing', function (): void {
    $config = $this->createMock(ConfigInterface::class);
    $config->expects($this->once())
        ->method('get')
        ->with('ga4_server_side')
        ->willReturn([]);

    $client = new GoogleAnalyticsClient($config);

    // We cannot easily test curl execution in PHPUnit without interceptors,
    // but we can ensure the empty return path is hit without errors.
    $client->trackPageView('client123', 'sess123', '/home', 'Home');

    \expect(true)->toBeTrue(); // Simply asserting it didn't crash
})->covers(GoogleAnalyticsClient::class);
