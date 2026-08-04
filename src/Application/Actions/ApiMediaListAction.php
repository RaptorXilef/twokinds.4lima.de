<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Core\Service\AuthService;

#[ActionRoute('api_list_media')]
final readonly class ApiMediaListAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('media.upload') && ! $this->auth->hasPermission('media.delete') && ! $this->auth->hasPermission('characters.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $folder  = $request->get['folder'] ?? 'profiles';
        $allowed = ['profiles', 'portraits', 'palettes', 'refsheets'];

        if (! \in_array($folder, $allowed, true)) {
            $folder = 'profiles';
        }

        $dir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/' . $folder;

        if (! \is_dir($dir)) {
            return JsonResponse::success(['files' => []]);
        }

        $files  = \array_diff(\scandir($dir), ['.', '..']);
        $result = [];

        foreach ($files as $file) {
            if (\is_file($dir . '/' . $file)) {
                $result[] = ['filename' => $file, 'url' => "/assets/images/characters/{$folder}/{$file}"];
            }
        }

        return JsonResponse::success(['files' => $result]);
    }
}
