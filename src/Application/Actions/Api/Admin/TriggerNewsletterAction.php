<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use App\Core\Service\AuthService;

#[Route('POST', '/api/admin_trigger_newsletter')]
#[RequiresAuth]
final readonly class TriggerNewsletterAction implements ActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailServiceInterface $mailService,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->hasPermission('comics.edit')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        // Parameter aus dem Admin-Frontend abgreifen
        $typeRaw = $request->post['type'] ?? 'full'; // 'full' oder 'transcript'
        $type = \is_string($typeRaw) ? $typeRaw : 'full';

        $comicNameRaw = $request->post['comic_name'] ?? 'TwoKinds';
        $comicName = \is_scalar($comicNameRaw) ? \trim((string) $comicNameRaw) : 'TwoKinds';

        $pageNumberRaw = $request->post['page_number'] ?? '';
        $pageNumber = \is_scalar($pageNumberRaw) ? \trim((string) $pageNumberRaw) : '';

        $pageUrlRaw = $request->post['page_url'] ?? '';
        $pageUrl = \is_scalar($pageUrlRaw) ? \trim((string) $pageUrlRaw) : '';

        if ($pageUrl === '' || $pageNumber === '') {
            return JsonResponse::error('Seiten-URL und Seitenzahl müssen angegeben werden.', 400);
        }

        $isTranscript = $type === 'transcript';

        /** @var array<int, User> $subscribers */
        $subscribers = $this->userRepository->findNewsletterSubscribers($isTranscript);

        if ($subscribers === []) {
            return JsonResponse::success(['message' => 'Niemand hat diesen Newsletter abonniert. Es wurden keine E-Mails versendet.']);
        }

        $template = $isTranscript ? 'newsletter_transcript' : 'newsletter_full';
        $subject = $isTranscript
            ? "Neues Transkript verfügbar: {$comicName} - Seite {$pageNumber}"
            : "Neue Comic-Seite: {$comicName} - Seite {$pageNumber}";

        $count = 0;
        foreach ($subscribers as $user) {
            $this->mailService->sendTemplate($user->email->value, $subject, $template, [
                'username' => $user->username->value,
                'comicName' => $comicName,
                'pageNumber' => $pageNumber,
                'pageUrl' => $pageUrl,
            ]);
            ++$count;
        }

        return JsonResponse::success([
            'message' => "Erfolg! {$count} E-Mails wurden für den CronJob (Priorität 10) in die Warteschlange eingereiht.",
        ]);
    }
}
