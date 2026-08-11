<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\DatabaseMigratorInterface;
use App\Contracts\Utils\ClockInterface;
use PDO;
use RuntimeException;
use Throwable;

final readonly class DatabaseMigrator implements DatabaseMigratorInterface
{
    public function __construct(
        private PDO $pdo,
        private ConfigInterface $config,
        private ClockInterface $clock,
    ) {
    }

    public function migrate(): int
    {
        $this->ensureMigrationsTableExists();

        $rootRaw = $this->config->get('root_path', '');
        $root = \is_string($rootRaw) ? $rootRaw : '';
        $migrationDir = \rtrim($root, '/\\') . '/database/migrations';

        if (!\is_dir($migrationDir)) {
            return 0;
        }

        $files = \scandir($migrationDir);
        if ($files === false) {
            return 0;
        }

        $sqlFiles = [];
        foreach ($files as $file) {
            if (\str_ends_with($file, '.sql')) {
                $sqlFiles[] = $file;
            }
        }

        \sort($sqlFiles);

        $appliedMigrations = $this->getAppliedMigrations();
        $count = 0;

        foreach ($sqlFiles as $file) {
            if (\in_array($file, $appliedMigrations, true)) {
                continue;
            }

            $filePath = $migrationDir . '/' . $file;
            $sql = \file_get_contents($filePath);

            if ($sql === false || \trim($sql) === '') {
                continue;
            }

            $this->pdo->beginTransaction();

            try {
                $this->pdo->exec($sql);

                $stmtInsert = $this->pdo->prepare('INSERT INTO `' . Table::MIGRATIONS . '` (`version`, `applied_at`) VALUES (?, ?)');
                $stmtInsert->execute([$file, $this->clock->nowAsString()]);

                $this->pdo->commit();
                ++$count;
            } catch (Throwable $e) {
                $this->pdo->rollBack();

                throw new RuntimeException("Migration fehlgeschlagen bei Datei {$file}: " . $e->getMessage(), 0, $e);
            }
        }

        return $count;
    }

    private function ensureMigrationsTableExists(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS `' . Table::MIGRATIONS . '` (
            `version` VARCHAR(255) PRIMARY KEY,
            `applied_at` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    /**
     * @return array<int, string>
     */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT `version` FROM `' . Table::MIGRATIONS . '`');
        if ($stmt === false) {
            return [];
        }

        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $validApplied = [];
        if (\is_array($applied)) {
            foreach ($applied as $version) {
                if (\is_string($version)) {
                    $validApplied[] = $version;
                }
            }
        }

        return $validApplied;
    }
}
