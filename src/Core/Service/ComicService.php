<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Exception\EntityNotFoundException;
use App\Core\ValueObject\ComicId;

final readonly class ComicService
{
    public function __construct(
        private ComicRepositoryInterface $comicRepository,
        private ClockInterface $clock,
    ) {
    }

    public function saveComic(ComicPage $comic): void
    {
        // Bewahre den imageUpdatedAt Timestamp, falls dieser in der DB existiert,
        // im aktuellen Request aber nicht explizit neu gesetzt wurde.
        if ($comic->imageUpdatedAt === null) {
            $existing = $this->comicRepository->findById($comic->id);
            if ($existing instanceof ComicPage && $existing->imageUpdatedAt !== null) {
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
    }

    public function triggerImageCacheBust(ComicId $id): void
    {
        $comic = $this->comicRepository->findById($id);

        if (! $comic instanceof ComicPage) {
            throw new EntityNotFoundException("Comic mit der ID {$id->value} nicht gefunden.");
        }

        // Da unsere Entities 'readonly' (immutable) sind, erstellen wir eine exakte Kopie
        // mit dem aktualisierten Timestamp. Das ist Best-Practice in DDD.
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
        $this->comicRepository->delete($id);
    }
}
