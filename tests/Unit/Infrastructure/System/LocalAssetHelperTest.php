<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Infrastructure\System\LocalAssetHelper;

\uses()->group('infrastructure', 'system', 'assets');

\it('generates asset url with mtime cache fallback', function (): void {
    $config = $this->createStub(ConfigInterface::class);
    $config->method('getBaseUrl')->willReturn('https://twokinds.local');
    $config->method('get')->with('root_path')->willReturn(__DIR__);

    $helper = new LocalAssetHelper($config);

    // Will fallback to empty mtime if file doesn't exist
    $url = $helper->url('css/main.css');
    \expect($url)->toBe('https://twokinds.local/css/main.css');
})->covers(LocalAssetHelper::class);
