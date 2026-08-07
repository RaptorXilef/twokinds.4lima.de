<?php

declare(strict_types=1);

use App\Core\Security\Sanitizer;

\it('sanitizes strings by stripping HTML tags and trimming', function (): void {
    $input = "  <b>Hello</b> \n World! <script>alert('xss');</script>  ";
    // Strip_tags hinterlässt "Hello \n World! alert('xss');"
    $expected = "Hello \n World! alert('xss');";

    \expect(Sanitizer::string($input))->toBe($expected);
})->covers(Sanitizer::class);

\it('sanitizes emails correctly', function (): void {
    $input = '  TEST@example.com!  ';
    \expect(Sanitizer::email($input))->toBe('TEST@example.com');
})->covers(Sanitizer::class);

\it('sanitizes HTML securely via HtmlSanitizer', function (): void {
    $input     = '<a href="javascript:alert(1)">Link</a> <img src="x" onerror="alert(1)">';
    $sanitized = Sanitizer::html($input);

    // JS-Links und OnError-Attribute müssen weg sein!
    \expect($sanitized)->not->toContain('javascript:')
        ->and($sanitized)->not->toContain('onerror');
})->covers(Sanitizer::class);

\it('slugifies filenames correctly', function (): void {
    \expect(Sanitizer::slugify('Mein Tolles B!ld_2023.JPEG'))->toBe('mein-tolles-b-ld-2023.jpeg')
        ->and(Sanitizer::slugify('Übergröße_Wüste.png'))->toBe('uebergroesse-wueste.png');
})->covers(Sanitizer::class);
