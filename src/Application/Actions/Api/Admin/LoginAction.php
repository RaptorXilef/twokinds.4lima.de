<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Security\Sanitizer;
use App\Core\Service\AuthService;

#[Route('POST', '/api/admin_login')]
final readonly class LoginAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $username    = Sanitizer::string($request->post['username'] ?? '');
        $passwordRaw = $request->post['password'] ?? '';
        $password    = \is_scalar($passwordRaw) ? (string) $passwordRaw : '';
        $ip          = $request->getIp();

        if ($username === '' || $password === '') {
            return JsonResponse::error('Bitte Benutzername und Passwort eingeben.', 400);
        }

        try {
            if ($this->auth->login($username, $password, $ip)) {
                $role  = $this->auth->getRole();
                $label = $this->auth->getUsername();

                // Dynamische Weiterleitung: Admin/Backdoor ins Dashboard, User zu den Lesezeichen
                $target = $role === 'admin' || $label === 'Systembetreuer' || $label === 'System-Inhaber' ? 'admin' : 'lesezeichen';

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
