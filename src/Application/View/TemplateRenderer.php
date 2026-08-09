<?php

declare(strict_types=1);

namespace App\Application\View;

use App\Application\Response\HtmlResponse;
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

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $templatePath, array $data = [], int $statusCode = 200): HtmlResponse
    {
        $rootPath = $this->config->get('root_path');
        $appRoot = \is_string($rootPath) ? \rtrim($rootPath, '/\\') : '';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $systemVars = [
            'appRoot' => $appRoot,
            'config' => $this->config,
            'imageStorage' => $this->imageStorage,
            'jsonHelper' => $this->jsonHelper,
            'settings' => $this->getGlobalSettings(),
            'asset' => $this->assetHelper, // (Steht als $asset im Template bereit)
            'isLoggedIn' => $this->sessionManager->getUserId() !== '', // Auth-Status direkt in jedes Template injizieren!
            'currentUserName' => $this->sessionManager->getAdminUser(),
            'currentUserRole' => $this->sessionManager->getAdminGroup(),
            // Darf dieser User die Lesezeichen-Cloud nutzen? (Eingeloggt + Keine System-ID)
            'canUseCloudSync' => $this->sessionManager->getUserId() !== '' && !\str_starts_with($this->sessionManager->getUserId(), 'sys_'),
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong

        if (!isset($data['flashes'])) {
            // Lade alle Flashes automatisch in die View-Daten!
            // Nutzt vorhandene Flashes oder holt sie aus der Session
            $data['flashes'] = $this->sessionManager->getFlashes();
        }

        \extract($systemVars);

        // 2. Nutzerdaten bereitstellen (EXTR_SKIP verhindert das Überschreiben der Systemvariablen!)
        \extract($data, \EXTR_SKIP);

        \ob_start();

        include $appRoot . "/templates/{$templatePath}.phtml";
        $html = (string) \ob_get_clean();

        return new HtmlResponse($html, $statusCode);
    }

    /**
     * @return array<string, string>
     */
    private function getGlobalSettings(): array
    {
        $siteTitle = $this->config->get('site_title', 'Twokinds auf Deutsch');
        $siteDesc = $this->config->get('site_description', 'Die deutsche Übersetzung des Webcomics Twokinds.');

        // Variablen für das Impressum!
        $emailUser = $this->config->get('email_user', '');
        $emailDomain = $this->config->get('email_domain', '');

        // Social Links für den Header
        $patreon = $this->config->get('social_patreon', '');
        $inkbunny = $this->config->get('social_inkbunny', '');
        $paypal = $this->config->get('social_paypal', '');
        $github = $this->config->get('social_github', '');
        $twokinds = $this->config->get('social_twokinds', '');
        $gaId = $this->config->get('google_analytics_id', '');

        return [
            'base_url' => $this->config->getBaseUrl(),
            'site_title' => \is_string($siteTitle) ? $siteTitle : 'Twokinds auf Deutsch',
            'site_description' => \is_string($siteDesc) ? $siteDesc : 'Die deutsche Übersetzung des Webcomics Twokinds.', // phpcs:ignore Generic.Files.LineLength.TooLong
            'app_version' => $this->systemInfo->getCurrentVersion(),
            'google_analytics_id' => \is_string($gaId) ? $gaId : '',
            'email_user' => \is_string($emailUser) ? $emailUser : '',
            'email_domain' => \is_string($emailDomain) ? $emailDomain : '',
            'social_patreon' => \is_string($patreon) ? $patreon : '',
            'social_inkbunny' => \is_string($inkbunny) ? $inkbunny : '',
            'social_paypal' => \is_string($paypal) ? $paypal : '',
            'social_github' => \is_string($github) ? $github : '',
            'social_twokinds' => \is_string($twokinds) ? $twokinds : '',
        ];
    }
}
