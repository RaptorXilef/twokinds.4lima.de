<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

final readonly class HtmlResponse implements ResponseInterface
{
    public function __construct(
        public string $html,
        public int $statusCode = 200,
    ) {
    }

    public function send(): void
    {
        \http_response_code($this->statusCode);
        \header('Content-Type: text/html; charset=utf-8');
        echo $this->html;

        if (\function_exists('fastcgi_finish_request')) {
            if (\session_status() === \PHP_SESSION_ACTIVE) {
                \session_write_close();
            }
            \fastcgi_finish_request();

            return;
        }
        exit;
    }
}
