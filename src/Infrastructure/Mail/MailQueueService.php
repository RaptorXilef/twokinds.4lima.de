<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Core\Entity\MailJob;

final readonly class MailQueueService implements MailServiceInterface
{
    public function __construct(
        private MailQueueRepositoryInterface $repository,
        private MailServiceInterface $realMailService,
    ) {
    }

    public function sendTemplate(string $recipient, string $subject, string $template, array $data): bool|string
    {
        $job = new MailJob(
            \uniqid('mq_'),
            $recipient,
            $subject,
            $template, // String!
            $data,
            0,
            new \DateTimeImmutable(),
        );
        $this->repository->enqueue($job);

        return true;
    }

    public function processQueue(int $limit = 5, array $allowedTemplates = []): int
    {
        return $this->repository->processBatch($limit, function (string $rec, string $sub, string $tpl, array $dat): void {
            $result = $this->realMailService->sendTemplate($rec, $sub, $tpl, $dat);
            if ($result !== true) {
                throw new \Exception((string) $result);
            }
        }, $allowedTemplates);
    }
}
