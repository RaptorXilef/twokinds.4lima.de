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
        }

        return $response;
    }

    private function trackEvent(ServerRequest $request): void
    {
        if ($this->config->get('is_local_env', false) === true) {
            return;
        }

        $scriptNameRaw = $request->server['SCRIPT_NAME'] ?? '';
        $scriptName = \is_string($scriptNameRaw) ? $scriptNameRaw : '';

        if (\str_contains($scriptName, '/api/') || \str_contains($scriptName, 'cron.php') || \str_contains($scriptName, 'process_mail_queue.php')) {
            return;
        }

        // --- 1. DATENSCHUTZ-FIX: Consent-Prüfung ---
        $consentCookie = $request->cookie['twokinds_cookie_consent'] ?? null;
        if (!\is_string($consentCookie) || $consentCookie === '') {
            return; // Kein Consent-Cookie vorhanden -> Nichts tracken
        }

        $consent = \json_decode($consentCookie, true);
        if (!\is_array($consent) || !isset($consent['analytics']) || $consent['analytics'] !== true) {
            return; // Nutzer hat Analytics abgelehnt -> Nichts tracken
        }

        $gaCfg = $this->config->get('ga4_server_side');
        if (!\is_array($gaCfg)) {
            return;
        }

        $gaId = \is_string($gaCfg['measurement_id'] ?? null) ? $gaCfg['measurement_id'] : '';
        $apiSecret = \is_string($gaCfg['api_secret'] ?? null) ? $gaCfg['api_secret'] : '';

        if ($gaId === '' || $apiSecret === '') {
            return;
        }

        $clientId = $this->sessionManager->getAnalyticsId();
        if (!\is_string($clientId) || $clientId === '') {
            $clientId = \bin2hex(\random_bytes(16));
            $this->sessionManager->setAnalyticsId($clientId);
        }

        // --- 2. GA4 Session-ID ---
        $formData = $this->sessionManager->getFormData();
        if (!isset($formData['ga4_session_id']) || !\is_scalar($formData['ga4_session_id'])) {
            $sessionId = (string) $this->clock->now()->getTimestamp();
            $formData['ga4_session_id'] = $sessionId;
            $this->sessionManager->setFormData($formData);
        } else {
            $sessionId = (string) $formData['ga4_session_id'];
        }

        $serverNameRaw = $request->server['SERVER_NAME'] ?? 'localhost';
        $serverName = \is_string($serverNameRaw) ? $serverNameRaw : 'localhost';

        $baseUrl = $this->config->getBaseUrl() !== ''
            ? \rtrim($this->config->getBaseUrl(), '/')
            : 'https://' . $serverName;

        $requestUriRaw = $request->server['REQUEST_URI'] ?? '';
        $requestUri = \is_string($requestUriRaw) ? $requestUriRaw : '';

        $pageLocation = $baseUrl . $requestUri;
        $pageTitle = \ucfirst(\basename($scriptName, '.php'));

        $this->analyticsClient->trackPageView(
            $clientId,
            $sessionId,
            $pageLocation,
            $pageTitle,
        );
    }
}
