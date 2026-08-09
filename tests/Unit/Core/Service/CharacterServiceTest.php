<?php

declare(strict_types=1);

use App\Contracts\Storage\CharacterGroupRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Core\Entity\Character;
use App\Core\Entity\CharacterGroup;
use App\Core\Exception\EntityNotFoundException;
use App\Core\Service\CharacterService;
use App\Core\ValueObject\CharacterId;
use PHPUnit\Framework\MockObject\MockObject;

function setupCharacterTest(mixed $test): object
{
    $mock = \Closure::bind(fn (string $c) => $test->createMock($c), $test, $test::class);

    return new class (
        $mock(CharacterRepositoryInterface::class),
        $mock(CharacterGroupRepositoryInterface::class),
        $mock(SiteGeneratorInterface::class),
    ) {
        public CharacterService $service;

        public function __construct(
            public MockObject&CharacterRepositoryInterface $charRepo,
            public MockObject&CharacterGroupRepositoryInterface $groupRepo,
            public MockObject&SiteGeneratorInterface $siteGen,
        ) {
            $this->service = new CharacterService($this->charRepo, $this->groupRepo, $this->siteGen);
        }
    };
}

\it('saves a character and triggers site generation', function (): void {
    $app = \setupCharacterTest($this);

    $charId = new CharacterId('char_1234');
    $character = new Character($charId, 'TestChar', null, null);

    // Explizite Deklaration, dass das GroupRepo in diesem Test nicht aufgerufen wird (behebt PHPUnit Notice)
    $app->groupRepo->expects($this->never())->method('findAll');

    $app->charRepo->expects($this->once())
        ->method('save')
        ->with($character);

    $app->siteGen->expects($this->once())
        ->method('generateAll');

    $app->service->saveCharacter($character);
})->covers(CharacterService::class);

\it('deletes a character and removes it from all existing groups', function (): void {
    $app = \setupCharacterTest($this);

    $charIdToDelete = new CharacterId('char_0001');
    $otherCharId = new CharacterId('char_0002');

    $group1 = new CharacterGroup('Group1', [$charIdToDelete, $otherCharId]);
    $group2 = new CharacterGroup('Group2', [$otherCharId]); // Betrifft diesen Charakter nicht

    $app->charRepo->expects($this->once())
        ->method('delete')
        ->with($charIdToDelete);

    $app->groupRepo->expects($this->once())
        ->method('findAll')
        ->willReturn([$group1, $group2]);

    // Group1 muss gespeichert werden, da char_0001 entfernt wird. Group2 bleibt unberührt.
    $app->groupRepo->expects($this->once())
        ->method('save')
        ->with($this->callback(fn (CharacterGroup $savedGroup): bool => $savedGroup->name === 'Group1'
                && \count($savedGroup->characterIds) === 1
                && $savedGroup->characterIds[0]->value === $otherCharId->value));

    $app->siteGen->expects($this->once())
        ->method('generateAll');

    $app->service->deleteCharacter($charIdToDelete);
})->covers(CharacterService::class);

\it('throws EntityNotFoundException if adding non-existent character to a group', function (): void {
    $app = \setupCharacterTest($this);

    $charId1 = new CharacterId('char_1111');
    $charId2 = new CharacterId('char_9999'); // Existiert nicht

    $group = new CharacterGroup('Heroes', [$charId1, $charId2]);

    // Explizit unterdrücken
    $app->siteGen->expects($this->never())->method('generateAll');

    $app->charRepo->expects($this->exactly(2))
        ->method('findById')
        ->willReturnMap([
            [$charId1, new Character($charId1, 'Hero 1', null, null)],
            [$charId2, null], // Zweiter Aufruf liefert null (nicht gefunden)
        ]);

    $app->groupRepo->expects($this->never())->method('save');

    $app->service->saveGroup($group);
})->throws(EntityNotFoundException::class)->covers(CharacterService::class);

\it('successfully saves a group if all characters exist', function (): void {
    $app = \setupCharacterTest($this);

    // Explizit unterdrücken
    $app->siteGen->expects($this->never())->method('generateAll');

    $charId = new CharacterId('char_1111');
    $group = new CharacterGroup('Heroes', [$charId]);

    $app->charRepo->expects($this->once())
        ->method('findById')
        ->willReturn(new Character($charId, 'Hero 1', null, null));

    $app->groupRepo->expects($this->once())
        ->method('save')
        ->with($group);

    $app->service->saveGroup($group);
})->covers(CharacterService::class);
