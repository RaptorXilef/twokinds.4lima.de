<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * TODO DOCBLOCK
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class EmptyResponse implements ResponseInterface
{
    public function __construct(public int $status = 204)
    {
    }

    public function send(): void
    {
        \http_response_code($this->status);
        exit;
    }
}
