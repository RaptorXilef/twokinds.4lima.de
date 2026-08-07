<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/crop_social_media')]
#[RequiresAuth]
final readonly class CropSocialMediaAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private MediaServiceInterface $mediaService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        // Maximale Power für riesige Bilder und Warnungen unterdrücken, damit das JSON nicht kaputt geht
        \ini_set('memory_limit', '1024M');
        \ini_set('display_errors', '0');
        \error_reporting(0);

        try {
            $comicId = \trim((string) ($request->post['comic_id'] ?? ''));
            $x       = (int) ($request->post['x'] ?? 0);
            $y       = (int) ($request->post['y'] ?? 0);
            $w       = (int) ($request->post['width'] ?? 0);
            $h       = (int) ($request->post['height'] ?? 0);

            if ($comicId === '' || $w <= 0 || $h <= 0) {
                return JsonResponse::error('Ungültige Schnitt-Parameter.', 400);
            }

            $targetDir  = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comics';
            $sourcePath = "$targetDir/hires/$comicId.webp";
            // WICHTIG: JPG für maximale Kompatibilität!
            $targetPath = "$targetDir/social/$comicId.jpg";

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

            return JsonResponse::error('Fehler beim Generieren des Bildes (GD Error).', 500);
        } catch (\Throwable $e) {
            // Fängt alle fatalen PHP-Fehler (z.B. OOM) als sauberes JSON ab
            return JsonResponse::error('Server-Fehler: ' . $e->getMessage(), 500);
        }
    }
}
