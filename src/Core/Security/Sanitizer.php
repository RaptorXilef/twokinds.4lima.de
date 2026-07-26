<?php

declare(strict_types=1);

namespace App\Core\Security;

final class Sanitizer
{
    /**
     * Entfernt alle HTML-Tags und unsichtbare Leerzeichen. (Für Namen, IDs, einfache Texte)
     */
    public static function string(mixed $input): string
    {
        return \trim(\strip_tags((string) $input));
    }

    /**
     * Bereinigt E-Mail-Adressen von ungültigen Zeichen.
     */
    public static function email(mixed $input): string
    {
        return \filter_var(\trim((string) $input), \FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Erlaubt Formatierungen für WYSIWYG-Editoren, blockiert aber <script>, <iframe> etc.
     */
    public static function html(mixed $input): string
    {
        $allowedTags = [
            'p', 'br', 'b', 'i', 'strong', 'em', 's', 'del', 'u',
            'a', 'img', 'ul', 'ol', 'li', 'span', 'div',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
        ];

        return \trim(\strip_tags((string) $input, $allowedTags));
    }
}
