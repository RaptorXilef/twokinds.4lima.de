<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;

/**
 * Global Security Headers.
 * Implementiert Zero-Trust CSP, HSTS und Permissions-Policies zum Schutz vor XSS und Clickjacking.
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
            // Basis Security Header
            \header('X-Frame-Options: SAMEORIGIN');
            \header('X-Content-Type-Options: nosniff');
            \header('X-XSS-Protection: 1; mode=block');
            \header('Referrer-Policy: strict-origin-when-cross-origin');
            \header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

            // Session starten/auslesen für den Nonce (CSRF Token)
            if (\session_status() === \PHP_SESSION_NONE) {
                \session_start();
            }
            $nonce = $_SESSION['csrf_token'] ?? \bin2hex(\random_bytes(16));

            // Host für lokale Whitelist prüfen
            $host    = $request->server['HTTP_HOST'] ?? '';
            $isLocal = \str_ends_with($host, '.local')
                || $host === 'localhost'
                || $host === '127.0.0.1'
                || \php_sapi_name() === 'cli';

            // CSP Definition (Konsolidiert aus Public & Admin)
            $cspDirectives = [
                "default-src 'self'",
                'upgrade-insecure-requests',
                "script-src 'self' 'nonce-{$nonce}' https://code.jquery.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://www.googletagmanager.com https://placehold.co https://cdn.twokinds.keenspot.com",
                "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.twokinds.keenspot.com https://fonts.googleapis.com https://code.jquery.com",
                "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com https://cdn.twokinds.keenspot.com https://cdn.jsdelivr.net",
                "img-src 'self' data: https://placehold.co https://cdn.twokinds.keenspot.com https://twokindscomic.com https://www.2kinds.com https://i.creativecommons.org https://licensebuttons.net https://www.google-analytics.com https://www.googletagmanager.com",
                "connect-src 'self' https://cdn.twokinds.keenspot.com https://region1.google-analytics.com https://*.google-analytics.com https://twokindscomic.com https://cdn.jsdelivr.net",
                "object-src 'none'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ];

            // Wenn wir lokal sind, fügen wir die lokalen Hosts zur Sicherheit hinzu
            if ($isLocal) {
                $cspDirectives = \str_replace(
                    "'self'",
                    "'self' https://twokinds.4lima.local http://localhost",
                    $cspDirectives,
                );
            }

            \header('Content-Security-Policy: ' . \implode('; ', $cspDirectives));

            // HSTS nur erzwingen, wenn wir NICHT in der lokalen Entwicklung sind
            if (! $isLocal) {
                \header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        return $next($request);
    }
}
