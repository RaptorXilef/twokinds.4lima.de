<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\AuthService;

#[ActionRoute('api_admin_login')]
final readonly class ApiAdminLoginAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $username = \trim((string) ($request->post['username'] ?? ''));
        $password = (string) ($request->post['password'] ?? '');
        $ip       = $request->getIp();

        if ($username === '' || $password === '') {
            return JsonResponse::error('Bitte Benutzername und Passwort eingeben.', 400);
        }

        try {
            if ($this->auth->login($username, $password, $ip)) {
                $role = $this->auth->getRole();
                // Dynamische Weiterleitung: Admin ins Dashboard, User zu den Lesezeichen
                $target = ($role === 'admin' || $role === 'Systembetreuer') ? 'admin' : 'lesezeichen';

                return JsonResponse::success([
                    'message'  => 'Erfolgreich eingeloggt.',
                    'redirect' => $target,
                ]);
            }

            return JsonResponse::error('Ungültige Zugangsdaten.', 401);

        } catch (\DomainException $e) {
            // Fängt die "Konto nicht bestätigt" Exception aus dem AuthService!
            return JsonResponse::error($e->getMessage(), 401);
        } catch (\RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 429);
        }
    }
}
