<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Contracts\ResponseInterface;
use App\Application\Http\ServerRequest;
use App\Application\Middleware\MiddlewarePipeline;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Routing\UniversalActionFactory;
use App\Contracts\Config\ConfigInterface;

final readonly class FrontendController
{
    public function __construct(
        private ConfigInterface $config,
        private UniversalActionFactory $actionFactory,
        private SecurityHeadersMiddleware $securityHeaders,
        // Weitere Middlewares (wie Analytics) kommen später hier rein
    ) {
    }

    public function handleRequest(ServerRequest $request): void
    {
        $route         = $this->determineRoute($request);
        $actionKey     = $route['action'];
        $resolvedInput = $route['input'];

        // Füge die aufgelösten URL-Parameter (z.B. Comic-ID) dem Request hinzu
        $request = $request->withInput($resolvedInput);

        $pipeline = new MiddlewarePipeline();
        $pipeline->add($this->securityHeaders);

        $response = $pipeline->process($request, function (ServerRequest $req) use ($actionKey): mixed {
            $action = $this->actionFactory->create($actionKey);

            if ($action !== null) {
                return $action->execute($req);
            }

            // Fallback auf 404 Action
            return $this->actionFactory->create('render_404')->execute($req);
        });

        if ($response instanceof ResponseInterface) {
            $response->send();
        }
    }

    /**
     * @return array{action: string, input: array<string, mixed>}
     */
    private function determineRoute(ServerRequest $request): array
    {
        $input = [];

        // URL-Pfad bereinigen (z.B. /twokinds/public/comic/20251225 -> comic/20251225)
        $path     = \parse_url($request->getPath(), \PHP_URL_PATH);
        $basePath = \parse_url($this->config->getBaseUrl(), \PHP_URL_PATH) ?? '/';

        $relativePath = '';
        if (\str_starts_with((string) $path, $basePath)) {
            $relativePath = \trim(\substr((string) $path, \strlen($basePath)), '/');
        } else {
            $relativePath = \trim((string) $path, '/');
        }

        if ($relativePath === '' || $relativePath === 'index.php') {
            return ['action' => 'render_comic', 'input' => $input];
        }

        if (\preg_match('#^comic/(\d{8})(?:\.php)?$#', $relativePath, $matches)) {
            return ['action' => 'render_comic', 'input' => ['id' => $matches[1]]];
        }

        if ($relativePath === 'archiv' || $relativePath === 'archiv.php') {
            return ['action' => 'render_archive', 'input' => $input];
        }

        if ($relativePath === 'charaktere' || $relativePath === 'charakter-vorstellung.php') {
            return ['action' => 'render_character_list', 'input' => $input];
        }

        if (\preg_match('#^charaktere/([a-zA-Z0-9_-]+)(?:\.php)?$#', $relativePath, $matches)) {
            return ['action' => 'render_character_detail', 'input' => ['char_name' => $matches[1]]];
        }

        return ['action' => 'render_404', 'input' => $input];
    }
}
