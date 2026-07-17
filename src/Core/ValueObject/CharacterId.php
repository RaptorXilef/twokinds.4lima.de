<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

final readonly class CharacterId
{
    public string $value;

    public function __construct(string $value)
    {
        $value = \trim($value);
        if (! \preg_match('/^char_\d{4}$/', $value)) {
            throw new \InvalidArgumentException("Ungültiges Character-ID Format. Erwartet char_XXXX, erhalten: {$value}");
        }
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
