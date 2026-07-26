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
    public function __construct(
        public array $get = [],
        public array $post = [],
        public array $files = [],
        public array $server = [],
        public array $input = [], // Parsed JSON Body
    ) {
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getPath(): string
    {
        return $this->server['REQUEST_URI'] ?? '';
    }

    public function getContentType(): string
    {
        return $this->server['CONTENT_TYPE'] ?? '';
    }

    public function getHeader(string $name): string
    {
        $key = 'HTTP_' . \strtoupper(\str_replace('-', '_', $name));

        return $this->server[$key] ?? '';
    }

    public function getIp(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (! empty($this->server[$k])) {
                $ips = \explode(',', $this->server[$k]);

                return \trim($ips[0]);
            }
        }

        return 'unknown';
    }

    public function withInput(array $input): self
    {
        return new self($this->get, $this->post, $this->files, $this->server, $input);
    }
}
