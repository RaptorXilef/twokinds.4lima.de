<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

use InvalidArgumentException;

final readonly class IpAddress
{
    public string $value;

    public function __construct(string $value)
    {
        $value = \trim($value);
        if ($value !== '0.0.0.0' && \filter_var($value, \FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('Ungültiges IP-Format');
        }
        $this->value = $value;
    }
}
