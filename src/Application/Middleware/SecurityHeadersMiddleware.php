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
            // Verhindert das Caching der HTML-Seite durch den Browser. Zwingend nötig für korrekte
            // CSRF-Tokens und damit der Browser immer die neusten ?v= Datei-Versionen für CSS/JS lädt!
            \header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            \header('Pragma: no-cache');
            \header('Expires: 0');

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

            // Host für lokale Whitelist prüfen
            $nonce   = $_SESSION['csrf_token'] ?? \bin2hex(\random_bytes(16));
            $host    = $request->server['HTTP_HOST'] ?? '';
            $isLocal = \str_ends_with($host, '.local')
                || $host === 'localhost'
                || $host === '127.0.0.1'
                || \php_sapi_name() === 'cli';

            // Hochlesbare CSP Definition (Konsolidiert aus Public & Admin)
            $csp = [
                'default-src' => [
                    "'self'",
                    'https://cdnjs.cloudflare.com',
                ],
                'upgrade-insecure-requests' => [],
                'script-src'                => [
                    "'self'",
                    "'nonce-{$nonce}'",
                    'https://cdn.jsdelivr.net',
                    'https://cdn.twokinds.keenspot.com',
                    'https://cdnjs.cloudflare.com',
                    'https://code.jquery.com',
                    'https://placehold.co',
                    'https://twokinds.4lima.de',
                    'https://www.googletagmanager.com',
                ],
                'style-src' => [
                    "'self'",
                    "'unsafe-inline'",
                    'https://cdn.jsdelivr.net',
                    'https://cdn.twokinds.keenspot.com',
                    'https://cdnjs.cloudflare.com',
                    'https://code.jquery.com',
                    'https://fonts.googleapis.com',
                    'https://twokinds.4lima.de',
                ],
                'font-src' => [
                    "'self'",
                    'data:',
                    'https://cdn.jsdelivr.net',
                    'https://cdn.twokinds.keenspot.com',
                    'https://cdnjs.cloudflare.com',
                    'https://fonts.gstatic.com',
                    'https://twokinds.4lima.de',
                ],
                'img-src' => [
                    "'self'",
                    'data:',
                    'blob:',
                    'https://cdn.twokinds.keenspot.com',
                    'https://i.creativecommons.org',
                    'https://licensebuttons.net',
                    'https://placehold.co',
                    'https://placehold.co/',
                    'https://twokinds.4lima.de',
                    'https://twokindscomic.com',
                    'https://www.2kinds.com',
                    'https://www.google-analytics.com',
                    'https://www.googletagmanager.com',
                ],
                'connect-src' => [
                    "'self'",
                    'https://*.google-analytics.com',
                    'https://cdn.jsdelivr.net',
                    'https://cdn.twokinds.keenspot.com',
                    'https://cdnjs.cloudflare.com',
                    'https://region1.google-analytics.com',
                    'https://twokindscomic.com',
                ],
                'object-src' => [
                    "'none'",
                ],
                'frame-ancestors' => [
                    "'self'",
                ],
                'base-uri' => [
                    "'self'",
                ],
                'form-action' => [
                    "'self'",
                ],
            ];

            // Lokale Dev-Umgebungen dynamisch zu den Arrays hinzufügen
            if ($isLocal) {
                $localHosts = ['https://twokinds.4lima.local', 'http://localhost'];
                foreach (['default-src', 'script-src', 'style-src', 'font-src', 'img-src', 'connect-src', 'frame-ancestors', 'base-uri', 'form-action'] as $directive) {
                    $csp[$directive] = \array_merge($csp[$directive], $localHosts);
                }
            }

            // CSP Array zu einem sauberen String kompilieren
            $cspHeader = '';
            foreach ($csp as $directive => $sources) {
                $sourceString = $sources === [] ? '' : ' ' . \implode(' ', $sources);
                $cspHeader .= $directive . $sourceString . '; ';
            }

            \header('Content-Security-Policy: ' . \trim($cspHeader));

            // HSTS nur erzwingen, wenn wir NICHT in der lokalen Entwicklung sind
            if (! $isLocal) {
                \header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        return $next($request);
    }
}
