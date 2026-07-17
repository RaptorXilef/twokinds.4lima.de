<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveSingleCharacterRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Entity\Character;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_save_single_character')]
final readonly class ApiSaveSingleCharacterAction implements ActionInterface
{
    public function __construct(private CharacterService $characterService)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SaveSingleCharacterRequest::fromRequest($request);

            $charIdStr = $dto->id;

            // Wenn der Client "new" sendet, generieren wir eine einzigartige ID.
            if ($charIdStr === 'new') {
                // In einer echten Umgebung idealerweise über eine Factory oder DB Sequence,
                // hier zur Sicherheit ein zufälliger Suffix nach dem Format 'char_XXXX'
                $charIdStr = 'char_' . \str_pad((string) \random_int(1, 9999), 4, '0', \STR_PAD_LEFT);
            }

            $character = new Character(
                id: new CharacterId($charIdStr),
                name: $dto->name,
                picUrl: $dto->picUrl,
                description: $dto->description,
            );

            $this->characterService->saveCharacter($character);

            return JsonResponse::success([
                'message'      => "Charakter '{$dto->name}' erfolgreich gespeichert.",
                'character_id' => $charIdStr,
            ]);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
