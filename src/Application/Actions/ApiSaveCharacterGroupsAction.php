<?php
declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Core\Entity\CharacterGroup;
use App\Core\ValueObject\CharacterId;

#[ActionRoute('api_save_character_groups')]
final readonly class ApiSaveCharacterGroupsAction implements ActionInterface
{
    public function __construct(private CharacterGroupRepositoryInterface $groupRepo) {}

    public function execute(ServerRequest $request): mixed
    {
        try {
            $jsonData = $request->post['groups_data'] ?? '[]';
            $inputGroups = \json_decode($jsonData, true, 512, \JSON_THROW_ON_ERROR);

            $existingGroups = $this->groupRepo->findAll();
            $existingNames = \array_map(fn($g) => $g->name, $existingGroups);
            $newNames = [];

            // 1. Alle reinkommenden Gruppen speichern/updaten
            foreach ($inputGroups as $groupData) {
                $name = \trim($groupData['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $newNames[] = $name;

                $charIds = [];
                foreach ($groupData['characters'] ?? [] as $cid) {
                    try {
                        $charIds[] = new CharacterId($cid);
                    } catch (\InvalidArgumentException) {}
                }

                $this->groupRepo->save(new CharacterGroup($name, $charIds));
            }

            // 2. Gruppen löschen, die der User im Frontend entfernt hat
            $toDelete = \array_diff($existingNames, $newNames);
            foreach ($toDelete as $delName) {
                $this->groupRepo->delete($delName);
            }

            return JsonResponse::success(['message' => 'Gruppen-Sortierung erfolgreich gespeichert.']);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Speichern der Gruppen: ' . $e->getMessage(), 500);
        }
    }
}
