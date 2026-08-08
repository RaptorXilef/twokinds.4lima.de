<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class ReportId implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $value = \trim($value);
        if (!\str_starts_with($value, 'report_')) {
            throw new InvalidArgumentException("Ungültiges Report-ID Format. Erwartet 'report_' Präfix, erhalten: {$value}");
        }
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
