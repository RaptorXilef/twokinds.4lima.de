<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface RemoteResourceProberInterface
{
    /**
     * Prüft, welche Dateiendung unter einer Remote-URL tatsächlich erreichbar ist.
     */
    public function probeExtension(string $url, string $fallback = 'png'): string;
}
