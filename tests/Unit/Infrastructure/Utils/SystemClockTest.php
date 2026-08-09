<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Utils;

use App\Infrastructure\Utils\SystemClock;
use DateTimeImmutable;

\uses()->group('infrastructure', 'utils');

\it('returns current time as DateTimeImmutable', function (): void {
    $clock = new SystemClock();
    $now = $clock->now();

    \expect($now)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($now->getTimestamp())->toBeLessThanOrEqual(\time());
})->covers(SystemClock::class);

\it('returns current time as formatted string', function (): void {
    $clock = new SystemClock();
    $nowStr = $clock->nowAsString();

    // Checks if string matches Y-m-d H:i:s format
    \expect(\preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $nowStr))->toBe(1);
})->covers(SystemClock::class);
