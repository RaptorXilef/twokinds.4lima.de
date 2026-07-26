<?php

declare(strict_types=1);

namespace App\Contracts\Utils;

/**
 * TODO DOCBLOCK
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface ClockInterface
{
    public function now(): \DateTimeImmutable;

    public function nowAsString(): string;
}
