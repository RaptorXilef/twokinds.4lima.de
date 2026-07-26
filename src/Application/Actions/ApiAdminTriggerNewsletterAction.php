<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\UserRepositoryInterface;

#[ActionRoute('api_admin_trigger_newsletter')]
final readonly class ApiAdminTriggerNewsletterAction implements ActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailServiceInterface $mailService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        // Parameter aus dem Admin-Frontend abgreifen
        $type       = $request->post['type'] ?? 'full'; // 'full' oder 'transcript'
        $comicName  = \trim((string) ($request->post['comic_name'] ?? 'TwoKinds'));
        $pageNumber = \trim((string) ($request->post['page_number'] ?? ''));
        $pageUrl    = \trim((string) ($request->post['page_url'] ?? ''));

        if ($pageUrl === '' || $pageNumber === '') {
            return JsonResponse::error('Seiten-URL und Seitenzahl müssen angegeben werden.', 400);
        }

        $isTranscript = $type === 'transcript';
        $subscribers  = $this->userRepository->findNewsletterSubscribers($isTranscript);

        if (empty($subscribers)) {
            return JsonResponse::success(['message' => 'Niemand hat diesen Newsletter abonniert. Es wurden keine E-Mails versendet.']);
        }

        $template = $isTranscript ? 'newsletter_transcript' : 'newsletter_full';
        $subject  = $isTranscript
            ? "Neues Transkript verfügbar: {$comicName} - Seite {$pageNumber}"
            : "Neue Comic-Seite: {$comicName} - Seite {$pageNumber}";

        $count = 0;
        foreach ($subscribers as $user) {
            $this->mailService->sendTemplate($user->email->value, $subject, $template, [
                'username'   => $user->username->value,
                'comicName'  => $comicName,
                'pageNumber' => $pageNumber,
                'pageUrl'    => $pageUrl,
            ]);
            ++$count;
        }

        return JsonResponse::success([
            'message' => "Erfolg! {$count} E-Mails wurden für den CronJob (Priorität 10) in die Warteschlange eingereiht.",
        ]);
    }
}
