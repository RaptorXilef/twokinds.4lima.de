<?php

declare(strict_types=1);

namespace App\Application\View;

use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\ImageStorageInterface;
use App\Contracts\System\JsonHelperInterface;

/**
 * TODO DOCBLOCK
 * Zentraler Service für das Rendering von PHTML-Templates.
 * Sammelt globale System-Variablen und injiziert sie sicher in den View-Scope.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class TemplateRenderer
{
    public function __construct(
        private ConfigInterface $config,
        private ImageStorageInterface $imageStorage,
        private JsonHelperInterface $jsonHelper,
        private SessionManager $sessionManager,
    ) {
    }

    // TODO DOCBLOCK
    public function render(string $templatePath, array $data = []): void
    {
        $appRoot = \rtrim((string) $this->config->get('root_path'), '/\\');

        // 1. Systemvariablen bereitstellen
        $systemVars = [
            'appRoot'      => $appRoot,
            'config'       => $this->config,
            'imageStorage' => $this->imageStorage,
            'jsonHelper'   => $this->jsonHelper,
            'settings'     => $this->getGlobalSettings(),
        ];

        // Lade alle Flashes automatisch in die View-Daten!
        // Nutzt vorhandene Flashes oder holt sie aus der Session
        $data['flashes'] ??= $this->sessionManager->getFlashes();

        \extract($systemVars);

        // 2. Nutzerdaten bereitstellen (EXTR_SKIP verhindert das Überschreiben der Systemvariablen!)
        \extract($data, \EXTR_SKIP);

        include $appRoot . "/templates/pages/{$templatePath}.phtml";
    }

    // TODO DOCBLOCK
    private function getGlobalSettings(): array
    {
        $templates = (array) $this->config->get('permit_templates', []);

        return [
            'base_url'           => $this->config->getBaseUrl(),
            'bic'                => $this->config->get('bic'),
            'iban'               => $this->config->get('iban'),
            'jahresFarbe'        => $this->config->get('jahresFarbe'),
            'kontoinhaber'       => $this->config->get('kontoinhaber'),
            'opening_hours'      => $this->config->get('default_opening_hours'),
            'public_templates'   => \array_filter($templates, fn (array $t): bool => ($t['public'] ?? false) === true),
            'purposes'           => $this->config->get('purposes'),
            'terminkalender_url' => $this->config->get('terminkalender_url'),
            'vehicle_types'      => $this->config->get('vehicle_types'),
            'vereins_name'       => $this->config->get('vereins_name'),
        ];
    }
}
