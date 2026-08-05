<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\Route;
use App\Application\Attribute\RequiresAuth;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;
use App\Core\Service\MediaService;

#[Route('POST', '/api/upload_media')]
#[RequiresAuth]
final readonly class UploadMediaAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private MediaService $mediaService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.upload')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }
        $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';

        if (! \is_dir($targetDir)) {
            @\mkdir($targetDir, 0o755, true);
        }

        if (! isset($_FILES['files']) || ! \is_array($_FILES['files']['name'])) {
            return JsonResponse::error('Keine Dateien hochgeladen.', 400);
        }

        $files          = $_FILES['files'];
        $count          = \count($files['name']);
        $processedCount = 0;

        for ($i = 0; $i < $count; ++$i) {
            if ($files['error'][$i] === \UPLOAD_ERR_OK) {
                $tmpName      = $files['tmp_name'][$i];
                $originalName = $files['name'][$i];

                // Dateinamen bereinigen
                // Kebab-Case Formatierung anwenden
                $slugifiedName  = Sanitizer::slugify($originalName);
                $nameWithoutExt = \pathinfo($slugifiedName, \PATHINFO_FILENAME);

                $targetPath = $targetDir . '/' . $nameWithoutExt . '.webp';

                // In WebP umwandeln (Max 1000px für Profile)
                if ($this->mediaService->generateScaledImage($tmpName, $targetPath, 1000)) {
                    ++$processedCount;
                }
            }
        }

        return JsonResponse::success(['message' => "{$processedCount} Bild(er) erfolgreich verarbeitet und hochgeladen!"]);
    }
}
