<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Contracts\System\SiteGeneratorInterface;
use App\Contracts\Utils\ClockInterface;
use App\Core\Entity\ComicPage;
use App\Core\Service\AuthService;
use App\Core\ValueObject\ComicId;
use Throwable;

#[Route('POST', '/api/upload_comic_media')]
#[RequiresAuth]
final readonly class UploadComicMediaAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private MediaServiceInterface $mediaService,
        private SiteGeneratorInterface $siteGenerator,
        private AuthService $auth,
        private ClockInterface $clock,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        try {
            $comicIdRaw = $request->post['comic_id'] ?? '';
            $comicIdStr = \is_scalar($comicIdRaw) ? \trim((string) $comicIdRaw) : '';

            if ($comicIdStr === '') {
                return JsonResponse::error('Keine Comic-ID übermittelt.', 400);
            }

            $comicId = new ComicId($comicIdStr);
            $comic = $this->comicRepo->findById($comicId);

            $fRaw = $request->post['force'] ?? false;
            $force = \in_array($fRaw, [true, 1, '1', 'true', 'on'], true);

            // Wenn Comic nicht existiert UND kein force-Flag gesetzt ist, brich ab und frag nach
            if (!$comic instanceof ComicPage && !$force) {
                return JsonResponse::sendPayload([
                    'success' => false,
                    'error' => 'COMIC_NOT_FOUND',
                    'message' => "Comic {$comicIdStr} existiert nicht.",
                ], 404);
            }

            $files = $request->files;

            $uploadHires = $files['upload_hires'] ?? null;
            $hiresUploaded = \is_array($uploadHires) && isset($uploadHires['error']) && $uploadHires['error'] === \UPLOAD_ERR_OK; // phpcs:ignore Generic.Files.LineLength.TooLong
            $tmpHires = $hiresUploaded && isset($uploadHires['tmp_name']) && \is_string($uploadHires['tmp_name']) ? $uploadHires['tmp_name'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong

            $uploadLowres = $files['upload_lowres'] ?? null;
            $lowresUploaded = \is_array($uploadLowres) && isset($uploadLowres['error']) && $uploadLowres['error'] === \UPLOAD_ERR_OK; // phpcs:ignore Generic.Files.LineLength.TooLong
            $tmpLowres = $lowresUploaded && isset($uploadLowres['tmp_name']) && \is_string($uploadLowres['tmp_name']) ? $uploadLowres['tmp_name'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong

            if (!$hiresUploaded && !$lowresUploaded) {
                return JsonResponse::error('Keine gültigen Bilder hochgeladen.', 400);
            }

            // Gesamte Datei-System und Skalierungslogik an Infrastruktur delegiert!
            $this->mediaService->processAndStoreComicMedia($comicIdStr, $tmpHires, $tmpLowres);

            if ($comic instanceof ComicPage) {
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
                $this->comicRepo->save($updatedComic);
                $this->siteGenerator->generateAll();
            }

            return JsonResponse::success(['message' => "Medien für {$comicIdStr} erfolgreich verarbeitet!"]);
        } catch (Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
