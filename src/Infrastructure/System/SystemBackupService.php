<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\BackupServiceInterface;
use App\Contracts\System\JsonHelperInterface;

final readonly class SystemBackupService implements BackupServiceInterface
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

        // Wir nutzen kein PRETTY_PRINT mehr, um die Dateigröße im ZIP maximal klein zu halten
        $json = \json_encode($backupData, \JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('JSON encode Fehler beim Backup.');
        }

        $type     = $backupData['type'];
        $filename = 'backup_' . \date('Ymd_His') . '_' . $type . '.zip';
        $filepath = $this->backupDir . '/' . $filename;

        // Phase 2: ZIP Kompression & Verschlüsselung
        $zip = new \ZipArchive();
        if ($zip->open($filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Konnte ZIP-Datei nicht erstellen: $filepath");
        }

        $zip->addFromString('data.json', $json);

        // Meta-Daten unverschlüsselt in den ZIP-Kommentar schreiben (Für blitzschnelles Auflisten im Dashboard)
        $meta = ['type' => $type, 'tables' => \array_keys($backupData['tables'])];
        $zip->setArchiveComment(\json_encode($meta));

        // Passwortschutz anwenden, falls konfiguriert
        $backupCfg = (array) $this->config->get('backup', []);
        $password  = (string) ($backupCfg['zip_password'] ?? '');
        if ($password !== '') {
            $zip->setPassword($password);
            $zip->setEncryptionName('data.json', \ZipArchive::EM_AES_256);
        }

        $zip->close();

        // Phase 3 & 4: Externe Kopie hochladen und lokalen Ordner aufräumen
        $this->uploadToFtp($filepath, $filename);
        $this->cleanupOldBackups();

        return $filename;
    }

    public function restoreBackup(string $filename, int $mode, ?string $tableName = null, ?string $customPassword = null): void
    {
        $filepath = $this->backupDir . '/' . \basename($filename);
        if (! \file_exists($filepath)) {
            throw new \RuntimeException('Backup-Datei nicht gefunden.');
        }

        if (\str_ends_with($filename, '.zip')) {
            $zip = new \ZipArchive();
            if ($zip->open($filepath) !== true) {
                throw new \RuntimeException('Konnte ZIP-Backup nicht öffnen.');
            }

            // Nutze Formular-Passwort, ansonsten Fallback auf Config
            $backupCfg      = (array) $this->config->get('backup', []);
            $configPassword = (string) ($backupCfg['zip_password'] ?? '');

            if ($customPassword !== null && $customPassword !== '') {
                $zip->setPassword($customPassword);
            } elseif ($configPassword !== '') {
                $zip->setPassword($configPassword);
            }

            // Holt die Datei direkt in den Arbeitsspeicher (Entschlüsselt automatisch, wenn PW stimmt)
            $json = $zip->getFromName('data.json');
            $zip->close();

            if ($json === false) {
                throw new \RuntimeException('Fehler beim Entschlüsseln. Falsches Passwort? Bitte gib das korrekte Passwort für dieses alte Backup ein.');
            }
            $data = $this->jsonHelper->decode($json);
        } else {
            // Legacy Unterstützung für alte unverschlüsselte JSON-Backups
            $data = $this->jsonHelper->read($filepath);
        }

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
                        // Exakte Kopie (Wenn Backup leer ist, Tabelle leeren)
                        $this->pdo->exec("TRUNCATE TABLE `$table`");
                    }

                    continue;
                }

                $primaryKeys = $this->getPrimaryKeys($table);

                // Daten, die im Backup fehlen, auf dem Server löschen
                if ($mode === 2) {
                    $this->deleteMissingRecords($table, $rows, $primaryKeys);
                }

                $columns      = \array_keys($rows[0]);
                $colNames     = \implode(', ', \array_map(fn ($c) => "`$c`", $columns));
                $placeholders = \implode(', ', \array_map(fn ($c) => ":$c", $columns));

                if ($mode === 3) {
                    // Nur fehlende ergänzen
                    $sql  = "INSERT IGNORE INTO `$table` ($colNames) VALUES ($placeholders)";
                    $stmt = $this->pdo->prepare($sql);
                    foreach ($rows as $row) {
                        $stmt->execute($row);
                    }
                } else {
                    // Migrieren (Einfügen + Update)
                    $updateCols = [];
                    foreach ($columns as $c) {
                        $updateCols[] = "`$c` = VALUES(`$c`)";
                    }
                    $updateSql = \implode(', ', $updateCols);
                    $sql       = "INSERT INTO `$table` ($colNames) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
                    $stmt      = $this->pdo->prepare($sql);
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
            $path = $this->backupDir . '/' . $file;
            $size = \filesize($path);
            $date = \filemtime($path);

            if (\str_ends_with($file, '.zip')) {
                $zip = new \ZipArchive();
                if ($zip->open($path) === true) {
                    // Liest NUR den unverschlüsselten Meta-Kommentar
                    $comment = $zip->getArchiveComment();
                    $zip->close();

                    $meta      = $comment ? \json_decode($comment, true) : [];
                    $backups[] = [
                        'filename' => $file,
                        'size'     => $size,
                        'date'     => $date,
                        'type'     => $meta['type'] ?? 'Unbekannt',
                        'tables'   => $meta['tables'] ?? [],
                    ];
                }
            } elseif (\str_ends_with($file, '.json')) {
                // Legacy .json Dateien
                $data      = $this->jsonHelper->read($path);
                $backups[] = [
                    'filename' => $file,
                    'size'     => $size,
                    'date'     => $date,
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

    // =========================================================================
    // Off-Site FTP Upload & Local Cleanup
    // =========================================================================
    private function uploadToFtp(string $filepath, string $filename): void
    {
        $backupCfg = (array) $this->config->get('backup', []);
        $ftpCfg    = $backupCfg['ftp'] ?? [];

        if (empty($ftpCfg['enabled']) || empty($ftpCfg['host'])) {
            return;
        }

        $host    = $ftpCfg['host'];
        $port    = (int) ($ftpCfg['port'] ?? 21);
        $user    = $ftpCfg['user'] ?? '';
        $pass    = $ftpCfg['pass'] ?? '';
        $path    = \rtrim($ftpCfg['path'] ?? '', '/\\') . '/';
        $ssl     = ! empty($ftpCfg['ssl']);
        $timeout = 60;

        try {
            $connId = $ssl ? @\ftp_ssl_connect($host, $port, $timeout) : @\ftp_connect($host, $port, $timeout);
            if (! $connId) {
                throw new \RuntimeException("Verbindung fehlgeschlagen (Timeout nach {$timeout}s).");
            }

            if (! @\ftp_login($connId, $user, $pass)) {
                throw new \RuntimeException('Login fehlgeschlagen.');
            }

            // Sicherstellen, dass die Verbindung während großer Uploads oder Wartezeiten nicht abbricht
            @\ftp_set_option($connId, \FTP_TIMEOUT_SEC, $timeout);

            // Passive mode ist essentiell für Router/Firewalls (wie die FritzBox!)
            @\ftp_pasv($connId, true);

            // Verzeichnisse iterativ erstellen, falls sie nicht existieren
            $parts = \explode('/', \trim($path, '/'));
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                if (! @\ftp_chdir($connId, $part)) {
                    @\ftp_mkdir($connId, $part);
                    @\ftp_chdir($connId, $part);
                }
            }

            // Upload
            if (! @\ftp_put($connId, $filename, $filepath, \FTP_BINARY)) {
                throw new \RuntimeException('Upload verweigert.');
            }

            @\ftp_close($connId);
        } catch (\Throwable $e) {
            // Wir lassen den Haupt-Cronjob nicht crashen, nur weil der FTP-Server zickt.
            // Stattdessen loggen wir es stillschweigend.
            \error_log('Off-Site Backup (FTP) fehlgeschlagen: ' . $e->getMessage());
        }
    }

    private function cleanupOldBackups(): void
    {
        $backupCfg = (array) $this->config->get('backup', []);
        $days      = (int) ($backupCfg['retention_days'] ?? 365);

        if ($days <= 0) {
            return; // 0 = Niemals löschen
        }

        $threshold = \time() - ($days * 86400); // 86400 Sekunden = 1 Tag
        $files     = \array_diff(\scandir($this->backupDir), ['.', '..', '.htaccess']);

        foreach ($files as $file) {
            if (\str_ends_with($file, '.zip') || \str_ends_with($file, '.json')) {
                $path = $this->backupDir . '/' . $file;
                if (\filemtime($path) < $threshold) {
                    @\unlink($path);
                }
            }
        }
    }
}
