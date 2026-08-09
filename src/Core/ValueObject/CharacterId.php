<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class CharacterId implements Stringable
{
    public function __construct(public string $value)
    {
        // Wir erlauben nun 'char_' gefolgt von BELIEBIG vielen Ziffern (\d+)
        if (\preg_match('/^char_\d+$/', $value) !== 1) {
            throw new InvalidArgumentException(
                "Ungültiges Character-ID Format. Erwartet char_XXXX, erhalten: {$value}",
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
