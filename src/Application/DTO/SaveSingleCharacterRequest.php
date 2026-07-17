<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class SaveSingleCharacterRequest
{
    private function __construct(
        public string $id,
        public string $name,
        public ?string $picUrl,
        public ?string $description,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        $post = $request->post;

        $id = \trim((string) ($post['character_id'] ?? ''));
        if (! \preg_match('/^char_\d{4}$/', $id)) {
            // Falls es ein komplett neuer Charakter ist, generieren wir in der Action eine ID.
            // Erlauben wir hier das Schlüsselwort 'new' als Indikator.
            if ($id !== 'new') {
                throw ValidationException::withMessage('Ungültige oder fehlende Charakter-ID.');
            }
        }

        $name = \trim((string) ($post['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessage('Der Charakter-Name darf nicht leer sein.');
        }

        $picUrl      = \trim((string) ($post['pic_url'] ?? ''));
        $description = \trim((string) ($post['description'] ?? ''));

        return new self(
            id: $id,
            name: $name,
            picUrl: $picUrl === '' ? null : $picUrl,
            description: $description === '' ? null : $description,
        );
    }
}
