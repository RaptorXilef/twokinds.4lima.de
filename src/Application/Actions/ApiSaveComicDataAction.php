<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SaveComicDataRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\ComicService;
use App\Core\ValueObject\CharacterId;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_save_comic_data')]
final readonly class ApiSaveComicDataAction implements ActionInterface
{
    public function __construct(
        private ComicService $comicService,
        private ComicRepositoryInterface $comicRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SaveComicDataRequest::fromRequest($request);

            // 1. Abgleich: Was wurde gelöscht?
            $existingComics = $this->comicRepo->findAll();
            $incomingIds    = \array_keys($dto->comics);

            foreach ($existingComics as $existing) {
                if (! \in_array($existing->id->value, $incomingIds, true)) {
                    $this->comicService->deleteComic($existing->id);
                }
            }

            // 2. Speichern / Aktualisieren
            foreach ($dto->comics as $id => $cData) {
                // Charaktere mappen
                $charIds = [];
                if (isset($cData['charaktere']) && \is_array($cData['charaktere'])) {
                    foreach ($cData['charaktere'] as $cId) {
                        try {
                            $charIds[] = new CharacterId((string) $cId);
                        } catch (\InvalidArgumentException) {
                            continue; // Ungültige IDs (z.B. aus Legacy-Fehlern) ignorieren
                        }
                    }
                }

                $comic = new ComicPage(
                    id: new ComicId((string) $id),
                    type: $cData['type'] ?? 'Comicseite',
                    name: $cData['name'] ?? '',
                    transcript: $cData['transcript'] ?? '',
                    chapterId: (isset($cData['chapter']) && $cData['chapter'] !== '') ? (string) $cData['chapter'] : null,
                    characterIds: $charIds,
                    originalUrl: $cData['url_originalbild'] ?? '',
                    sketchUrl: $cData['url_originalsketch'] ?? '',
                    imageUpdatedAt: null, // Wird vom Service intelligent bewahrt
                );

                $this->comicService->saveComic($comic);
            }

            return JsonResponse::success(['message' => 'Comic-Daten erfolgreich gespeichert.']);

        } catch (ValidationException|\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return JsonResponse::error('Ein interner Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
