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
        // Weil wir TemplateKey ausgebaut haben, job->template ist jetzt ein string!
        $templateStr = \is_string($job->template) ? $job->template : ($job->template->value ?? 'std');
        $data        = [
            'id'         => $job->id,
            'recipient'  => $job->recipient,
            'subject'    => $job->subject,
            'template'   => $templateStr,
            'data'       => \json_encode($job->data, \JSON_UNESCAPED_UNICODE),
            'attempts'   => $job->attempts,
            'created_at' => $job->createdAt->format('Y-m-d H:i:s'),
        ];

        $sql = 'REPLACE INTO `mail_queue` (id, recipient, subject, template, data, attempts, created_at)
                VALUES (:id, :recipient, :subject, :template, :data, :attempts, :created_at)';
        $this->pdo->prepare($sql)->execute($data);
    }

    public function processBatch(int $limit, callable $processor, array $allowedTemplates = []): int
    {
        $sentCount    = 0;
        $lockAcquired = $this->pdo->query("SELECT GET_LOCK('tk_mail_queue', 2)")->fetchColumn();
        if (! $lockAcquired) {
            return 0;
        }

        $templateFilterSql = '';
        $params            = [];

        // Newsletter herausfiltern oder gezielt zulassen
        if (! empty($allowedTemplates)) {
            $inQuery           = \implode(',', \array_fill(0, \count($allowedTemplates), '?'));
            $templateFilterSql = " AND template IN ($inQuery)";
            $params            = $allowedTemplates;
        }

        try {
            $this->pdo->exec("UPDATE `mail_queue` SET attempts = attempts + 100 WHERE attempts < 3 {$templateFilterSql} ORDER BY created_at ASC LIMIT {$limit}");
            $stmt = $this->pdo->prepare("SELECT * FROM `mail_queue` WHERE attempts >= 100 {$templateFilterSql} ORDER BY created_at ASC");
            $stmt->execute($params);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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
