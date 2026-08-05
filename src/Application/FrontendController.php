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
        // 1. Zuerst die Route bestimmen, damit wir wissen, um welche Aktion es geht!
        $route         = $this->determineRoute($request);
        $actionKey     = $route['action'];
        $resolvedInput = $route['input'];
        $request       = $request->withInput($resolvedInput);

        // --- WARTUNGSMODUS PRÜFUNG ---
        $maintenanceMode  = (bool) $this->config->get('maintenance_mode', false);
        $maintenanceAdmin = (bool) $this->config->get('maintenance_mode_admin', false);

        // Frontend-Aktionen definieren
        $isFrontendAction = \str_starts_with($actionKey, 'render_frontend_')
            || \str_starts_with($actionKey, 'api_frontend_')
            || \in_array($actionKey, [
                'render_archive', 'render_bookmarks', 'render_character_detail', 'render_character_list',
                'render_comic', 'render_403', 'render_404', 'page_imprint', 'page_privacy', 'page_project_info',
                'api_get_comic', 'api_get_transcript', 'api_submit_report', 'api_sync_bookmarks', 'api_toggle_bookmark',
            ], true);

        // Admin-Aktionen definieren
        $isAdminAction = \str_starts_with($actionKey, 'render_admin_')
            || \str_starts_with($actionKey, 'api_admin_')
            || \str_starts_with($actionKey, 'api_delete_')
            || \str_starts_with($actionKey, 'api_save_')
            || \str_starts_with($actionKey, 'api_upload_')
            || \str_starts_with($actionKey, 'api_list_')
            || \in_array($actionKey, [
                'api_crop_social_media', 'api_restore_deleted_comic', 'api_undo_comic', 'api_update_report_status',
                'api_create_backup', 'api_restore_backup', 'api_cron_backup', 'api_download_backup',
            ], true);

        // Neutrale Routen wie 'api_keep_alive' oder 'api_process_mail_queue' werden ignoriert

        $isLocked = false;

        // Strikt getrennte Logik basierend auf der Aktion
        if ($isAdminAction && $maintenanceAdmin) {
            $isLocked = true; // Adminbereich komplett gesperrt
        } elseif ($isFrontendAction && $maintenanceMode) {
            // Admins dürfen das Frontend trotz Wartungsmodus sehen und testen!
            if ($this->sessionManager->getAdminGroup() !== 'admin') {
                $isLocked = true; // Frontend für normale Nutzer gesperrt
            }
        }

        if ($isLocked) {
            if (\str_starts_with($actionKey, 'api_')) {
                // API-Aufrufe bekommen eine saubere JSON-Antwort statt HTML
                \http_response_code(503);
                \header('Content-Type: application/json; charset=utf-8');
                echo \json_encode(['success' => false, 'error' => 'System wird gewartet. Bitte versuche es in Kürze erneut.']);
                exit;
            }
            // Normale Seitenaufrufe laden die maintenance.php
            require_once \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/maintenance.php';
            exit;

        }

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
            'api_cron_backup',
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

        // MAGIC: Dynamisches Auto-Routing für ALLE APIs!
        if (\str_starts_with($relativePath, 'api/')) {
            $apiPath = \trim(\substr($relativePath, 4), '/');

            return ['action' => 'api_' . $apiPath, 'input' => $input];
        }

        if (\in_array($relativePath, ['', 'index.php', 'comic'], true)) {
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

        if (\in_array($relativePath, ['datenschutz', 'datenschutz.php', 'datenschutzerklaerung.php'], true)) {
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

        // Route für den Klick auf den E-Mail-Ändern Link
        if ($relativePath === 'email-bestaetigen' || $relativePath === 'email-bestaetigen.php') {
            return ['action' => 'render_frontend_verify_email', 'input' => $input];
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
