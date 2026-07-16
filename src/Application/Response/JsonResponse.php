<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * Standardisierte JSON-Antwort.
 * Kapselt die JSON-Codierung und HTTP-Statuscodes ab, ohne den PHP-Prozess hart zu beenden.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class JsonResponse implements ResponseInterface
{
    public function __construct(
        public array $data,
        public int $statusCode = 200,
    ) {
    }

    /**
     * Sendet eine generische JSON-Antwort und beendet den Request.
     */
    public function send(): void
    {
        \http_response_code($this->statusCode);
        \header('Content-Type: application/json; charset=utf-8');
        echo \json_encode(
            $this->data,
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT,
        );
        exit;
    }

    // TODO DOCBLOCK
    public static function sendPayload(array $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    /**
     * Sendet eine Erfolgsantwort (200 OK) und mischt das 'success' => true Flag bei.
     */
    public static function success(array $data = []): self
    {
        return self::sendPayload(\array_merge(['success' => true], $data), 200);
    }

    /**
     * Sendet eine Fehlerantwort (Standard: 400 Bad Request).
     */
    public static function error(string $message, int $statusCode = 400): self
    {
        return self::sendPayload(['success' => false, 'error' => $message], $statusCode);
    }

    /**
     * Standardisierter 401 Unauthorized Fehler.
     */
    public static function unauthorized(string $message = 'Unauthorized: Invalid Security Token'): self
    {
        return self::error($message, 401);
    }
}
