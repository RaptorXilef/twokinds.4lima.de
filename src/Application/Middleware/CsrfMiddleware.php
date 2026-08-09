<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Contracts\MiddlewareInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\RedirectResponse;
use App\Application\Session\SessionManager;

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionManager $sessionManager,
        private string $fallbackUrl,
    ) {
    }

    public function process(ServerRequest $request, callable $next): mixed
    {
        // CSRF greift nur bei POST-Requests!
        $method = $request->getMethod();

        if (\in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // getHeader() liefert laut Typisierung immer string.
            $headerToken = $request->getHeader('X-CSRF-Token');

            $postRaw = $request->post['csrf_token'] ?? '';
            $postToken = \is_string($postRaw) ? $postRaw : '';

            $provided = $headerToken !== '' ? $headerToken : $postToken;
            $stored = $this->sessionManager->getCsrfToken();

            if ($stored === '' || !\hash_equals($stored, $provided)) {
                // UX-Rettung: Wir speichern die eingegebenen Formulardaten zwischen,
                // bevor wir die Anfrage ablehnen.
                $postData = $request->post;
                unset($postData['csrf_token'], $postData['action']); // Interne Felder entfernen

                if ($postData !== []) {
                    $this->sessionManager->setFormData($postData);
                }

                $this->sessionManager->addFlash(
                    'error',
                    'Ihre Sitzung ist abgelaufen. '
                        . 'Zu Ihrer Sicherheit wurde die Seite neu geladen. '
                        . 'Ihre Eingaben wurden wiederhergestellt - bitte senden Sie das Formular erneut ab.',
                );

                return new RedirectResponse($this->fallbackUrl);
            }
        }

        // Alles okay! Weiter zur nächsten Schicht.
        return $next($request);
    }
}
