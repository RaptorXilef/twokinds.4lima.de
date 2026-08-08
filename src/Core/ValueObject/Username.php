<?php

declare(strict_types=1);

namespace App\Core\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class Username implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        // 1. Whitespaces und HTML-Tags entfernen
        $val = \trim(\strip_tags($value));

        // 2. Längen-Check
        if (\strlen($val) < 3) {
            throw new InvalidArgumentException('Der Benutzername muss mindestens 3 Zeichen lang sein.');
        }
        if (\strlen($val) > 50) {
            throw new InvalidArgumentException('Der Benutzername darf maximal 50 Zeichen lang sein.');
        }

        // 3. SECURITY FIX: Erlaube nur lateinische Zeichen (inkl. europäischer Umlaute/Akzente), Zahlen und wenige Sonderzeichen.
        // Das 'u' am Ende steht für UTF-8-Sicherheit.
        // \p{Latin} erlaubt z.B. a-z, ä, ö, ü, é, è, å, ø
        // Es blockiert z.B. Kyrillisch, Chinesisch, Arabisch, Emojis und unsichtbare Steuerzeichen.
        if (\preg_match('/^[\p{Latin}0-9 \-_.]+$/u', $val) !== 1) {
            throw new InvalidArgumentException('Der Benutzername enthält ungültige Zeichen. Erlaubt sind nur Buchstaben, Zahlen, Leerzeichen, Binde- und Unterstriche sowie Punkte.');
        }

        $this->value = $val;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
