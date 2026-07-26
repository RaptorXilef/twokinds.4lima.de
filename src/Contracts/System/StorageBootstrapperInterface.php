<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface StorageBootstrapperInterface
{
    public function bootstrap(): void;
}
