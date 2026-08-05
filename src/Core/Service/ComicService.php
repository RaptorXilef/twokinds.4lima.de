<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Exception\EntityNotFoundException;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

final readonly class ComicService
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private ComicRevisionRepositoryInterface $revisionRepository,
        private ClockInterface $clock,
        private SiteGeneratorService $siteGenerator,
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

        if (! $comic instanceof ComicPage) {
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
            imageUpdatedAt: $this->clock->now()->getTimestamp(),
        );

        $this->comicRepository->save($updatedComic);
    }

    public function deleteComic(ComicId $id): void
    {
        $existing = $this->comicRepository->findById($id);
        if ($existing) {
            // VOR dem Löschen ein letztes Backup für den Papierkorb anlegen!
            $this->revisionRepository->createSnapshot($existing);
        }
        $this->comicRepository->delete($id);
        $this->siteGenerator->generateAll();
    }

    public function restoreLatestRevision(ComicId $id): void
    {
        $revisionData = $this->revisionRepository->popLatestRevision($id);

        if ($revisionData === null) {
            throw new \DomainException('Keine vorherige Version (Snapshot) für diesen Comic gefunden.');
        }

        $charIds = [];
        foreach ($revisionData['character_ids'] ?? [] as $cid) {
            try {
                $charIds[] = new CharacterId($cid);
            } catch (\InvalidArgumentException) {
            }
        }

        $restoredComic = new ComicPage(
            id: $id,
            type: $revisionData['type'] ?? 'Comicseite',
            name: $revisionData['name'] ?? '',
            transcript: $revisionData['transcript'] ?? null,
            chapterId: $revisionData['chapter_id'] ?? null,
            characterIds: $charIds,
            originalUrl: $revisionData['original_url'] ?? '',
            sketchUrl: $revisionData['sketch_url'] ?? '',
            imageUpdatedAt: $revisionData['image_updated_at'] ?? null,
        );

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
            throw new \DomainException("Die neue Comic-ID {$newId->value} existiert bereits und kann nicht überschrieben werden!");
        }

        $this->comicRepository->renameComicId($oldId, $newId);
    }

    public function restoreDeletedComic(): array
    {
        $revisionData = $this->revisionRepository->popLatestDeletedRevision();
        if ($revisionData === null) {
            throw new \DomainException('Kein gelöschter Comic im Papierkorb gefunden.');
        }

        $id = new ComicId($revisionData['comic_id']);

        $charIds = [];
        foreach ($revisionData['character_ids'] ?? [] as $cid) {
            try {
                $charIds[] = new CharacterId($cid);
            } catch (\InvalidArgumentException) {
            }
        }

        $restoredComic = new ComicPage(
            id: $id,
            type: $revisionData['type'] ?? 'Comicseite',
            name: $revisionData['name'] ?? '',
            transcript: $revisionData['transcript'] ?? null,
            chapterId: $revisionData['chapter_id'] ?? null,
            characterIds: $charIds,
            originalUrl: $revisionData['original_url'] ?? '',
            sketchUrl: $revisionData['sketch_url'] ?? '',
            imageUpdatedAt: $revisionData['image_updated_at'] ?? null,
        );

        // Wir legen ihn wieder regulär an
        $this->comicRepository->save($restoredComic);
        $this->siteGenerator->generateAll();

        return ['id' => $id->value];
    }
}
