<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * Repräsentiert eine Dateidownload-Antwort.
 * Setzt entsprechende HTTP-Header, um den Browser zum Speichern der Datei aufzufordern.
 */
final readonly class FileDownloadResponse implements ResponseInterface
{
    public function __construct(public string $content, public string $filename, public string $contentType)
    {
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    public function send(): void
    {
        \header('Content-Type: ' . $this->contentType);
        \header('Content-Disposition: attachment; filename="' . $this->filename . '"');
        echo $this->content;
        exit;
    }
}
