<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

final readonly class ComicId
{
    public string $value;

    public function __construct(string $value)
    {
        $value = \trim($value);
        if (! \preg_match('/^\d{8}$/', $value)) {
            throw new \InvalidArgumentException("Ungültiges Comic-ID Format. Erwartet YYYYMMDD, erhalten: {$value}");
        }
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
