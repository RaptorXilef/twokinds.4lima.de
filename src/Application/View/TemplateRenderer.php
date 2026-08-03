<?php

declare(strict_types=1);

namespace App\Application\View;

use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\AssetHelperInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Contracts\System\SystemInfoInterface;

final readonly class TemplateRenderer
{
    public function __construct(
        private ConfigInterface $config,
        private ImageStorageInterface $imageStorage,
        private JsonHelperInterface $jsonHelper,
        private SessionManager $sessionManager,
        private SystemInfoInterface $systemInfo,
        private AssetHelperInterface $assetHelper,
    ) {
    }

    public function render(string $templatePath, array $data = []): void
    {
        $appRoot = \rtrim((string) $this->config->get('root_path'), '/\\');

        $systemVars = [
            'appRoot'         => $appRoot,
            'config'          => $this->config,
            'imageStorage'    => $this->imageStorage,
            'jsonHelper'      => $this->jsonHelper,
            'settings'        => $this->getGlobalSettings(),
            'asset'           => $this->assetHelper, // (Steht als $asset im Template bereit)
            'isLoggedIn'      => $this->sessionManager->getUserId() !== '', // Auth-Status direkt in jedes Template injizieren!
            'currentUserName' => $this->sessionManager->getAdminUser(),
            'currentUserRole' => $this->sessionManager->getAdminGroup(),
            // Darf dieser User die Lesezeichen-Cloud nutzen? (Eingeloggt + Keine System-ID)
            'canUseCloudSync' => $this->sessionManager->getUserId() !== '' && ! \str_starts_with($this->sessionManager->getUserId(), 'sys_'),
        ];

        // Lade alle Flashes automatisch in die View-Daten!
        // Nutzt vorhandene Flashes oder holt sie aus der Session
        $data['flashes'] ??= $this->sessionManager->getFlashes();

        \extract($systemVars);

        // 2. Nutzerdaten bereitstellen (EXTR_SKIP verhindert das Überschreiben der Systemvariablen!)
        \extract($data, \EXTR_SKIP);

        include $appRoot . "/templates/pages/{$templatePath}.phtml";
    }

    private function getGlobalSettings(): array
    {
        return [
            'base_url'            => $this->config->getBaseUrl(),
            'site_title'          => $this->config->get('site_title', 'Twokinds auf Deutsch'),
            'site_description'    => $this->config->get('site_description', 'Die deutsche Übersetzung des Webcomics Twokinds.'),
            'app_version'         => $this->systemInfo->getCurrentVersion(),
            'google_analytics_id' => $this->config->get('google_analytics_id', ''),

            // Diese Variablenn für das Impressum!
            'email_user'   => $this->config->get('email_user', ''),
            'email_domain' => $this->config->get('email_domain', ''),

            // Social Links für den Header
            'social_patreon'  => $this->config->get('social_patreon', ''),
            'social_inkbunny' => $this->config->get('social_inkbunny', ''),
            'social_paypal'   => $this->config->get('social_paypal', ''),
        ];
    }
}
