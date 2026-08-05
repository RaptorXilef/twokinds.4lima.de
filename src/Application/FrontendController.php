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
    ) {
    }

    public function handleRequest(ServerRequest $request): void
    {
        $path     = \parse_url($request->getPath(), \PHP_URL_PATH) ?? '/';
        $basePath = \parse_url($this->config->getBaseUrl(), \PHP_URL_PATH) ?? '/';

        $relativePath = '/';
        if (\str_starts_with((string) $path, $basePath)) {
            $relativePath = '/' . \ltrim(\substr((string) $path, \strlen($basePath)), '/');
        } else {
            $relativePath = '/' . \ltrim((string) $path, '/');
        }

        if (\str_ends_with($relativePath, '.php')) {
            $relativePath = \substr($relativePath, 0, -4);
        }
        if ($relativePath === '/index') {
            $relativePath = '/';
        }

        $method  = $request->getMethod();
        $matched = $this->actionFactory->getRegistry()->match($method, $relativePath);

        if ($matched === null) {
            $matched = $this->actionFactory->getRegistry()->match('GET', '/404');
            \http_response_code(404);
        }

        $className = $matched['class'];
        $request   = $request->withInput(\array_merge($request->input, $matched['params']));

        $maintenanceMode  = (bool) $this->config->get('maintenance_mode', false);
        $maintenanceAdmin = (bool) $this->config->get('maintenance_mode_admin', false);

        $isAdminAction = \str_starts_with($className, 'App\\Application\\Actions\\Admin\\')
            || \str_starts_with($className, 'App\\Application\\Actions\\Api\\Admin\\');
        $isFrontendAction = \str_starts_with($className, 'App\\Application\\Actions\\Frontend\\')
            || \str_starts_with($className, 'App\\Application\\Actions\\Api\\Frontend\\');

        $safeDuringMaintenance = [
            'App\Application\Actions\Api\Admin\LoginAction',
            'App\Application\Actions\Api\System\CronBackupAction',
            'App\Application\Actions\Api\System\ProcessMailQueueAction',
        ];

        $isLocked = false;
        if ($isAdminAction && $maintenanceAdmin) {
            $isLocked = true;
        } elseif ($isFrontendAction && $maintenanceMode && $this->sessionManager->getAdminGroup() !== 'admin') {
            $isLocked = true;
        }

        if ($isLocked && ! \in_array($className, $safeDuringMaintenance, true)) {
            if (\str_starts_with($className, 'App\\Application\\Actions\\Api\\')) {
                \http_response_code(503);
                \header('Content-Type: application/json; charset=utf-8');
                echo \json_encode(['success' => false, 'error' => 'System wird gewartet.']);
                exit;
            }
            require_once \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/maintenance.php';
            exit;
        }

        $pipeline = new MiddlewarePipeline();
        $pipeline->add($this->securityHeaders);

        // MAGIC: Die Middleware wird vollautomatisch geladen, wenn die Klasse das Attribut #[RequiresAuth] hat!
        if ($matched['requiresAuth']) {
            $pipeline->add(new AuthMiddleware($this->sessionManager, $this->config));
        }

        $response = $pipeline->process($request, function (ServerRequest $req) use ($className): mixed {
            $action = $this->actionFactory->create($className);
            if ($action !== null) {
                return $action->execute($req);
            }

            return $this->actionFactory->create('App\\Application\\Actions\\Frontend\\Error404Action')->execute($req);
        });

        if ($response instanceof ResponseInterface) {
            $response->send();
        }
    }
}
