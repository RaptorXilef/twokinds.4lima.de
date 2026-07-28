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
        $publicApiRoutes = [
            'api_admin_login',
            'api_frontend_forgot_password',
            'api_frontend_logout',
            'api_frontend_register',
            'api_frontend_resend_verification',
            'api_frontend_reset_password',
            'api_get_transcript',
            'api_submit_report',
        ];
        $isProtectedApi = \str_starts_with($actionKey, 'api_') && ! \in_array($actionKey, $publicApiRoutes, true);

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

        if ($relativePath === '' || $relativePath === 'index.php' || $relativePath === 'comic') {
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

        if (\preg_match('#^charaktere/([^/]+)(?:\.php)?$#', $relativePath, $matches)) {
            return ['action' => 'render_character_detail', 'input' => ['id' => $matches[1]]];
        }

        if ($relativePath === 'projekt' || $relativePath === 'projekt.php') {
            return ['action' => 'page_project_info', 'input' => $input];
        }

        if ($relativePath === 'lesezeichen' || $relativePath === 'lesezeichen.php') {
            return ['action' => 'render_bookmarks', 'input' => $input];
        }

        if ($relativePath === 'impressum' || $relativePath === 'impressum.php') {
            return ['action' => 'page_imprint', 'input' => $input];
        }

        if ($relativePath === 'datenschutz' || $relativePath === 'datenschutz.php' || $relativePath === 'datenschutzerklaerung.php') {
            return ['action' => 'page_privacy', 'input' => $input];
        }

        if ($relativePath === 'login' || $relativePath === 'login.php') {
            return ['action' => 'render_frontend_login', 'input' => $input];
        }

        if ($relativePath === 'registrieren' || $relativePath === 'registrieren.php') {
            return ['action' => 'render_frontend_register', 'input' => $input];
        }

        if ($relativePath === 'passwort-vergessen' || $relativePath === 'passwort-vergessen.php') {
            return ['action' => 'render_frontend_forgot_password', 'input' => $input];
        }
        if ($relativePath === 'passwort-reset' || $relativePath === 'passwort-reset.php') {
            return ['action' => 'render_frontend_reset_password', 'input' => $input];
        }

        if ($relativePath === 'verifizieren' || $relativePath === 'verifizieren.php') {
            return ['action' => 'render_frontend_verify', 'input' => $input];
        }

        if ($relativePath === 'profil' || $relativePath === 'profil.php') {
            return ['action' => 'render_frontend_profile', 'input' => $input];
        }

        if ($relativePath === 'bestaetigungsmail-anfordern' || $relativePath === 'bestaetigungsmail-anfordern.php') {
            return ['action' => 'render_frontend_resend_verification', 'input' => $input];
        }

        if ($relativePath === '403') {
            return ['action' => 'render_403', 'input' => $input];
        }

        // === API Routing ===
        if (\str_starts_with($relativePath, 'api/')) {
            // Schneidet 'api/' ab und entfernt eventuelle Slashes am Ende
            $apiPath = \trim(\substr($relativePath, 4), '/');

            return match ($apiPath) {
                'admin_login'                  => ['action' => 'api_admin_login', 'input' => $input],
                'admin_logout'                 => ['action' => 'api_admin_logout', 'input' => $input],
                'admin_trigger_newsletter'     => ['action' => 'api_admin_trigger_newsletter', 'input' => $input],
                'crop_social_media'            => ['action' => 'api_crop_social_media', 'input' => $input],
                'delete_chapter'               => ['action' => 'api_delete_chapter', 'input' => $input],
                'delete_character'             => ['action' => 'api_delete_character', 'input' => $input],
                'delete_comic_media'           => ['action' => 'api_delete_comic_media', 'input' => $input],
                'delete_comic'                 => ['action' => 'api_delete_comic', 'input' => $input],
                'delete_media'                 => ['action' => 'api_delete_media', 'input' => $input],
                'delete_role'                  => ['action' => 'api_delete_role', 'input' => $input],
                'delete_user'                  => ['action' => 'api_delete_user', 'input' => $input],
                'frontend_forgot_password'     => ['action' => 'api_frontend_forgot_password', 'input' => $input],
                'frontend_logout'              => ['action' => 'api_frontend_logout', 'input' => $input],
                'frontend_register'            => ['action' => 'api_frontend_register', 'input' => $input],
                'frontend_resend_verification' => ['action' => 'api_frontend_resend_verification', 'input' => $input],
                'frontend_reset_password'      => ['action' => 'api_frontend_reset_password', 'input' => $input],
                'frontend_update_profile'      => ['action' => 'api_frontend_update_profile', 'input' => $input],
                'get_transcript'               => ['action' => 'api_get_transcript', 'input' => $input],
                'list_comic_media'             => ['action' => 'api_list_comic_media', 'input' => $input],
                'list_media'                   => ['action' => 'api_list_media', 'input' => $input],
                'process_mail_queue'           => ['action' => 'api_process_mail_queue', 'input' => $input],
                'projekt'                      => ['action' => 'page_project_info'],
                'restore_deleted_comic'        => ['action' => 'api_restore_deleted_comic', 'input' => $input],
                'save_chapter'                 => ['action' => 'api_save_chapter', 'input' => $input],
                'save_character_groups'        => ['action' => 'api_save_character_groups', 'input' => $input],
                'save_role'                    => ['action' => 'api_save_role', 'input' => $input],
                'save_single_character'        => ['action' => 'api_save_single_character', 'input' => $input],
                'save_single_comic'            => ['action' => 'api_save_single_comic', 'input' => $input],
                'save_user'                    => ['action' => 'api_save_user', 'input' => $input],
                'submit_report'                => ['action' => 'api_submit_report', 'input' => $input],
                'sync_bookmarks'               => ['action' => 'api_sync_bookmarks', 'input' => $input],
                'toggle_bookmark'              => ['action' => 'api_toggle_bookmark', 'input' => $input],
                'undo_comic'                   => ['action' => 'api_undo_comic', 'input' => $input],
                'update_report_status'         => ['action' => 'api_update_report_status', 'input' => $input],
                'upload_comic_media'           => ['action' => 'api_upload_comic_media', 'input' => $input],
                'upload_media'                 => ['action' => 'api_upload_media', 'input' => $input],
                default                        => ['action' => 'render_404', 'input' => $input],
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
