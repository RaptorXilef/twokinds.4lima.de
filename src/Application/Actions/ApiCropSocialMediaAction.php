<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\MediaService;

#[ActionRoute('api_crop_social_media')]
final readonly class ApiCropSocialMediaAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private MediaService $mediaService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $comicId = \trim((string) ($request->post['comic_id'] ?? ''));
        $x       = (int) ($request->post['x'] ?? 0);
        $y       = (int) ($request->post['y'] ?? 0);
        $w       = (int) ($request->post['width'] ?? 0);
        $h       = (int) ($request->post['height'] ?? 0);

        if ($comicId === '' || $w <= 0 || $h <= 0) {
            return JsonResponse::error('Ungültige Schnitt-Parameter.', 400);
        }

        $targetDir  = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comic';
        $sourcePath = "$targetDir/hires/$comicId.webp";
        // WICHTIG: JPG für maximale Kompatibilität!
        $targetPath = "$targetDir/socialmedia/$comicId.jpg";

        if (! \file_exists($sourcePath)) {
            return JsonResponse::error('Hires-Quellbild für den Zuschnitt nicht gefunden.', 404);
        }

        $success = $this->mediaService->generateManualCrop(
            $sourcePath,
            $targetPath,
            $x,
            $y,
            $w,
            $h,
            1200,
            630,
        );

        if ($success) {
            return JsonResponse::success(['message' => 'Social-Media-Bild erfolgreich zugeschnitten!']);
        }

        return JsonResponse::error('Fehler beim Generieren des Bildes.', 500);
    }
}
