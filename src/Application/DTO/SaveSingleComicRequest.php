<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Core\Security\Sanitizer;

final readonly class SaveSingleComicRequest
{
    /**
     * @param array<int, string> $characterIds
     * @param array<int, string> $userIds
     */
    private function __construct(
        public string $id,
        public string $type,
        public string $name,
        public string $transcript,
        public ?string $chapterId,
        public array $characterIds,
        public array $userIds,
        public string $originalUrl,
        public string $sketchUrl,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        // Wir nehmen die Daten jetzt aus einem ganz normalen POST-Request (kein JSON-String mehr!)
        $post = $request->post;

        $id = Sanitizer::string($post['comic_id'] ?? '');
        if (\preg_match('/^\d{8}$/', $id) !== 1) {
            throw ValidationException::withMessage('Ungültige oder fehlende Comic-ID.');
        }

        $type       = Sanitizer::string($post['type'] ?? 'Comicseite');
        $name       = Sanitizer::string($post['name'] ?? '');
        $transcript = Sanitizer::html($post['transcript'] ?? ''); // HTML erlaubt!
        $chapterId  = Sanitizer::string($post['chapter_id'] ?? '');

        // Checkboxen oder Multi-Selects senden Arrays
        $characterIdsRaw = $post['character_ids'] ?? [];
        $characterIdsArr = \is_array($characterIdsRaw) ? $characterIdsRaw : [];
        // Char-IDs säubern
        /** @var array<int, string> $characterIds */
        $characterIds = \array_map(Sanitizer::string(...), $characterIdsArr);

        $userIdsRaw = $post['user_ids'] ?? [];
        $userIdsArr = \is_array($userIdsRaw) ? $userIdsRaw : [];
        /** @var array<int, string> $userIds */
        $userIds = \array_map(Sanitizer::string(...), $userIdsArr);

        // Flexible URL-Behandlung für Originalbilder
        $originalUrl = Sanitizer::string($post['url_originalbild'] ?? '');
        if ($originalUrl !== '' && ! \str_starts_with($originalUrl, 'http')) {
            $originalUrl = 'https://cdn.twokinds.keenspot.com/comics/' . $originalUrl; // TODO ggf. URL in Config auslagern
        }

        // Flexible URL-Behandlung für Skizzen
        $sketchUrl = Sanitizer::string($post['url_originalsketch'] ?? '');
        if ($sketchUrl !== '' && ! \str_starts_with($sketchUrl, 'http')) {
            $sketchUrl = 'https://twokindscomic.com/images/' . $sketchUrl; // TODO ggf. URL in Config auslagern
        }

        return new self(
            id: $id,
            type: $type,
            name: $name,
            transcript: $transcript,
            chapterId: $chapterId === '' ? null : $chapterId,
            characterIds: $characterIds,
            userIds: $userIds,
            originalUrl: $originalUrl,
            sketchUrl: $sketchUrl,
        );
    }
}
