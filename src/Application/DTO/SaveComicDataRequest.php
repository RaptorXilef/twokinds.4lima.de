<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class SaveComicDataRequest
{
    private function __construct(public array $comics)
    {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        $json = $request->post['comics'] ?? '';
        if ($json === '') {
            throw ValidationException::withMessage('Fehler: Keine Comic-Daten empfangen.');
        }

        try {
            $data = \json_decode((string) $json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessage('Fehler: Ungültiges JSON-Format.');
        }

        if (! \is_array($data)) {
            throw ValidationException::withMessage('Fehler: Erwartetes Format ist ein Array/Objekt.');
        }

        return new self($data);
    }
}
