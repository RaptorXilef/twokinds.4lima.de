<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;

/**
 * Reiht Middlewares aneinander und führt sie sequenziell aus.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class MiddlewarePipeline
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Schickt den Request durch alle Middlewares bis zur Kern-Aktion.
     */
    public function process(ServerRequest $request, callable $coreAction): mixed
    {
        $next = $coreAction;

        // Wir bauen die Pipeline von hinten nach vorne auf (Onion-Architektur)
        for ($i = \count($this->middlewares) - 1; $i >= 0; --$i) {
            $middleware = $this->middlewares[$i];

            $next = function (ServerRequest $req) use ($middleware, $next): mixed {
                return $middleware->process($req, $next);
            };
        }

        // Startschuss: Der Request betritt die äußerste Schicht
        return $next($request);
    }
}
