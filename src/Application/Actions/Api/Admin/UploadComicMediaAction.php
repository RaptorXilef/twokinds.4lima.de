<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\AuthService;
use App\Core\ValueObject\ComicId;

#[Route('POST', '/api/upload_comic_media')]
#[RequiresAuth]
final readonly class UploadComicMediaAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private MediaServiceInterface $mediaService,
        private SiteGeneratorInterface $siteGenerator,
        private ConfigInterface $config,
        private AuthService $auth,
        private ClockInterface $clock,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $comicIdStr = \trim((string) ($request->post['comic_id'] ?? ''));
            if ($comicIdStr === '') {
                return JsonResponse::error('Keine Comic-ID übermittelt.', 400);
            }

            $comicId = new ComicId($comicIdStr);
            $comic   = $this->comicRepo->findById($comicId);
            $force   = (bool) ($request->post['force'] ?? false);

            // Wenn Comic nicht existiert UND kein force-Flag gesetzt ist, brich ab und frag nach
            if (! $comic && ! $force) {
                return JsonResponse::sendPayload([
                    'success' => false,
                    'error'   => 'COMIC_NOT_FOUND',
                    'message' => "Comic {$comicIdStr} existiert nicht.",
                ], 404);
            }

            $files          = $request->files;
            $hiresUploaded  = isset($files['upload_hires']) && $files['upload_hires']['error'] === \UPLOAD_ERR_OK;
            $lowresUploaded = isset($files['upload_lowres']) && $files['upload_lowres']['error'] === \UPLOAD_ERR_OK;

            if (! $hiresUploaded && ! $lowresUploaded) {
                return JsonResponse::error('Keine gültigen Bilder hochgeladen.', 400);
            }

            $tmpHires  = $hiresUploaded ? $files['upload_hires']['tmp_name'] : null;
            $tmpLowres = $lowresUploaded ? $files['upload_lowres']['tmp_name'] : null;

            // Gesamte Datei-System und Skalierungslogik an Infrastruktur delegiert!
            $this->mediaService->processAndStoreComicMedia($comicIdStr, $tmpHires, $tmpLowres);

            if ($comic) {
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
                $this->comicRepo->save($updatedComic);
                $this->siteGenerator->generateAll();
            }

            return JsonResponse::success(['message' => "Medien für {$comicIdStr} erfolgreich verarbeitet!"]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
