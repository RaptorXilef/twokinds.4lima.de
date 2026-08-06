<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface AnalyticsClientInterface
{
    public function trackPageView(string $clientId, string $sessionId, string $pageLocation, string $pageTitle): void;
}
