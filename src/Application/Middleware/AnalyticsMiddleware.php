<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\AnalyticsClientInterface;
use App\Contracts\Utils\ClockInterface;

/**
 * Sendet Serverseitige Events an Google Analytics (GA4).
 * Asynchron im Terminate-Prozess (nachdem der Request beantwortet wurde).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class AnalyticsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ConfigInterface $config,
        private SessionManager $sessionManager,
        private AnalyticsClientInterface $analyticsClient,
        private ClockInterface $clock,
    ) {
    }

    public function process(ServerRequest $request, callable $next): mixed
    {
        $response = $next($request);

        try {
            $this->trackEvent($request);
        } catch (\Throwable) {
        }

        return $response;
    }

    private function trackEvent(ServerRequest $request): void
    {
        if ($this->config->get('is_local_env', false)) {
            return;
        }

        $scriptName = $request->server['SCRIPT_NAME'] ?? '';
        if (\str_contains($scriptName, '/api/') || \str_contains($scriptName, 'cron.php') || \str_contains($scriptName, 'process_mail_queue.php')) {
            return;
        }

        // --- 1. DATENSCHUTZ-FIX: Consent-Prüfung ---
        $consentCookie = $request->cookie['twokinds_cookie_consent'] ?? null;
        if (! $consentCookie) {
            return; // Kein Consent-Cookie vorhanden -> Nichts tracken
        }

        $consent = \json_decode($consentCookie, true);
        if (empty($consent['analytics'])) {
            return; // Nutzer hat Analytics abgelehnt -> Nichts tracken
        }
        // -------------------------------------------

        $gaCfg     = $this->config->get('ga4_server_side', []);
        $gaId      = $gaCfg['measurement_id'] ?? '';
        $apiSecret = $gaCfg['api_secret'] ?? '';

        if ($gaId === '' || $apiSecret === '') {
            return;
        }

        if ($this->sessionManager->getAnalyticsId() === null) {
            $this->sessionManager->setAnalyticsId(\bin2hex(\random_bytes(16)));
        }

        // --- 2. GA4 Session-ID ---
        if (($this->sessionManager->getFormData()['ga4_session_id'] ?? null) === null) {
            $this->sessionManager->setFormData(['ga4_session_id' => $this->clock->now()->getTimestamp()]);
        }

        $sessionId = $this->sessionManager->getFormData()['ga4_session_id'];
        // ---------------------------------

        $baseUrl = $this->config->getBaseUrl() !== ''
            ? \rtrim($this->config->getBaseUrl(), '/')
            : 'https://' . ($request->server['SERVER_NAME'] ?? 'localhost');

        $pageLocation = $baseUrl . ($request->server['REQUEST_URI'] ?? '');
        $pageTitle    = \ucfirst(\basename($scriptName, '.php'));

        $this->analyticsClient->trackPageView(
            $this->sessionManager->getAnalyticsId(),
            (string) $sessionId,
            $pageLocation,
            $pageTitle,
        );
    }
}
