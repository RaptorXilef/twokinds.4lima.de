<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;

/**
 * Liest sichere JSON-Bodys asynchroner Anfragen aus und mappt sie in den Request.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequest $request, callable $next): mixed
    {
        $method      = $request->getMethod() ?? '';
        $contentType = $request->getContentType() ?? '';

        if (\in_array($method, ['POST', 'PUT', 'PATCH'], true) && \str_contains($contentType, 'application/json')) {
            $raw = \file_get_contents('php://input');
            if ($raw !== '' && $raw !== false) {
                try {
                    $request = $request->withInput(\json_decode($raw, true, 512, \JSON_THROW_ON_ERROR));
                } catch (\JsonException) {
                    return JsonResponse::error('Bad Request: Ungültiges JSON-Format gesendet.', 400);
                }
            }
        }

        return $next($request);
    }
}
