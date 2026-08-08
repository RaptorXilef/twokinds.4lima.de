<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class ComicViewRequest
{
    private function __construct(public string $comicId)
    {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        // Wir unterstützen ?id=20251222 oder saubere URLs (wenn der Front-Controller sie in $_GET['id'] mappt)
        $idRaw = $request->get['id'] ?? '';
        $id    = \is_string($idRaw) ? \trim($idRaw) : '';

        if ($id === '') {
            throw ValidationException::withMessage('Keine Comic-ID angegeben.');
        }

        return new self($id);
    }
}
