<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\AnalyticsClientInterface;

final readonly class GoogleAnalyticsClient implements AnalyticsClientInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function trackPageView(string $clientId, string $sessionId, string $pageLocation, string $pageTitle): void
    {
        $gaCfgRaw = $this->config->get('ga4_server_side', []);
        $gaCfg = \is_array($gaCfgRaw) ? $gaCfgRaw : [];

        $gaIdRaw = $gaCfg['measurement_id'] ?? '';
        $gaId = \is_scalar($gaIdRaw) ? (string) $gaIdRaw : '';

        $apiSecretRaw = $gaCfg['api_secret'] ?? '';
        $apiSecret = \is_scalar($apiSecretRaw) ? (string) $apiSecretRaw : '';

        if ($gaId === '' || $apiSecret === '') {
            return;
        }

        $payload = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'page_view',
                    'params' => [
                        'page_location' => $pageLocation,
                        'page_title' => $pageTitle,
                        'session_id' => $sessionId,
                        'engagement_time_msec' => 1,
                    ],
                ],
            ],
        ];

        $ch = \curl_init('https://www.google-analytics.com/mp/collect?measurement_id=' . \urlencode($gaId) . '&api_secret=' . \urlencode($apiSecret));
        if ($ch === false) {
            return;
        }

        $jsonPayload = \json_encode($payload);
        if (!\is_string($jsonPayload)) {
            return;
        }

        \curl_setopt_array($ch, [
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTPS,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => $jsonPayload,
            \CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            \CURLOPT_TIMEOUT_MS => 250,
        ]);
        \curl_exec($ch);
    }
}
