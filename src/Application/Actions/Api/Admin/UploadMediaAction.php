<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Service\AuthService;

#[Route('POST', '/api/upload_media')]
#[RequiresAuth]
final readonly class UploadMediaAction implements ActionInterface
{
    public function __construct(
        private MediaServiceInterface $mediaService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('media.upload')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $files = $request->files['files'] ?? null;
        if (!\is_array($files) || !isset($files['name']) || !\is_array($files['name'])) {
            return JsonResponse::error('Keine Dateien hochgeladen.', 400);
        }

        /** @var array<string, mixed> $validFiles */
        $validFiles = [];
        foreach ($files as $k => $v) {
            $validFiles[(string) $k] = $v;
        }

        // Infrastruktur erledigt den kompletten Upload, Ordner-Check und Skalierung!
        $processedCount = $this->mediaService->processMassProfileUpload($validFiles);

        return JsonResponse::success(['message' => "{$processedCount} Bild(er) erfolgreich verarbeitet und hochgeladen!"]);
    }
}
