<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface SiteGeneratorInterface
{
    /**
     * Markiert die Seite für eine Neugenerierung (z.B. am Ende des Requests via Destruktor).
     */
    public function generateAll(): void;
}
