<?php

declare(strict_types=1);

namespace App\Contracts\System;

use Throwable;

interface ErrorLoggerInterface
{
    public function logThrowable(Throwable $throwable): void;
}
