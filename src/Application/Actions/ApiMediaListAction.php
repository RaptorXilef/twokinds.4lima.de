<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;

#[ActionRoute('api_list_media')]
final readonly class ApiMediaListAction implements ActionInterface
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        $dir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/characters/profiles';
        if (! \is_dir($dir)) {
            return JsonResponse::success(['files' => []]);
        }

        $files  = \array_diff(\scandir($dir), ['.', '..']);
        $result = [];
        foreach ($files as $file) {
            $result[] = ['filename' => $file, 'url' => "/assets/images/characters/profiles/{$file}"];
        }

        return JsonResponse::success(['files' => $result]);
    }
}
