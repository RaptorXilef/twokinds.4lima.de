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
        $actionKey = $this->determineActionKey($request, $resolvedInput);

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

    private function determineActionKey(ServerRequest $request, &$resolvedInput): string
    {
        $resolvedInput = [];

        // URL-Pfad bereinigen (z.B. /twokinds/public/comic/20251225 -> comic/20251225)
        $path         = \parse_url($request->getPath(), \PHP_URL_PATH);
        $basePath     = \parse_url($this->config->getBaseUrl(), \PHP_URL_PATH) ?? '/';
        $relativePath = \trim(\substr($path, \strlen($basePath)), '/');

        if ($relativePath === '' || $relativePath === 'index.php') {
            return 'render_comic'; // Startseite lädt den neuesten Comic
        }

        if (\preg_match('#^comic/(\d{8})(?:\.php)?$#', $relativePath, $matches)) {
            $resolvedInput['id'] = $matches[1];

            return 'render_comic';
        }

        if ($relativePath === 'archiv' || $relativePath === 'archiv.php') {
            return 'render_archive';
        }

        if ($relativePath === 'charaktere' || $relativePath === 'charakter-vorstellung.php') {
            return 'render_character_list';
        }

        if (\preg_match('#^charaktere/([a-zA-Z0-9_-]+)(?:\.php)?$#', $relativePath, $matches)) {
            $resolvedInput['char_name'] = $matches[1];

            return 'render_character_detail';
        }

        return 'render_404';
    }
}
