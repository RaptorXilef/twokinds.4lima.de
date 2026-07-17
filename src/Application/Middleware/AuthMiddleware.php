<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;

final readonly class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionManager $sessionManager,
        private ConfigInterface $config,
    ) {
    }

    public function process(ServerRequest $request, callable $next): mixed
    {
        // Prüfen, ob der User eine ID in der Session hat
        if ($this->sessionManager->getUserId() === '') {
            $path = $request->getPath();

            // Wenn es ein API-Aufruf ist, JSON-Fehler zurückgeben
            if (\str_contains($path, '/api/')) {
                return JsonResponse::unauthorized('Sitzung abgelaufen. Bitte neu anmelden.');
            }

            // Bei normalen Seitenaufrufen zurück zum Login leiten
            $baseUrl = \rtrim($this->config->getBaseUrl(), '/');

            return new RedirectResponse($baseUrl . '/admin/login');
        }

        return $next($request);
    }
}
