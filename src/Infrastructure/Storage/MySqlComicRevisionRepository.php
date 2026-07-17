<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\ComicRevisionRepositoryInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;

final readonly class MySqlComicRevisionRepository implements ComicRevisionRepositoryInterface
{
    public function __construct(
        private \PDO $pdo,
        private ClockInterface $clock,
    ) {
    }

    public function createSnapshot(ComicPage $oldState): void
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
}
