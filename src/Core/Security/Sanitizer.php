<?php

declare(strict_types=1);

namespace App\Core\Security;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

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
        $inputStr = (string) $input;

        if (\trim($inputStr) === '') {
            return '';
        }

        $config = (new HtmlSanitizerConfig())
            // Erlaubt grundlegende sichere Elemente (p, br, b, i, strong, em, div, span, h1-h6, ul, li...)
            ->allowSafeElements()
            // Links absichern: Nur http/https erlauben, target und rel für externe Links zulassen
            ->allowElement('a', ['href', 'title', 'target', 'rel'])
            // Bilder absichern: Nur saubere Quellen und Layout-Attribute zulassen
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
            // Erlaubt Klassen und Inline-Styles, die oft vom WYSIWYG-Editor genutzt werden (z.B. für Textausrichtung).
            // Der Sanitizer blockiert dabei automatisch bösartige CSS-Ausdrücke.
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*');

        $sanitizer = new HtmlSanitizer($config);

        return \trim($sanitizer->sanitize($inputStr));
    }
}
