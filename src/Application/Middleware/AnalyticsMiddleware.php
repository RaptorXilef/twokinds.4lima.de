<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\AnalyticsClientInterface;
use App\Contracts\Utils\ClockInterface;
use Throwable;

/**
 * Sendet Serverseitige Events an Google Analytics (GA4).
 * Asynchron im Terminate-Prozess (nachdem der Request beantwortet wurde).
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
        } catch (Throwable) {
            // Tracking errors shouldn't crash the request -> ignore
        }

        return $response;
    }

    private function trackEvent(ServerRequest $request): void
    {
        if (!$this->isTrackingAllowed($request)) {
            return;
        }

        $gaCfgRaw = $this->config->get('ga4_server_side');
        $gaCfg = \is_array($gaCfgRaw) ? $gaCfgRaw : [];

        $gaId = \is_string($gaCfg['measurement_id'] ?? null) ? $gaCfg['measurement_id'] : '';
        $apiSecret = \is_string($gaCfg['api_secret'] ?? null) ? $gaCfg['api_secret'] : '';

        if ($gaId === '' || $apiSecret === '') {
            return;
        }

        $clientId = $this->resolveClientId();
        $sessionId = $this->resolveSessionId();
        $pageLocation = $this->resolvePageLocation($request);

        $scriptNameRaw = $request->server['SCRIPT_NAME'] ?? '';
        $scriptName = \is_string($scriptNameRaw) ? $scriptNameRaw : '';
        $pageTitle = \ucfirst(\basename($scriptName, '.php'));

        $this->analyticsClient->trackPageView(
            $clientId,
            $sessionId,
            $pageLocation,
            $pageTitle,
        );
    }

    private function isTrackingAllowed(ServerRequest $request): bool
    {
        if ($this->config->get('is_local_env', false) === true) {
            return false;
        }

        $scriptNameRaw = $request->server['SCRIPT_NAME'] ?? '';
        $scriptName = \is_string($scriptNameRaw) ? $scriptNameRaw : '';

        if (
            \str_contains($scriptName, '/api/')
            || \str_contains($scriptName, 'cron.php')
            || \str_contains($scriptName, 'process_mail_queue.php')
        ) {
            return false;
        }

        // --- 1. DATENSCHUTZ-FIX: Consent-Prüfung ---
        $consentCookie = $request->cookie['twokinds_cookie_consent'] ?? null;
        if (!\is_string($consentCookie) || $consentCookie === '') {
            return false; // Kein Consent-Cookie vorhanden
        }

        $consent = \json_decode($consentCookie, true);
        if (!\is_array($consent) || !isset($consent['analytics']) || $consent['analytics'] !== true) {
            return false; // Nutzer hat Analytics abgelehnt
        }

        return true;
    }

    private function resolveClientId(): string
    {
        $clientId = $this->sessionManager->getAnalyticsId();
        if (!\is_string($clientId) || $clientId === '') {
            $clientId = \bin2hex(\random_bytes(16));
            $this->sessionManager->setAnalyticsId($clientId);
        }

        return $clientId;
    }

    private function resolveSessionId(): string
    {
        // --- 2. GA4 Session-ID ---
        $formData = $this->sessionManager->getFormData();
        if (!isset($formData['ga4_session_id']) || !\is_scalar($formData['ga4_session_id'])) {
            $sessionId = (string) $this->clock->now()->getTimestamp();
            $formData['ga4_session_id'] = $sessionId;
            $this->sessionManager->setFormData($formData);

            return $sessionId;
        }

        return (string) $formData['ga4_session_id'];
    }

    private function resolvePageLocation(ServerRequest $request): string
    {
        $serverNameRaw = $request->server['SERVER_NAME'] ?? 'localhost';
        $serverName = \is_string($serverNameRaw) ? $serverNameRaw : 'localhost';

        $baseUrl = $this->config->getBaseUrl() !== ''
            ? \rtrim($this->config->getBaseUrl(), '/')
            : 'https://' . $serverName;

        $requestUriRaw = $request->server['REQUEST_URI'] ?? '';
        $requestUri = \is_string($requestUriRaw) ? $requestUriRaw : '';

        return $baseUrl . $requestUri;
    }
}
