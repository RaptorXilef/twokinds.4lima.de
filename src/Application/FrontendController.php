<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Contracts\ResponseInterface;
use App\Application\Http\ServerRequest;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Middleware\MiddlewarePipeline;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Routing\UniversalActionFactory;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;

final readonly class FrontendController
{
    public function __construct(
        private ConfigInterface $config,
        private UniversalActionFactory $actionFactory,
        private SecurityHeadersMiddleware $securityHeaders,
        private SessionManager $sessionManager,
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

        // --- AuthMiddleware dynamisch einhängen ---
        $isProtectedApi   = \str_starts_with($actionKey, 'api_') && $actionKey !== 'api_admin_login';
        $isProtectedAdmin = \str_starts_with($actionKey, 'render_admin_') && $actionKey !== 'render_admin_login';

        if ($isProtectedApi || $isProtectedAdmin) {
            $pipeline->add(new AuthMiddleware($this->sessionManager, $this->config));
        }
        // -----------------------------------------------

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

        // === API Routing ===
        if (\str_starts_with($relativePath, 'api/')) {
            $apiPath = \substr($relativePath, 4); // Schneidet 'api/' ab

            return match ($apiPath) {
                'admin_login'           => ['action' => 'api_admin_login', 'input' => $input],
                'admin_logout'          => ['action' => 'api_admin_logout', 'input' => $input],
                'delete_chapter'        => ['action' => 'api_delete_chapter', 'input' => $input],
                'delete_character'      => ['action' => 'api_delete_character', 'input' => $input],
                'delete_comic'          => ['action' => 'api_delete_comic', 'input' => $input],
                'save_chapter'          => ['action' => 'api_save_chapter', 'input' => $input],
                'save_character_groups' => ['action' => 'api_save_character_groups', 'input' => $input],
                'save_single_character' => ['action' => 'api_save_single_character', 'input' => $input],
                'save_single_comic'     => ['action' => 'api_save_single_comic', 'input' => $input],
                'submit_report'         => ['action' => 'api_submit_report', 'input' => $input],
                'undo_comic'            => ['action' => 'api_undo_comic', 'input' => $input],
                'update_report_status'  => ['action' => 'api_update_report_status', 'input' => $input],
                'upload_comic_media'    => ['action' => 'api_upload_comic_media', 'input' => $input],
                default                 => ['action' => 'render_404', 'input' => $input],
            };
        }

        // Backend (Admin-Bereich) Routing
        if (\str_starts_with($relativePath, 'admin')) {
            if ($relativePath === 'admin/login' || $relativePath === 'admin/login.php') {
                return ['action' => 'render_admin_login', 'input' => $input];
            }

            // Standardmäßig alles in /admin auf das Dashboard routen
            return ['action' => 'render_admin_dashboard', 'input' => $input];
        }

        return ['action' => 'render_404', 'input' => $input];
    }
}
