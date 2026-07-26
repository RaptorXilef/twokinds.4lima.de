<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface AssetHelperInterface
{
    /**
     * Gibt die URL des Assets inkl. Cache-Busting-Parameter zurück.
     * Erwartet einen relativen Pfad aus dem public/ Verzeichnis, z.B. 'assets/css/main.min.css'
     */
    public function url(string $assetPath): string;
}
