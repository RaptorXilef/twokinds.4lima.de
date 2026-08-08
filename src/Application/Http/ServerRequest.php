<?php

declare(strict_types=1);

namespace App\Application\Http;

/**
 * Kapselt den gesamten HTTP-Request in ein typsicheres, objektorientiertes Format (PSR-7 inspiriert).
 * Ersetzt den direkten Zugriff auf Superglobals ($_POST, $_GET, $_SERVER, $_FILES).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class ServerRequest
{
    /**
     * @param array<string, mixed> $get
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     * @param array<string, mixed> $input
     * @param array<string, mixed> $cookie
     */
    public function __construct(
        public array $get = [],
        public array $post = [],
        public array $files = [],
        public array $server = [],
        public array $input = [], // Parsed JSON Body
        public array $cookie = [],
    ) {
    }

    public function getMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        return \is_string($method) ? $method : 'GET';
    }

    public function getPath(): string
    {
        $path = $this->server['REQUEST_URI'] ?? '';

        return \is_string($path) ? $path : '';
    }

    public function getContentType(): string
    {
        $type = $this->server['CONTENT_TYPE'] ?? '';

        return \is_string($type) ? $type : '';
    }

    public function getHeader(string $name): string
    {
        $key = 'HTTP_' . \strtoupper(\str_replace('-', '_', $name));
        $header = $this->server[$key] ?? '';

        return \is_string($header) ? $header : '';
    }

    public function getIp(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (isset($this->server[$k]) && \is_string($this->server[$k]) && $this->server[$k] !== '') {
                $ips = \explode(',', $this->server[$k]);

                return \trim($ips[0]);
            }
        }

        return 'unknown';
    }

    /**
     * Methode anpassen für Fluid-Interface:
     *
     * @param array<string, mixed> $input
     */
    public function withInput(array $input): self
    {
        return new self($this->get, $this->post, $this->files, $this->server, $input, $this->cookie);
    }
}
