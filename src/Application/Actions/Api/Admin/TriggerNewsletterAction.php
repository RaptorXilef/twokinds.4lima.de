<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\ComicPage;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\ValueObject\ComicId;

#[Route('POST', '/api/admin_trigger_newsletter')]
#[RequiresAuth]
final readonly class TriggerNewsletterAction implements ActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MailServiceInterface $mailService,
        private AuthService $auth,
        private ComicRepositoryInterface $comicRepo,
        private ConfigInterface $config,
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
        if ($pageNumber === '') {
            return JsonResponse::error('Seitenzahl (Comic-ID) muss angegeben werden.', 400);
        }
        $baseUrl = \rtrim($this->config->getBaseUrl(), '/');
        $pageUrl = $baseUrl . '/comic/' . $pageNumber;
        $isTranscript = $type === 'transcript';

        /** @var array<int, User> $subscribers */
        $subscribers = $this->userRepository->findNewsletterSubscribers($isTranscript);

        if ($subscribers === []) {
            return JsonResponse::success([
                'message' => 'Niemand hat diesen Newsletter abonniert. Es wurden keine E-Mails versendet.',
            ]);
        }

        $template = $isTranscript ? 'newsletter_transcript' : 'newsletter_full';
        $subject = $isTranscript
            ? "Neues Transkript verfügbar: {$comicName} - Seite {$pageNumber}"
            : "Neue Comic-Seite: {$comicName} - Seite {$pageNumber}";

        // Zusatz-Daten für die E-Mail aus der Datenbank laden
        $comic = null;
        if (\preg_match('/^\d{8}[a-z]?$/i', $pageNumber) === 1) {
            $comic = $this->comicRepo->findById(new ComicId($pageNumber));
        }

        $transcriptSnippet = '';
        $comicTitle = '';
        $comicChapter = '';
        $imageUrl = '';

        if ($comic instanceof ComicPage) {
            $transcriptSnippet = \trim(\strip_tags($comic->transcript ?? ''));
            if (\mb_strlen($transcriptSnippet) > 150) {
                $transcriptSnippet = \mb_substr($transcriptSnippet, 0, 147) . '...';
            }

            $comicTitle = $comic->name;
            $comicChapter = $comic->chapterId ?? '';
            $cb = $comic->imageUpdatedAt ? '?c=' . $comic->imageUpdatedAt : '';
            $imageUrl = "{$baseUrl}/assets/images/comics/social/{$comic->id->value}.jpg{$cb}";
        }

        $count = 0;
        foreach ($subscribers as $user) {
            $this->mailService->sendTemplate(
                $user->email->value,
                $subject,
                $template,
                [
                    'username' => $user->username->value,
                    'comicName' => $comicName,
                    'pageNumber' => $pageNumber,
                    'pageUrl' => $pageUrl,
                    'comicTitle' => $comicTitle,
                    'comicChapter' => $comicChapter,
                    'transcriptSnippet' => $transcriptSnippet,
                    'imageUrl' => $imageUrl,
                ],
            );
            ++$count;
        }

        return JsonResponse::success([
            'message' => "Erfolg! {$count} E-Mails wurden für den CronJob (Priorität 10) in die Warteschlange eingereiht.",
        ]);
    }
}
