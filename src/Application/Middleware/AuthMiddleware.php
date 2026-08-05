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
    public function __construct(private SessionManager $sessionManager, private ConfigInterface $config)
    {
    }

    public function process(ServerRequest $request, callable $next): mixed
    {
        if ($this->sessionManager->getUserId() === '') {
            $path = $request->getPath();

            if (\str_starts_with($path, '/api/')) {
                return JsonResponse::unauthorized('Sitzung abgelaufen. Bitte neu anmelden.');
            }
            if (\str_starts_with($path, '/admin')) {
                return new RedirectResponse(\rtrim($this->config->getBaseUrl(), '/') . '/admin/login');
            }

            return new RedirectResponse(\rtrim($this->config->getBaseUrl(), '/') . '/login');
        }

        return $next($request);
    }
}
