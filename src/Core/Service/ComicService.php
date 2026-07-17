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
        private \PDO $pdo, // Hinzugefügt für schnelle Revisions-Inserts
    ) {
    }

    public function saveComic(ComicPage $comic): void
    {
        $existing = $this->comicRepository->findById($comic->id);

        if ($existing instanceof ComicPage) {
            // 1. Snapshot für Undo-Funktion erstellen
            $this->createRevisionSnapshot($existing);

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
    }

    private function createRevisionSnapshot(ComicPage $oldState): void
    {
        $snapshotData = [
            'type'             => $oldState->type,
            'name'             => $oldState->name,
            'transcript'       => $oldState->transcript,
            'chapter_id'       => $oldState->chapterId,
            'character_ids'    => \array_map(fn ($id) => $id->value, $oldState->characterIds),
            'original_url'     => $oldState->originalUrl,
            'sketch_url'       => $oldState->sketchUrl,
            'image_updated_at' => $oldState->imageUpdatedAt,
        ];

        $stmt = $this->pdo->prepare('INSERT INTO `comic_revisions` (`comic_id`, `revision_data`, `created_at`) VALUES (?, ?, ?)');
        $stmt->execute([
            $oldState->id->value,
            \json_encode($snapshotData, \JSON_UNESCAPED_UNICODE),
            $this->clock->now()->format('Y-m-d H:i:s'),
        ]);
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
