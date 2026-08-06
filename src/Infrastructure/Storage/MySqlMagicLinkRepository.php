<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\MagicLinkRepositoryInterface;
use App\Core\Entity\MagicLink;
use App\Core\ValueObject\EmailAddress;
use App\Infrastructure\Database\Table;

final readonly class MySqlMagicLinkRepository implements MagicLinkRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function loadAll(): array
    {
        $links = [];
        $stmt  = $this->pdo->query('SELECT * FROM `' . Table::MAGIC_LINKS . '`');

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $dt = \is_numeric($r['expires'])
                ? (new \DateTimeImmutable())->setTimestamp((int) $r['expires'])
                : new \DateTimeImmutable($r['expires']);

            $links[$r['token']] = new MagicLink(
                $r['token'],
                new EmailAddress($r['email']),
                $r['code'],
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
                    'token'   => $token,
                    'email'   => $link->email->value,
                    'code'    => $link->code,
                    'expires' => $link->expires->format('Y-m-d H:i:s'),
                ];
                $this->pdo->prepare('REPLACE INTO `' . Table::MAGIC_LINKS . '` (token, email, code, expires) VALUES (:token, :email, :code, :expires)')->execute($data);
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
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
