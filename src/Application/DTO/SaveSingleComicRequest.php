<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class SaveSingleComicRequest
{
    private function __construct(
        public string $id,
        public string $type,
        public string $name,
        public string $transcript,
        public ?string $chapterId,
        public array $characterIds,
        public string $originalUrl,
        public string $sketchUrl,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        // Wir nehmen die Daten jetzt aus einem ganz normalen POST-Request (kein JSON-String mehr!)
        $post = $request->post;

        $id = \trim((string) ($post['comic_id'] ?? ''));
        if (! \preg_match('/^\d{8}$/', $id)) {
            throw ValidationException::withMessage('Ungültige oder fehlende Comic-ID.');
        }

        $type       = \trim((string) ($post['type'] ?? 'Comicseite'));
        $name       = \trim((string) ($post['name'] ?? ''));
        $transcript = \trim((string) ($post['transcript'] ?? ''));
        $chapterId  = \trim((string) ($post['chapter_id'] ?? ''));

        // Checkboxen oder Multi-Selects senden Arrays
        $characterIds = (array) ($post['character_ids'] ?? []);

        return new self(
            id: $id,
            type: $type,
            name: $name,
            transcript: $transcript,
            chapterId: $chapterId === '' ? null : $chapterId,
            characterIds: $characterIds,
            originalUrl: \trim((string) ($post['url_originalbild'] ?? '')),
            sketchUrl: \trim((string) ($post['url_originalsketch'] ?? '')),
        );
    }
}
