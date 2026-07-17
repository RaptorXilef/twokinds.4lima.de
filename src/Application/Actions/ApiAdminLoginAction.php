<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;

#[ActionRoute('api_admin_login')]
final readonly class ApiAdminLoginAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private SessionManager $sessionManager,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $username = \trim((string) ($request->post['username'] ?? ''));
        $password = (string) ($request->post['password'] ?? '');

        if ($username === '' || $password === '') {
            return JsonResponse::error('Bitte Benutzername und Passwort eingeben.', 400);
        }

        // Wir prüfen zuerst den Superadmin (dev_admin.php)
        $superAdmin = $this->config->get('superadmin', []);

        // Und den Backdoor-User (In app.php verankert)
        $backdoor = $this->config->get('backdoor', []);

        $isAuthenticated = false;
        $userLabel       = 'Unbekannt';

        if (isset($superAdmin['user'], $superAdmin['pass']) && $username === $superAdmin['user'] && $password === $superAdmin['pass']) {
            $isAuthenticated = true;
            $userLabel       = $superAdmin['label'] ?? 'Systembetreuer';
        } elseif (isset($backdoor['user'], $backdoor['pass']) && $username === $backdoor['user'] && \password_verify($password, $backdoor['pass'])) {
            $isAuthenticated = true;
            $userLabel       = $backdoor['label'] ?? 'System-Inhaber';
        }

        if ($isAuthenticated) {
            $this->sessionManager->regenerate();
            // UserID "1", Group "admin", Label setzen
            $this->sessionManager->setAuthSession('1', 'admin', $userLabel);

            return JsonResponse::success(['message' => 'Erfolgreich eingeloggt.', 'redirect' => 'admin']);
        }

        return JsonResponse::error('Ungültige Zugangsdaten.', 401);
    }
}
