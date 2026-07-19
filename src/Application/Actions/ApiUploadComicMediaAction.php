<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Core\Service\MediaService;
use App\Core\ValueObject\ComicId;

#[ActionRoute('api_upload_comic_media')]
final readonly class ApiUploadComicMediaAction implements ActionInterface
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private MediaService $mediaService,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $comicIdStr = \trim((string) ($request->post['comic_id'] ?? ''));
            if ($comicIdStr === '') {
                return JsonResponse::error('Keine Comic-ID übermittelt.', 400);
            }

            $comicId = new ComicId($comicIdStr);
            $comic   = $this->comicRepo->findById($comicId);

            if (! $comic) {
                return JsonResponse::error("Comic {$comicIdStr} existiert nicht in der Datenbank. Bitte lege ihn zuerst an.", 404);
            }

            $files     = $request->files;
            $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comic';

            $hiresUploaded  = isset($files['upload_hires']) && $files['upload_hires']['error'] === \UPLOAD_ERR_OK;
            $lowresUploaded = isset($files['upload_lowres']) && $files['upload_lowres']['error'] === \UPLOAD_ERR_OK;

            if (! $hiresUploaded && ! $lowresUploaded) {
                return JsonResponse::error('Keine gültigen Bilder hochgeladen.', 400);
            }

            foreach (['hires', 'lowres', 'thumbnails', 'socialmedia'] as $sub) {
                $path = "$targetDir/$sub";
                if (! \is_dir($path)) {
                    @\mkdir($path, 0o755, true);
                }
            }

            $baseProcessPath = '';
            $hiresPath       = "$targetDir/hires/{$comicIdStr}.webp";

            if ($hiresUploaded) {
                $this->mediaService->generateScaledImage($files['upload_hires']['tmp_name'], $hiresPath, 4000);
                $baseProcessPath = $hiresPath;
            }

            if ($lowresUploaded) {
                $lowresPath = "$targetDir/lowres/{$comicIdStr}.webp";
                $this->mediaService->generateScaledImage($files['upload_lowres']['tmp_name'], $lowresPath, 1500);
                $baseProcessPath = $lowresPath;
            } elseif ($hiresUploaded && \file_exists($hiresPath)) {
                $lowresPath = "$targetDir/lowres/{$comicIdStr}.webp";
                $this->mediaService->generateScaledImage($hiresPath, $lowresPath, 1080);
                $baseProcessPath = $lowresPath;
            }

            if ($baseProcessPath !== '') {
                $this->mediaService->generateScaledImage($baseProcessPath, "$targetDir/thumbnails/{$comicIdStr}.webp", 200);
                $this->mediaService->generateSquareCrop($baseProcessPath, "$targetDir/socialmedia/{$comicIdStr}.webp", 600);
            }

            // Zeitstempel für RSS / Cachebusting aktualisieren!
            $updatedComic = new \App\Core\Entity\ComicPage(
                id: $comic->id,
                type: $comic->type,
                name: $comic->name,
                transcript: $comic->transcript,
                chapterId: $comic->chapterId,
                characterIds: $comic->characterIds,
                originalUrl: $comic->originalUrl,
                sketchUrl: $comic->sketchUrl,
                imageUpdatedAt: \time(),
            );
            $this->comicRepo->save($updatedComic);

            return JsonResponse::success(['message' => "Medien für {$comicIdStr} erfolgreich verarbeitet!"]);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler: ' . $e->getMessage(), 500);
        }
    }
}
