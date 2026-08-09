<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\MailJob;
use App\Infrastructure\Database\Table;
use PDO;
use Throwable;

final readonly class MySqlMailQueueRepository implements MailQueueRepositoryInterface
{
    use DynamicSqlTrait;
    use EntityHydratorTrait;

    public function __construct(private PDO $pdo, private JsonHelperInterface $jsonHelper)
    {
    }

    public function enqueue(MailJob $job): void
    {
        $data = $this->extractEntity($job, [
            'template' => $job->template,
        ]);

        $this->executeUpsert(Table::MAIL_QUEUE, $data, ['id']);
    }

    public function processBatch(int $limit, callable $processor, array $allowedTemplates = []): int
    {
        $sentCount = 0;

        $stmtLock = $this->pdo->query("SELECT GET_LOCK('tk_mail_queue', 2)");
        $lockAcquired = $stmtLock !== false ? $stmtLock->fetchColumn() : false;

        if (\in_array($lockAcquired, [false, 0, '0'], true)) {
            return 0;
        }

        $templateFilterSql = '';
        $params = [];

        if ($allowedTemplates !== []) {
            $inQuery = \implode(',', \array_fill(0, \count($allowedTemplates), '?'));
            $templateFilterSql = " AND template IN ($inQuery)";
            $params = $allowedTemplates;
        }

        try {
            $updateSql = 'UPDATE `' . Table::MAIL_QUEUE . '` SET attempts = attempts + 100 '
                       . "WHERE attempts < 3 {$templateFilterSql} "
                       . "ORDER BY priority DESC, created_at ASC LIMIT {$limit}";

            $stmtUpdate = $this->pdo->prepare($updateSql);
            $stmtUpdate->execute($params);

            $selectSql = 'SELECT * FROM `' . Table::MAIL_QUEUE . '` '
                       . "WHERE attempts >= 100 {$templateFilterSql} "
                       . 'ORDER BY priority DESC, created_at ASC';

            $stmtSelect = $this->pdo->prepare($selectSql);
            $stmtSelect->execute($params);

            $items = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);
            if (!\is_array($items)) {
                return 0;
            }

            foreach ($items as $item) {
                if (!\is_array($item)) {
                    continue;
                }

                if (!$this->processSingleMailJob($item, $processor)) {
                    continue;
                }

                ++$sentCount;
            }
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('tk_mail_queue')");
        }

        return $sentCount;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function processSingleMailJob(array $item, callable $processor): bool
    {
        $recipient = \is_string($item['recipient'] ?? null) ? $item['recipient'] : '';
        $subject = \is_string($item['subject'] ?? null) ? $item['subject'] : '';
        $template = \is_string($item['template'] ?? null) ? $item['template'] : '';
        $dataStr = \is_string($item['data'] ?? null) ? $item['data'] : '{}';

        $rawId = $item['id'] ?? '';
        $idStr = \is_string($rawId) ? $rawId : (\is_numeric($rawId) ? (string) $rawId : '');

        $rawAttempts = $item['attempts'] ?? 0;
        $attempts = \is_numeric($rawAttempts) ? (int) $rawAttempts : 0;

        try {
            $processor($recipient, $subject, $template, $this->jsonHelper->decode($dataStr));
            $this->delete($idStr);

            return true;
        } catch (Throwable $t) {
            \error_log("MailQueue Error [ID {$idStr}]: " . $t->getMessage());

            $origAttempts = $attempts - 100 + 1;
            if ($origAttempts >= 3) {
                $this->delete($idStr);

                return false;
            }

            $this->pdo->prepare('UPDATE `' . Table::MAIL_QUEUE . '` SET attempts = ? WHERE id = ?')
                      ->execute([$origAttempts, $idStr]);

            return false;
        }
    }

    public function import(array $data): void
    {
        unset($data); // Interface Vorgabe
    }

    public function findAllQueue(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::MAIL_QUEUE . '` ORDER BY created_at DESC');
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $validRows */
        $validRows = [];
        foreach ($rows as $r) {
            if (!\is_array($r)) {
                continue;
            }

            /** @var array<string, mixed> $validR */
            $validR = $r;
            $validRows[] = $validR;
        }

        return $validRows;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `' . Table::MAIL_QUEUE . '` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!\is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $validRow */
        $validRow = $row;

        return $validRow;
    }

    public function delete(string $id): void
    {
        $this->pdo->prepare('DELETE FROM `' . Table::MAIL_QUEUE . '` WHERE id = ?')->execute([$id]);
    }
}
