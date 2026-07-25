<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;

#[ActionRoute('api_process_mail_queue')]
final readonly class SystemProcessMailQueueAction implements ActionInterface
{
    public function __construct(
        private MailServiceInterface $mailService,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $providedToken     = $request->get['token'] ?? '';
        $expectedCronToken = (string) $this->config->get('cron_secret', '');

        // Darf nur aufgerufen werden, wenn der Token stimmt
        if ($expectedCronToken === '' || $providedToken !== $expectedCronToken) {
            return JsonResponse::error('Unautorisiert.', 403);
        }

        // Limit für den Cronjob (z.B. 50 Mails pro 5 Minuten)
        $limit = 50;

        // Da wir das leere Array übergeben, darf ALLES versendet werden (auch Newsletter!)
        $sent = $this->mailService->processQueue($limit, []);

        return JsonResponse::success([
            'status'     => 'processed',
            'sent_count' => $sent,
            'mode'       => 'cron',
        ]);
    }
}
