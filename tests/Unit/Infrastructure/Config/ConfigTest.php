<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Config;

use App\Infrastructure\Config\Config;
use RuntimeException;

\uses()->group('infrastructure', 'config');

\it('returns default value if key is not found', function (): void {
    $config = new Config([]);
    \expect($config->get('missing_key', 'fallback'))->toBe('fallback')
        ->and($config->get('another_missing'))->toBeNull();
})->covers(Config::class);

\it('returns existing value from settings array', function (): void {
    $config = new Config(['site_title' => 'My Comic']);
    \expect($config->get('site_title'))->toBe('My Comic');
})->covers(Config::class);

\it('returns mail settings as an array', function (): void {
    $config = new Config(['mail' => ['host' => 'smtp.test.com']]);
    \expect($config->getMailSettings())->toBe(['host' => 'smtp.test.com']);
})->covers(Config::class);

\it('returns empty array for mail settings if not set', function (): void {
    $config = new Config([]);
    \expect($config->getMailSettings())->toBe([]);
})->covers(Config::class);

\it('determines test mode correctly', function (): void {
    $config1 = new Config(['test_mode' => true]);
    $config2 = new Config(['test_mode' => false]);
    $config3 = new Config([]);

    \expect($config1->isTestMode())->toBeTrue()
        ->and($config2->isTestMode())->toBeFalse()
        // App's default for test_mode is TRUE if the array key is completely missing
        ->and($config3->isTestMode())->toBeTrue();
})->covers(Config::class);

\it('resolves base url with trailing slash', function (): void {
    $config = new Config(['base_url' => 'https://example.com']);
    \expect($config->getBaseUrl())->toBe('https://example.com/');
})->covers(Config::class);

\it('builds local base url if is_local_env is true and base_url missing', function (): void {
    $config = new Config([
        'is_local_env' => true,
        'server_protocol' => 'http://',
        'server_host' => 'localhost',
        'server_script' => '/my_folder/public/index.php',
    ]);
    \expect($config->getBaseUrl())->toBe('http://localhost/my_folder/public/');
})->covers(Config::class);

\it('throws exception if base url is missing and not local', function (): void {
    $config = new Config(['is_local_env' => false]);
    $config->getBaseUrl();
})->throws(RuntimeException::class, 'base_url" ist in der config/config.php nicht gesetzt!')->covers(Config::class);

\it('generates correct storage path', function (): void {
    $config = new Config([
        'root_path' => '/var/www/',
        'storage_path_prefix' => 'app_data/',
    ]);
    \expect($config->getStoragePath('cache.php'))->toBe('/var/www/app_data/cache.php');
})->covers(Config::class);
