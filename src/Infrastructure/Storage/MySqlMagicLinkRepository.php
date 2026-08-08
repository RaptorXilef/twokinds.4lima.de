<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Core\Entity\MagicLink;
use App\Core\ValueObject\EmailAddress;
use App\Infrastructure\Database\Table;
use DateTimeImmutable;
use Exception;
use PDO;

final readonly class MySqlMagicLinkRepository implements MagicLinkRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function loadAll(): array
    {
        /** @var array<string, MagicLink> $links */
        $links = [];
        $stmt = $this->pdo->query('SELECT * FROM `' . Table::MAGIC_LINKS . '`');

        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        foreach ($rows as $r) {
            if (!\is_array($r)) {
                continue;
            }

            $expiresRaw = $r['expires'] ?? 'now';
            $dt = \is_numeric($expiresRaw)
                ? (new DateTimeImmutable())->setTimestamp((int) $expiresRaw)
                : new DateTimeImmutable((string) $expiresRaw);

            $token = \is_string($r['token'] ?? null) ? $r['token'] : '';
            $emailStr = \is_string($r['email'] ?? null) ? $r['email'] : '';
            $codeStr = \is_string($r['code'] ?? null) ? $r['code'] : '';
            if ($token === '') {
                continue;
            }
            if ($emailStr === '') {
                continue;
            }

            $links[$token] = new MagicLink(
                $token,
                new EmailAddress($emailStr),
                $codeStr,
                $dt,
            );
        }

        return $links;
    }

    public function saveAll(array $links, bool $forceSql = false): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec('DELETE FROM `' . Table::MAGIC_LINKS . '`');

            foreach ($links as $token => $link) {
                $data = [
                    'token' => (string) $token,
                    'email' => $link->email->value,
                    'code' => $link->code,
                    'expires' => $link->expires->format('Y-m-d H:i:s'),
                ];
                $this->pdo->prepare('REPLACE INTO `' . Table::MAGIC_LINKS . '` (token, email, code, expires) VALUES (:token, :email, :code, :expires)')->execute($data);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    public function import(array $data): void
    {
    }

    public function deleteExpired(): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . Table::MAGIC_LINKS . '` WHERE expires < NOW()');
        $stmt->execute();

        return $stmt->rowCount();
    }
}
