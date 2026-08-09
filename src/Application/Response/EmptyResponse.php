<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * Repräsentiert eine HTTP-Antwort ohne Payload (z.B. für 204 No Content).
 */
final readonly class EmptyResponse implements ResponseInterface
{
    public function __construct(public int $status = 204)
    {
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    public function send(): void
    {
        \http_response_code($this->status);
        exit;
    }
}
