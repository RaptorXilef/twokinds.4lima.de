<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\System\ImageStorageInterface;
use App\Core\Service\AuthService;

#[Route('GET', '/api/list_media')]
#[RequiresAuth]
final readonly class MediaListAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private ImageStorageInterface $imageStorage,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('media.upload') && !$this->auth->hasPermission('media.delete') && !$this->auth->hasPermission('characters.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $folderRaw = $request->get['folder'] ?? 'profiles';
        $folder = \is_string($folderRaw) ? $folderRaw : 'profiles';

        $result = $this->imageStorage->listCharacterMediaFiles($folder);

        return JsonResponse::success(['files' => $result]);
    }
}
