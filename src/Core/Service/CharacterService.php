<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Core\Entity\Character;
use App\Core\Entity\CharacterGroup;
use App\Core\Exception\EntityNotFoundException;
use App\Core\ValueObject\CharacterId;

final readonly class CharacterService
{
    public function __construct(
        private CharacterRepositoryInterface $characterRepo,
        private CharacterGroupRepositoryInterface $groupRepo,
        private SiteGeneratorInterface $siteGenerator,
    ) {
    }

    public function saveCharacter(Character $character): void
    {
        $this->characterRepo->save($character);

        // Sitemap sofort aktualisieren
        $this->siteGenerator->generateAll();
    }

    public function deleteCharacter(CharacterId $id): void
    {
        // 1. Charakter löschen
        $this->characterRepo->delete($id);

        // 2. Referenzielle Integrität: Charakter aus allen Gruppen entfernen
        $groups = $this->groupRepo->findAll();
        foreach ($groups as $group) {
            $updatedIds = \array_filter(
                $group->characterIds,
                fn (CharacterId $charId): bool => $charId->value !== $id->value,
            );

            // Nur speichern, wenn sich etwas geändert hat
            if (\count($updatedIds) === \count($group->characterIds)) {
                continue;
            }

            $this->groupRepo->save(new CharacterGroup($group->name, $updatedIds));
        }

        // Sitemap sofort aktualisieren
        $this->siteGenerator->generateAll();
    }

    public function saveGroup(CharacterGroup $group): void
    {
        // Validierung: Existieren alle zugewiesenen Charaktere wirklich?
        foreach ($group->characterIds as $charId) {
            if (! $this->characterRepo->findById($charId) instanceof Character) {
                throw new EntityNotFoundException("Charakter mit ID {$charId->value} existiert nicht und kann nicht der Gruppe hinzugefügt werden.");
            }
        }
        $this->groupRepo->save($group);
    }

    public function deleteGroup(string $name): void
    {
        $this->groupRepo->delete($name);
    }
}
