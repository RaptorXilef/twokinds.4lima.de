<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface ErrorLoggerInterface
{
    public function logThrowable(\Throwable $throwable): void;
}
