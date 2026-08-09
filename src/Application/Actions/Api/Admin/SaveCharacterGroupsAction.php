<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Core\Entity\CharacterGroup;
use App\Core\Service\AuthService;
use App\Core\ValueObject\CharacterId;
use InvalidArgumentException;
use Throwable;

#[Route('POST', '/api/save_character_groups')]
#[RequiresAuth]
final readonly class SaveCharacterGroupsAction implements ActionInterface
{
    public function __construct(
        private CharacterGroupRepositoryInterface $groupRepo,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('groups.manage')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $jsonDataRaw = $request->post['groups_data'] ?? '[]';
            $jsonData = \is_string($jsonDataRaw) ? $jsonDataRaw : '[]';
            $inputGroups = \json_decode($jsonData, true, 512, \JSON_THROW_ON_ERROR);

            if (!\is_array($inputGroups)) {
                $inputGroups = [];
            }

            $existingGroups = $this->groupRepo->findAll();
            $existingNames = \array_map(fn (CharacterGroup $group): string => $group->name, $existingGroups);

            $newNames = $this->processGroups($inputGroups);

            // Gruppen löschen, die der User im Frontend entfernt hat
            $toDelete = \array_diff($existingNames, $newNames);
            foreach ($toDelete as $delName) {
                $this->groupRepo->delete($delName);
            }

            return JsonResponse::success(['message' => 'Gruppen und Sortierungen erfolgreich gespeichert.']);
        } catch (Throwable $e) {
            return JsonResponse::error('Fehler beim Speichern der Gruppen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int|string, mixed> $inputGroups
     *
     * @return array<int, string>
     */
    private function processGroups(array $inputGroups): array
    {
        $newNames = [];
        $sortOrder = 0; // Hochzählen für die Drag&Drop Reihenfolge

        // Alle reinkommenden Gruppen speichern/updaten
        foreach ($inputGroups as $groupData) {
            if (!\is_array($groupData)) {
                continue;
            }

            $name = \is_string($groupData['name'] ?? null) ? \trim($groupData['name']) : '';
            if ($name === '') {
                continue;
            }

            $newNames[] = $name;

            $msRaw = $groupData['manual_sort'] ?? false;
            $manualSort = \in_array($msRaw, [true, 1, '1', 'true', 'on'], true);

            $charIds = [];
            // Eindeutige Zuweisung, falls ein Charakter versehentlich doppelt reingezogen wurde
            $charsRaw = $groupData['characters'] ?? [];

            // Wir müssen garantieren, dass es ein Array von Strings ist, bevor wir unique aufrufen
            /** @var array<int, string> $stringChars */
            $stringChars = [];
            if (\is_array($charsRaw)) {
                foreach ($charsRaw as $cr) {
                    if (!\is_string($cr)) {
                        continue;
                    }

                    $stringChars[] = $cr;
                }
            }

            $uniqueChars = \array_unique($stringChars);

            foreach ($uniqueChars as $cid) {
                try {
                    $charIds[] = new CharacterId($cid);
                } catch (InvalidArgumentException) {
                    // Ignorieren: Fehlerhafte IDs überspringen wir stumm
                }
            }

            $this->groupRepo->save(new CharacterGroup($name, $charIds, $sortOrder++, $manualSort));
        }

        return $newNames;
    }
}
