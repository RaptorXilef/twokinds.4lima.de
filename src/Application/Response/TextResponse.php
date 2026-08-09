<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * Repräsentiert eine reine Text-Antwort.
 * Kapselt header() und exit() aus den Actions heraus, um den Webserver-Output sauber zu steuern.
 */
final readonly class TextResponse implements ResponseInterface
{
    public function __construct(public string $content, public int $status = 200)
    {
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    public function send(): void
    {
        \http_response_code($this->status);
        \header('Content-Type: text/plain; charset=utf-8');
        echo $this->content;
        exit;
    }
}
