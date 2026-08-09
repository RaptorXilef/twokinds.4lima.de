<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Storage;

use App\Infrastructure\Storage\JsonHelper;
use RuntimeException;

\uses()->group('infrastructure', 'storage');

\it('decodes valid standard JSON correctly', function (): void {
    $helper = new JsonHelper();
    $json = '{"name":"Trace", "species":"Human"}';

    $result = $helper->decode($json);

    \expect($result)->toBe(['name' => 'Trace', 'species' => 'Human']);
})->covers(JsonHelper::class);

\it('strips single-line and multi-line comments from JSONC before decoding', function (): void {
    $helper = new JsonHelper();
    $jsonc = '{
        // Single line comment
        "role": "Templar",
        /* Multi
           Line
           Comment */
        "magic": true
    }';

    $result = $helper->decode($jsonc);

    \expect($result)->toBe(['role' => 'Templar', 'magic' => true]);
})->covers(JsonHelper::class);

\it('returns an empty array on empty string input', function (): void {
    $helper = new JsonHelper();

    \expect($helper->decode('   '))->toBe([]);
})->covers(JsonHelper::class);

\it('throws RuntimeException on invalid JSON formatting', function (): void {
    $helper = new JsonHelper();
    $invalidJson = '{"name": "Trace",}'; // trailing comma causes error in strict JSON

    $helper->decode($invalidJson);
})->throws(RuntimeException::class, 'JSON-Datenstruktur ist korrupt')->covers(JsonHelper::class);
