<?php
declare(strict_types=1);

namespace App\Core\ValueObject;

final readonly class Username
{
    public string $value;

    public function __construct(string $value)
    {
        $val = \trim(\strip_tags($value));
        if (\strlen($val) < 3) {
            throw new \InvalidArgumentException('Der Benutzername muss mindestens 3 Zeichen lang sein.');
        }
        if (\strlen($val) > 50) {
            throw new \InvalidArgumentException('Der Benutzername darf maximal 50 Zeichen lang sein.');
        }
        $this->value = $val;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
