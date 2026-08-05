<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\JsonHelperInterface;

final readonly class BackupService
{
    private string $backupDir;

    public function __construct(
        private \PDO $pdo,
        private ConfigInterface $config,
        private JsonHelperInterface $jsonHelper,
    ) {
        $this->backupDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/var/backups';
        if (! \is_dir($this->backupDir)) {
            @\mkdir($this->backupDir, 0o777, true);
            // Ordner vor direktem Web-Zugriff schützen
            @\file_put_contents($this->backupDir . '/.htaccess', "Order allow,deny\nDeny from all\n");
        }
    }

    public function createBackup(?string $tableName = null): string
    {
        $tables     = $tableName ? [$tableName] : $this->getAllTables();
        $backupData = [
            'timestamp' => \date('Y-m-d H:i:s'),
            'type'      => $tableName ? "table_{$tableName}" : 'full',
            'tables'    => [],
        ];

        foreach ($tables as $table) {
            $stmt                         = $this->pdo->query("SELECT * FROM `$table`");
            $backupData['tables'][$table] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $filename = 'backup_' . \date('Ymd_His') . '_' . $backupData['type'] . '.json';
        $filepath = $this->backupDir . '/' . $filename;

        $json = \json_encode($backupData, \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('JSON encode Fehler.');
        }

        if (@\file_put_contents($filepath, $json) === false) {
            throw new \RuntimeException("Konnte Backup-Datei nicht schreiben: $filepath");
        }

        return $filename;
    }

    public function restoreBackup(string $filename, int $mode, ?string $tableName = null): void
    {
        $filepath = $this->backupDir . '/' . \basename($filename);
        if (! \file_exists($filepath)) {
            throw new \RuntimeException('Backup-Datei nicht gefunden.');
        }

        $data = $this->jsonHelper->read($filepath);
        if (! isset($data['tables'])) {
            throw new \RuntimeException('Ungültiges Backup-Format.');
        }

        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            foreach ($data['tables'] as $table => $rows) {
                if ($tableName !== null && $table !== $tableName) {
                    continue; // Nur die gewählte Tabelle wiederherstellen
                }

                // Prüfen ob Tabelle noch existiert
                $checkTable = $this->pdo->prepare('SHOW TABLES LIKE ?');
                $checkTable->execute([$table]);
                if ($checkTable->rowCount() === 0) {
                    continue;
                }

                if (empty($rows)) {
                    if ($mode === 2) {
                        // 2.2 Exakte Kopie (Wenn Backup leer ist, Tabelle leeren)
                        $this->pdo->exec("TRUNCATE TABLE `$table`");
                    }

                    continue;
                }

                $primaryKeys = $this->getPrimaryKeys($table);

                // 2.2 Daten, die im Backup fehlen, auf dem Server löschen
                if ($mode === 2) {
                    $this->deleteMissingRecords($table, $rows, $primaryKeys);
                }

                $columns      = \array_keys($rows[0]);
                $colNames     = \implode(', ', \array_map(fn ($c) => "`$c`", $columns));
                $placeholders = \implode(', ', \array_map(fn ($c) => ":$c", $columns));

                if ($mode === 3) {
                    // 2.3 Nur fehlende ergänzen (Lücken füllen)
                    $sql  = "INSERT IGNORE INTO `$table` ($colNames) VALUES ($placeholders)";
                    $stmt = $this->pdo->prepare($sql);
                    foreach ($rows as $row) {
                        $stmt->execute($row);
                    }
                } else {
                    // 2.1 Migrieren (Standard) ODER 2.2 Exakte Kopie (Hier wird Insert/Update gemacht)
                    $updateCols = [];
                    foreach ($columns as $c) {
                        $updateCols[] = "`$c` = VALUES(`$c`)";
                    }
                    $updateSql = \implode(', ', $updateCols);
                    $sql       = "INSERT INTO `$table` ($colNames) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";

                    $stmt = $this->pdo->prepare($sql);
                    foreach ($rows as $row) {
                        $stmt->execute($row);
                    }
                }
            }

            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            throw clone $e;
        }
    }

    private function deleteMissingRecords(string $table, array $rows, array $primaryKeys): void
    {
        if (\count($primaryKeys) === 1) {
            $pk      = $primaryKeys[0];
            $keptIds = \array_map(fn ($r) => (string) $r[$pk], $rows);

            if (empty($keptIds)) {
                $this->pdo->exec("TRUNCATE TABLE `$table`");

                return;
            }
            $inStr = \str_repeat('?,', \count($keptIds) - 1) . '?';
            $stmt  = $this->pdo->prepare("DELETE FROM `$table` WHERE `$pk` NOT IN ($inStr)");
            $stmt->execute(\array_values($keptIds));
        } else {
            // Composite Key (Oder keine Keys) - Hier ist Truncate der sicherste Weg für eine 1:1 Kopie
            $this->pdo->exec("TRUNCATE TABLE `$table`");
        }
    }

    public function listBackups(): array
    {
        if (! \is_dir($this->backupDir)) {
            return [];
        }

        $files   = \array_diff(\scandir($this->backupDir), ['.', '..', '.htaccess']);
        $backups = [];

        foreach ($files as $file) {
            if (\str_ends_with($file, '.json')) {
                $path      = $this->backupDir . '/' . $file;
                $data      = $this->jsonHelper->read($path);
                $backups[] = [
                    'filename' => $file,
                    'size'     => \filesize($path),
                    'date'     => \filemtime($path),
                    'type'     => $data['type'] ?? 'unknown',
                    'tables'   => isset($data['tables']) ? \array_keys($data['tables']) : [],
                ];
            }
        }

        \usort($backups, fn ($a, $b) => $b['date'] <=> $a['date']);

        return $backups;
    }

    public function deleteBackup(string $filename): void
    {
        $filepath = $this->backupDir . '/' . \basename($filename);
        if (\file_exists($filepath)) {
            \unlink($filepath);
        }
    }

    public function getAllTables(): array
    {
        $stmt = $this->pdo->query('SHOW TABLES');

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getPrimaryKeys(string $table): array
    {
        $stmt = $this->pdo->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");

        return \array_map(fn ($k) => $k['Column_name'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }
}
