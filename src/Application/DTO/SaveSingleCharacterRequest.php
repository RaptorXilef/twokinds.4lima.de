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

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public static function fromRequest(ServerRequest $request): self
    {
        $data = $request->post;

        $name = Sanitizer::string($data['name'] ?? '');
        if ($name === '') {
            throw new ValidationException('Der Name des Charakters darf nicht leer sein.');
        }

        // phpcs:disable Generic.Files.LineLength.TooLong
        return new self(
            id: Sanitizer::string($data['id'] ?? 'new'),
            name: $name,
            picUrl: isset($data['pic_url']) && $data['pic_url'] !== '' ? Sanitizer::string($data['pic_url']) : null,
            description: isset($data['description']) && $data['description'] !== '' ? Sanitizer::html($data['description']) : null, // HTML erlaubt
            fullName: isset($data['full_name']) && $data['full_name'] !== '' ? Sanitizer::string($data['full_name']) : null,
            altNames: isset($data['alt_names']) && $data['alt_names'] !== '' ? Sanitizer::string($data['alt_names']) : null,
            gender: isset($data['gender']) && $data['gender'] !== '' ? Sanitizer::string($data['gender']) : null,
            age: isset($data['age']) && $data['age'] !== '' ? Sanitizer::string($data['age']) : null,
            rank: isset($data['rank']) && $data['rank'] !== '' ? Sanitizer::string($data['rank']) : null,
            species: isset($data['species']) && $data['species'] !== '' ? Sanitizer::string($data['species']) : null,
            subspecies: isset($data['subspecies']) && $data['subspecies'] !== '' ? Sanitizer::string($data['subspecies']) : null,
            languages: isset($data['languages']) && $data['languages'] !== '' ? Sanitizer::string($data['languages']) : null,
        );
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
