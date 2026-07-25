<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\MailJob;

final readonly class MySqlMailQueueRepository implements MailQueueRepositoryInterface
{
    public function __construct(private \PDO $pdo, private JsonHelperInterface $jsonHelper)
    {
    }

    public function enqueue(MailJob $job): void
    {
        $data = ['id' => $job->id, 'recipient' => $job->recipient, 'subject' => $job->subject, 'template' => $job->template->value, 'data' => \json_encode($job->data, \JSON_UNESCAPED_UNICODE), 'attempts' => $job->attempts, 'created_at' => $job->createdAt->format('Y-m-d H:i:s')];
        $this->pdo->prepare('REPLACE INTO `mail_queue` (id, recipient, subject, template, data, attempts, created_at) VALUES (:id, :recipient, :subject, :template, :data, :attempts, :created_at)')->execute($data);
    }

    public function processBatch(int $limit, callable $processor): int
    {
        $sentCount    = 0;
        $lockAcquired = $this->pdo->query("SELECT GET_LOCK('tk_mail_queue', 2)")->fetchColumn();
        if (! $lockAcquired) {
            return 0;
        }

        try {
            $this->pdo->exec("UPDATE `mail_queue` SET attempts = attempts + 100 WHERE attempts < 3 ORDER BY created_at ASC LIMIT {$limit}");
            $items = $this->pdo->query('SELECT * FROM `mail_queue` WHERE attempts >= 100 ORDER BY created_at ASC')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                try {
                    $processor($item['recipient'], $item['subject'], $item['template'], $this->jsonHelper->decode((string) $item['data']));
                    $this->pdo->prepare('DELETE FROM `mail_queue` WHERE id = ?')->execute([$item['id']]);
                    ++$sentCount;
                } catch (\Throwable $t) {
                    $origAttempts = $item['attempts'] - 100 + 1;
                    if ($origAttempts >= 3) {
                        $this->pdo->prepare('DELETE FROM `mail_queue` WHERE id = ?')->execute([$item['id']]);
                    } else {
                        $this->pdo->prepare('UPDATE `mail_queue` SET attempts = ? WHERE id = ?')->execute([$origAttempts, $item['id']]);
                    }
                }
            }
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('tk_mail_queue')");
        }

        return $sentCount;
    }

    public function import(array $data): void
    {
    }
}
