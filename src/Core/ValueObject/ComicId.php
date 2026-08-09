<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class ComicId implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $value = \trim($value);
        // NEU: Erlaubt 8 Ziffern PLUS optional einen Buchstaben (a-z) am Ende
        if (\preg_match('/^\d{8}[a-z]?$/i', $value) !== 1) {
            throw new InvalidArgumentException(
                "Ungültiges Comic-ID Format. Erwartet YYYYMMDD (mit optionalem Suffix a-z), erhalten: {$value}",
            );
        }

        $this->value = \strtolower($value); // Aus großem 'A' ein kleines 'a' machen
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
