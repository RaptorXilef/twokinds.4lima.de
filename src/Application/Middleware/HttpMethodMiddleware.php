<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;

/**
 * Middleware zur Validierung der zulässigen HTTP-Methoden.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class HttpMethodMiddleware implements MiddlewareInterface
{
    public function __construct(private array $allowedMethods = ['POST'])
    {
    }

    public function process(ServerRequest $request, callable $next): mixed
    {
        $method = $request->getMethod() ?? '';
        if (! \in_array($method, $this->allowedMethods, true)) {
            return JsonResponse::error('Methode nicht erlaubt.', 405);
        }

        return $next($request);
    }
}
