<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

final readonly class EmailAddress implements \Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $value = \trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('E-Mail-Adresse darf nicht leer sein.');
        }
        if (\filter_var($value, \FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException("Ungültiges E-Mail-Format: {$value}");
        }
        $this->value = \strtolower($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
