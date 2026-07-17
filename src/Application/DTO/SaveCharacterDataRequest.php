<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class SaveCharacterDataRequest
{
    private function __construct(
        public array $characters,
        public array $groups,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        $json = $request->post['characterData'] ?? '';
        if ($json === '') {
            throw ValidationException::withMessage('Fehler: Keine Charakter-Daten empfangen.');
        }

        try {
            $data = \json_decode((string) $json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessage('Fehler: Ungültiges JSON-Format.');
        }

        $characters = $data['characters'] ?? [];
        $groups     = $data['groups'] ?? [];

        if (! \is_array($characters) || ! \is_array($groups)) {
            throw ValidationException::withMessage('Fehler: Ungültige Datenstruktur für Charaktere/Gruppen.');
        }

        return new self($characters, $groups);
    }
}
