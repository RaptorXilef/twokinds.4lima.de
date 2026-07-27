<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * Repräsentiert eine HTTP-Weiterleitung.
 * Kapselt header() und exit() aus den Actions heraus, um Testbarkeit zu gewährleisten.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class RedirectResponse implements ResponseInterface
{
    public function __construct(public string $url, public int $statusCode = 302)
    {
    }

    public function send(): void
    {
        \http_response_code($this->statusCode);
        \header('Location: ' . $this->url);
        exit;
    }
}
