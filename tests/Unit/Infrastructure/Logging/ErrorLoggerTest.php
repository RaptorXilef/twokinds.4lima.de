<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Logging;

use App\Contracts\Config\ConfigInterface;
use App\Infrastructure\Logging\ErrorLogger;
use Closure;
use Exception;
use PHPUnit\Framework\MockObject\Stub;

\uses()->group('infrastructure', 'logging');

\it('ErrorLogger creates log directory if missing without crashing', function (): void {
    $stub = Closure::bind(fn (string $c): Stub => $this->createStub($c), $this, self::class);
    $config = $stub(ConfigInterface::class);

    $tempDir = \sys_get_temp_dir() . '/tk_logger_test_' . \uniqid();

    $config->method('get')->willReturnCallback(function (string $key) use ($tempDir) {
        return $key === 'root_path' ? $tempDir : null;
    });

    $logger = new ErrorLogger($config);
    $logger->logThrowable(new Exception('Test Crash'));

    \expect(\is_dir($tempDir . '/logs'))->toBeTrue();

    // Cleanup
    \rmdir($tempDir . '/logs');
    \rmdir($tempDir);
})->covers(ErrorLogger::class);
