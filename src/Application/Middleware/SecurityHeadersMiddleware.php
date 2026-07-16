<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;

/**
 * TODO DOCBLOCK
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequest $request,
        callable $next,
    ): mixed {
        if (! \headers_sent()) {
            \header('X-Frame-Options: SAMEORIGIN');
            \header('X-Content-Type-Options: nosniff');
            \header('X-XSS-Protection: 1; mode=block');
            \header('Referrer-Policy: strict-origin-when-cross-origin');
            $cspDirectives = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.paypal.com https://www.sandbox.paypal.com https://www.googletagmanager.com",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https://api.qrserver.com https://www.google-analytics.com https://www.paypalobjects.com",
                "connect-src 'self' https://www.google-analytics.com https://www.paypal.com https://www.sandbox.paypal.com",
                "frame-src 'self' https://www.paypal.com https://www.sandbox.paypal.com",
            ];
            // Verbindet die Zeilen mit "; " und sendet den Header
            \header('Content-Security-Policy: ' . \implode('; ', $cspDirectives) . ';');

            $host    = $request->server['HTTP_HOST'] ?? '';
            $isLocal = \str_ends_with($host, '.local')
                || $host === 'localhost'
                || $host === '127.0.0.1'
                || \php_sapi_name() === 'cli';

            if (! $isLocal) {
                \header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        return $next($request);
    }
}
