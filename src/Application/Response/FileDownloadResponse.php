<?php

declare(strict_types=1);

namespace App\Application\Response;

use App\Application\Contracts\ResponseInterface;

/**
 * TODO DOCBLOCK
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class FileDownloadResponse implements ResponseInterface
{
    public function __construct(public string $content, public string $filename, public string $contentType)
    {
    }

    public function send(): void
    {
        \header('Content-Type: ' . $this->contentType);
        \header('Content-Disposition: attachment; filename="' . $this->filename . '"');
        echo $this->content;
        exit;
    }
}
