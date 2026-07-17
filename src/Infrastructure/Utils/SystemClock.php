<?php

declare(strict_types=1);

namespace App\Infrastructure\Utils;

use App\Contracts\Utils\ClockInterface;

/**
 * TODO DOCBLOCK
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function nowAsString(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}
