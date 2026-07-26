<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface SystemInfoInterface
{
    public function getChangelog(): string;

    public function getCurrentVersion(): string;
}
