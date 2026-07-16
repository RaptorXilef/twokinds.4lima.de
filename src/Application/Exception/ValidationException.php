<?php

declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Wird geworfen, wenn die Formulardaten (DTO) ungültig sind.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class ValidationException extends \DomainException
{
    // TODO DOCBLOCK
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
