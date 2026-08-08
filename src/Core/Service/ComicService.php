<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Exception\EntityNotFoundException;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;
use DomainException;
use InvalidArgumentException;

final readonly class ComicService
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private ComicRevisionRepositoryInterface $revisionRepository,
        private ClockInterface $clock,
        private SiteGeneratorInterface $siteGenerator,
    ) {
    }

    public function saveComic(ComicPage $comic): void
    {
        $existing = $this->comicRepository->findById($comic->id);

        if ($existing instanceof ComicPage) {
            // 1. Snapshot für Undo-Funktion erstellen
            // Sauber über das Interface aufrufen
            $this->revisionRepository->createSnapshot($existing);

            // 2. Timestamp bewahren, falls im Request nicht neu gesetzt
            if ($comic->imageUpdatedAt === null && $existing->imageUpdatedAt !== null) {
                $comic = new ComicPage(
                    id: $comic->id,
                    type: $comic->type,
                    name: $comic->name,
                    transcript: $comic->transcript,
                    chapterId: $comic->chapterId,
                    characterIds: $comic->characterIds,
                    originalUrl: $comic->originalUrl,
                    sketchUrl: $comic->sketchUrl,
                    userIds: $comic->userIds,
                    imageUpdatedAt: $existing->imageUpdatedAt,
                );
            }
        }

        $this->comicRepository->save($comic);
        $this->siteGenerator->generateAll();
    }

    public function triggerImageCacheBust(ComicId $id): void
    {
        $comic = $this->comicRepository->findById($id);

        if (!$comic instanceof ComicPage) {
            throw new EntityNotFoundException("Comic mit der ID {$id->value} nicht gefunden.");
        }

        // Da unsere Entities 'readonly' (immutable) sind, erstellen wir eine exakte Kopie
        // mit dem aktualisierten Timestamp. Das ist wohl Best-Practice in DDD.
        $updatedComic = new ComicPage(
            id: $comic->id,
            type: $comic->type,
            name: $comic->name,
            transcript: $comic->transcript,
            chapterId: $comic->chapterId,
            characterIds: $comic->characterIds,
            originalUrl: $comic->originalUrl,
            sketchUrl: $comic->sketchUrl,
            userIds: $comic->userIds,
            imageUpdatedAt: $this->clock->now()->getTimestamp(),
        );

        $this->comicRepository->save($updatedComic);
    }

    public function deleteComic(ComicId $id): void
    {
        $existing = $this->comicRepository->findById($id);
        if ($existing instanceof ComicPage) {
            // VOR dem Löschen ein letztes Backup für den Papierkorb anlegen!
            $this->revisionRepository->createSnapshot($existing);
        }

        $this->comicRepository->delete($id);
        $this->siteGenerator->generateAll();
    }

    public function restoreLatestRevision(ComicId $id): void
    {
        $revisionData = $this->revisionRepository->popLatestRevision($id);

        if (!\is_array($revisionData)) {
            throw new DomainException('Keine vorherige Version (Snapshot) für diesen Comic gefunden.');
        }

        /** @var array<string, mixed> $validData */
        $validData = $revisionData;
        $restoredComic = $this->hydrateRevisionData($id, $validData);

        // MAGIC: Wir rufen saveComic() auf statt das Repository!
        // Dadurch wird der JETZIGE Zustand gesichert, bevor das Backup geladen wird.
        // Das ergibt automatisch unsere "Redo / Wiederherstellen" Funktion!
        $this->saveComic($restoredComic);
    }

    public function renameComic(ComicId $oldId, ComicId $newId): void
    {
        if ($oldId->value === $newId->value) {
            return;
        }

        if ($this->comicRepository->findById($newId) instanceof ComicPage) {
            throw new DomainException("Die neue Comic-ID {$newId->value} existiert bereits und kann nicht überschrieben werden!");
        }

        $this->comicRepository->renameComicId($oldId, $newId);
    }

    /**
     * @return array{id: string}
     */
    public function restoreDeletedComic(): array
    {
        $revisionData = $this->revisionRepository->popLatestDeletedRevision();

        if (!\is_array($revisionData)) {
            throw new DomainException('Kein gelöschter Comic im Papierkorb gefunden.');
        }

        $idRaw = \is_string($revisionData['comic_id'] ?? null) ? $revisionData['comic_id'] : '';
        $id = new ComicId($idRaw);

        /** @var array<string, mixed> $validData */
        $validData = $revisionData;
        $restoredComic = $this->hydrateRevisionData($id, $validData);

        // Wir legen ihn wieder regulär an
        $this->comicRepository->save($restoredComic);
        $this->siteGenerator->generateAll();

        return ['id' => $id->value];
    }

    /**
     * @param array<string, mixed> $revisionData
     */
    private function hydrateRevisionData(ComicId $id, array $revisionData): ComicPage
    {
        $charIds = [];
        $rawCharIds = $revisionData['character_ids'] ?? [];
        if (\is_array($rawCharIds)) {
            foreach ($rawCharIds as $cid) {
                if (!\is_string($cid)) {
                    continue;
                }

                try {
                    $charIds[] = new CharacterId($cid);
                } catch (InvalidArgumentException) {
                }
            }
        }

        $userIds = [];
        $rawUserIds = $revisionData['user_ids'] ?? [];
        if (\is_array($rawUserIds)) {
            foreach ($rawUserIds as $uid) {
                if (!\is_string($uid)) {
                    continue;
                }

                $userIds[] = $uid;
            }
        }

        return new ComicPage(
            id: $id,
            type: \is_string($revisionData['type'] ?? null) ? $revisionData['type'] : 'Comicseite',
            name: \is_string($revisionData['name'] ?? null) ? $revisionData['name'] : '',
            transcript: \is_string($revisionData['transcript'] ?? null) ? $revisionData['transcript'] : null,
            chapterId: \is_string($revisionData['chapter_id'] ?? null) ? $revisionData['chapter_id'] : null,
            characterIds: $charIds,
            originalUrl: \is_string($revisionData['original_url'] ?? null) ? $revisionData['original_url'] : '',
            sketchUrl: \is_string($revisionData['sketch_url'] ?? null) ? $revisionData['sketch_url'] : '',
            userIds: $userIds,
            imageUpdatedAt: \is_int($revisionData['image_updated_at'] ?? null) ? $revisionData['image_updated_at'] : null,
        );
    }
}
