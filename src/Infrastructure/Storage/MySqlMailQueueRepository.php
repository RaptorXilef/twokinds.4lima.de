<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\MailJob;
use App\Infrastructure\Database\Table;

final readonly class MySqlMailQueueRepository implements MailQueueRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private \PDO $pdo, private JsonHelperInterface $jsonHelper)
    {
    }

    public function enqueue(MailJob $job): void
    {
        // template kann in der DB nur ein String sein
        $templateStr = \is_string($job->template) ? $job->template : ($job->template->value ?? 'std');

        $data = $this->extractEntity($job, [
            'template' => $templateStr,
        ]);

        // HIER IST DIE MAGIE: Der Trait baut das SQL völlig dynamisch!
        $this->executeUpsert(Table::MAIL_QUEUE, $data, ['id']);
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
        if ($allowedTemplates !== []) {
            $inQuery           = \implode(',', \array_fill(0, \count($allowedTemplates), '?'));
            $templateFilterSql = " AND template IN ($inQuery)";
            $params            = $allowedTemplates;
        }

        try {
            $updateSql  = 'UPDATE `' . Table::MAIL_QUEUE . "` SET attempts = attempts + 100 WHERE attempts < 3 {$templateFilterSql} ORDER BY priority DESC, created_at ASC LIMIT {$limit}";
            $stmtUpdate = $this->pdo->prepare($updateSql);
            $stmtUpdate->execute($params);

            $selectSql  = 'SELECT * FROM `' . Table::MAIL_QUEUE . "` WHERE attempts >= 100 {$templateFilterSql} ORDER BY priority DESC, created_at ASC";
            $stmtSelect = $this->pdo->prepare($selectSql);
            $stmtSelect->execute($params);
            $items = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                try {
                    $processor($item['recipient'], $item['subject'], $item['template'], $this->jsonHelper->decode((string) $item['data']));
                    $this->delete($item['id']);
                    ++$sentCount;
                } catch (\Throwable $t) {
                    \error_log("MailQueue Error [ID {$item['id']}]: " . $t->getMessage());
                    $origAttempts = $item['attempts'] - 100 + 1;
                    if ($origAttempts >= 3) {
                        $this->delete($item['id']);
                    } else {
                        $this->pdo->prepare('UPDATE `' . Table::MAIL_QUEUE . '` SET attempts = ? WHERE id = ?')->execute([$origAttempts, $item['id']]);
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

    public function findAllQueue(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::MAIL_QUEUE . '` ORDER BY created_at DESC');

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::MAIL_QUEUE . '` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function delete(string $id): void
    {
        $this->pdo->prepare('DELETE FROM `' . Table::MAIL_QUEUE . '` WHERE id = ?')->execute([$id]);
    }
}
