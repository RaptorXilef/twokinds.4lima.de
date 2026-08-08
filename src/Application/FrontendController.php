<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Actions\Api\Admin\LoginAction;
use App\Application\Actions\Api\System\CronBackupAction;
use App\Application\Actions\Api\System\ProcessMailQueueAction;
use App\Application\Actions\Frontend\Error404Action;
use App\Application\Contracts\ActionInterface;
use App\Application\Contracts\ResponseInterface;
use App\Application\Http\ServerRequest;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Middleware\MiddlewarePipeline;
use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Application\Response\HtmlResponse;
use App\Application\Response\JsonResponse;
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
        $pathRaw = \parse_url($request->getPath(), \PHP_URL_PATH);
        $path    = \is_string($pathRaw) ? $pathRaw : '/';

        $basePathRaw = \parse_url($this->config->getBaseUrl(), \PHP_URL_PATH);
        $basePath    = \is_string($basePathRaw) ? $basePathRaw : '/';

        $relativePath = '/';

        if (\str_starts_with($path, $basePath)) {
            $relativePath = '/' . \ltrim(\substr($path, \strlen($basePath)), '/');
        } else {
            $relativePath = '/' . \ltrim($path, '/');
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
        }

        if (\is_array($matched)) {
            $className    = \is_string($matched['class']) ? $matched['class'] : Error404Action::class;
            $params       = \is_array($matched['params']) ? $matched['params'] : [];
            $request      = $request->withInput(\array_merge($request->input, $params));
            $requiresAuth = ($matched['requiresAuth'] ?? false) === true;
        } else {
            $className    = Error404Action::class;
            $requiresAuth = false;
        }

        $maintenanceMode  = $this->config->get('maintenance_mode', false) === true;
        $maintenanceAdmin = $this->config->get('maintenance_mode_admin', false) === true;

        $isAdminAction = \str_starts_with($className, 'App\\Application\\Actions\\Admin\\')
            || \str_starts_with($className, 'App\\Application\\Actions\\Api\\Admin\\');
        $isFrontendAction = \str_starts_with($className, 'App\\Application\\Actions\\Frontend\\')
            || \str_starts_with($className, 'App\\Application\\Actions\\Api\\Frontend\\');

        $safeDuringMaintenance = [
            LoginAction::class,
            CronBackupAction::class,
            ProcessMailQueueAction::class,
        ];

        $isLocked = false;
        if ($isAdminAction && $maintenanceAdmin) {
            $isLocked = true;
        } elseif ($isFrontendAction && $maintenanceMode && $this->sessionManager->getAdminGroup() !== 'admin') {
            $isLocked = true;
        }

        if ($isLocked && ! \in_array($className, $safeDuringMaintenance, true)) {
            if (\str_starts_with($className, 'App\\Application\\Actions\\Api\\')) {
                JsonResponse::error('System wird gewartet.', 503)->send();

                return;
            }

            \ob_start();

            $rootPathRaw = $this->config->get('root_path');
            $rootPath    = \is_string($rootPathRaw) ? $rootPathRaw : '';
            require_once \rtrim($rootPath, '/\\') . '/public/maintenance.php';

            $html = \ob_get_clean();

            (new HtmlResponse((string) $html, 503))->send();

            return;
        }

        $pipeline = new MiddlewarePipeline();
        $pipeline->add($this->securityHeaders);

        if ($requiresAuth) {
            $pipeline->add(new AuthMiddleware($this->sessionManager, $this->config));
        }

        $response = $pipeline->process($request, function (ServerRequest $req) use ($className): mixed {
            $action = $this->actionFactory->create($className);
            if ($action instanceof ActionInterface) {
                return $action->execute($req);
            }

            $fallback = $this->actionFactory->create(Error404Action::class);
            if ($fallback instanceof ActionInterface) {
                return $fallback->execute($req);
            }

            return new HtmlResponse('404 Not Found', 404);
        });

        if (!($response instanceof ResponseInterface)) {
            return;
        }

        $response->send();
    }
}
