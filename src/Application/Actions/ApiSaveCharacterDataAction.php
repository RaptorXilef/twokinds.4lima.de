<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveCharacterDataRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Core\Entity\Character;
use App\Core\Entity\CharacterGroup;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_save_character_data')]
final readonly class ApiSaveCharacterDataAction implements ActionInterface
{
    public function __construct(
        private CharacterService $characterService,
        private CharacterRepositoryInterface $charRepo,
        private CharacterGroupRepositoryInterface $groupRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SaveCharacterDataRequest::fromRequest($request);

            // 1. Löschen nicht mehr vorhandener Charaktere
            $existingChars   = $this->charRepo->findAll();
            $incomingCharIds = \array_keys($dto->characters);
            foreach ($existingChars as $existing) {
                if (! \in_array($existing->id->value, $incomingCharIds, true)) {
                    $this->characterService->deleteCharacter($existing->id);
                }
            }

            // 2. Löschen nicht mehr vorhandener Gruppen
            $existingGroups     = $this->groupRepo->findAll();
            $incomingGroupNames = \array_keys($dto->groups);
            foreach ($existingGroups as $existingGroup) {
                if (! \in_array($existingGroup->name, $incomingGroupNames, true)) {
                    $this->characterService->deleteGroup($existingGroup->name);
                }
            }

            // 3. Speichern / Aktualisieren der Charaktere
            foreach ($dto->characters as $id => $cData) {
                $char = new Character(
                    id: new CharacterId((string) $id),
                    name: $cData['name'] ?? 'Unbekannt',
                    picUrl: (isset($cData['pic_url']) && $cData['pic_url'] !== '') ? $cData['pic_url'] : null,
                    description: (isset($cData['description']) && $cData['description'] !== '') ? $cData['description'] : null,
                );
                $this->characterService->saveCharacter($char);
            }

            // 4. Speichern / Aktualisieren der Gruppen
            foreach ($dto->groups as $name => $charIdsArray) {
                $validCharIds = [];
                foreach ($charIdsArray as $cId) {
                    try {
                        $validCharIds[] = new CharacterId((string) $cId);
                    } catch (\InvalidArgumentException) {
                        continue;
                    }
                }

                $group = new CharacterGroup((string) $name, $validCharIds);
                $this->characterService->saveGroup($group);
            }

            return JsonResponse::success(['message' => 'Charakter-Daten erfolgreich gespeichert.']);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
