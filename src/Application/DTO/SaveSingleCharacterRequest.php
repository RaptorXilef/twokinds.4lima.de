<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Core\Security\Sanitizer;

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
            id: Sanitizer::string($data['id'] ?? 'new'),
            name: Sanitizer::string($data['name']),
            picUrl: Sanitizer::string($data['pic_url'] ?? '') ?: null,
            description: Sanitizer::html($data['description'] ?? '') ?: null, // HTML erlaubt
            fullName: Sanitizer::string($data['full_name'] ?? '') ?: null,
            altNames: Sanitizer::string($data['alt_names'] ?? '') ?: null,
            gender: Sanitizer::string($data['gender'] ?? '') ?: null,
            age: Sanitizer::string($data['age'] ?? '') ?: null,
            rank: Sanitizer::string($data['rank'] ?? '') ?: null,
            species: Sanitizer::string($data['species'] ?? '') ?: null,
            subspecies: Sanitizer::string($data['subspecies'] ?? '') ?: null,
            languages: Sanitizer::string($data['languages'] ?? '') ?: null,
        );
    }
}
