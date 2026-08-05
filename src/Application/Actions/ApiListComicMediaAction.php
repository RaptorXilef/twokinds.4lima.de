<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\AuthService;

#[ActionRoute('api_list_comic_media')]
final readonly class ApiListComicMediaAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.upload') && ! $this->auth->hasPermission('media.delete') && ! $this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $baseDir  = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/comics';
        $thumbDir = $baseDir . '/thumbnails';

        if (! \is_dir($thumbDir)) {
            return JsonResponse::success(['files' => []]);
        }

        $files  = \array_diff(\scandir($thumbDir), ['.', '..']);
        $result = [];

        foreach ($files as $file) {
            $id       = \pathinfo($file, \PATHINFO_FILENAME);
            $result[] = [
                'id' => $id,
                // Wir schicken dem Frontend Infos, welche Versionen existieren
                'has_hires'  => \file_exists("$baseDir/hires/$file"),
                'has_lowres' => \file_exists("$baseDir/lowres/$file"),
                'has_social' => \file_exists("$baseDir/social/$file"),
                'has_thumb'  => \file_exists("$baseDir/thumbnails/$file"),
                'url'        => "/assets/images/comics/thumbnails/{$file}",
            ];
        }

        // Sortiere nach ID absteigend (neueste Comics zuerst)
        \usort($result, fn (array $a, array $b): int => \strcmp($b['id'], $a['id']));

        return JsonResponse::success(['files' => $result]);
    }
}
