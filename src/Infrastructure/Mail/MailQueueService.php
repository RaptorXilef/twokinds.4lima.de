<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Core\Entity\MailJob;
use DateTimeImmutable;
use Exception;

final readonly class MailQueueService implements MailServiceInterface
{
    public function __construct(
        private MailQueueRepositoryInterface $repository,
        private MailServiceInterface $realMailService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool
    {
        // Prio-Mapping: Wichtige System-Mails drängeln sich nach vorne
        $priority = 10; // Standard für Newsletter
        if (\in_array($template, ['verify_account', 'forgot_password'], true)) {
            $priority = 100;
        } elseif ($template === 'report_resolved') {
            $priority = 50;
        }

        $job = new MailJob(
            \uniqid('mq_'),
            $recipient,
            $subject,
            $template, // String!
            $data,
            0,
            $priority, // Prio übergeben
            new DateTimeImmutable(),
        );
        $this->repository->enqueue($job);

        return true;
    }

    public function processQueue(int $limit = 5, array $allowedTemplates = []): int
    {
        return $this->repository->processBatch($limit, function (string $rec, string $sub, string $tpl, array $dat): void {
            /** @var array<string, mixed> $dat */
            $result = $this->realMailService->sendTemplate($rec, $sub, $tpl, $dat);
            if ($result !== true) {
                throw new Exception((string) $result);
            }
        }, $allowedTemplates);
    }
}
