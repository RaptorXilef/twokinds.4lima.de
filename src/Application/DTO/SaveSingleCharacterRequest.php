<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;

final readonly class SaveSingleCharacterRequest
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $picUrl,
        public ?string $description,
        public ?string $fullName,
        public ?string $altNames,
        public ?string $gender,
        public ?string $age,
        public ?string $rank,
        public ?string $species,
        public ?string $subspecies,
        public ?string $languages,
    ) {
    }

    public static function fromRequest(ServerRequest $request): self
    {
        $data = $request->post;

        if (empty($data['name'])) {
            throw new ValidationException('Der Name des Charakters darf nicht leer sein.');
        }

        return new self(
            id: \trim((string) ($data['id'] ?? 'new')),
            name: \trim((string) $data['name']),
            picUrl: \trim((string) ($data['pic_url'] ?? '')) ?: null,
            description: \trim((string) ($data['description'] ?? '')) ?: null,
            fullName: \trim((string) ($data['full_name'] ?? '')) ?: null,
            altNames: \trim((string) ($data['alt_names'] ?? '')) ?: null,
            gender: \trim((string) ($data['gender'] ?? '')) ?: null,
            age: \trim((string) ($data['age'] ?? '')) ?: null,
            rank: \trim((string) ($data['rank'] ?? '')) ?: null,
            species: \trim((string) ($data['species'] ?? '')) ?: null,
            subspecies: \trim((string) ($data['subspecies'] ?? '')) ?: null,
            languages: \trim((string) ($data['languages'] ?? '')) ?: null,
        );
    }
}
