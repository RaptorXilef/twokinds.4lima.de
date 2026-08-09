<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\BackupServiceInterface;
use App\Contracts\System\JsonHelperInterface;
use Exception;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

final readonly class SystemBackupService implements BackupServiceInterface
{
    private string $backupDir;

    public function __construct(
        private PDO $pdo,
        private ConfigInterface $config,
        private JsonHelperInterface $jsonHelper,
    ) {
        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';

        $this->backupDir = \rtrim($rootStr, '/\\') . '/var/backups';
        if (\is_dir($this->backupDir)) {
            return;
        }

        @\mkdir($this->backupDir, 0o777, true);
        // Ordner vor direktem Web-Zugriff schützen
        @\file_put_contents($this->backupDir . '/.htaccess', "Order allow,deny\nDeny from all\n");
    }

    public function createBackup(?string $tableName = null): string
    {
        $tables = $tableName !== null && $tableName !== '' ? [$tableName] : $this->getAllTables();
        $type = $tableName !== null && $tableName !== '' ? "table_{$tableName}" : 'full';

        $backupData = [
            'timestamp' => \date('Y-m-d H:i:s'),
            'type' => $type,
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SELECT * FROM `$table`");
            if ($stmt !== false) {
                $backupData['tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $backupData['tables'][$table] = [];
            }
        }

        // Wir nutzen kein PRETTY_PRINT mehr, um die Dateigröße im ZIP maximal klein zu halten
        $json = \json_encode($backupData, \JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('JSON encode Fehler beim Backup.');
        }

        $filename = 'backup_' . \date('Ymd_His') . '_' . $type . '.zip';
        $filepath = $this->backupDir . '/' . $filename;

        // Phase 2: ZIP Kompression & Verschlüsselung
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Konnte ZIP-Datei nicht erstellen: $filepath");
        }

        $zip->addFromString('data.json', $json);

        // Meta-Daten unverschlüsselt in den ZIP-Kommentar schreiben (Für blitzschnelles Auflisten im Dashboard)
        $meta = ['type' => $type, 'tables' => \array_keys($backupData['tables'])];
        $commentRaw = \json_encode($meta);
        $zip->setArchiveComment(\is_string($commentRaw) ? $commentRaw : '');

        // Passwortschutz anwenden, falls konfiguriert
        $backupCfgRaw = $this->config->get('backup', []);
        $backupCfg = \is_array($backupCfgRaw) ? $backupCfgRaw : [];
        $passwordRaw = $backupCfg['zip_password'] ?? '';
        $password = \is_scalar($passwordRaw) ? (string) $passwordRaw : '';

        if ($password !== '') {
            $zip->setPassword($password);
            $zip->setEncryptionName('data.json', ZipArchive::EM_AES_256);
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
        if (!\file_exists($filepath)) {
            throw new RuntimeException('Backup-Datei nicht gefunden.');
        }

        if (\str_ends_with($filename, '.zip')) {
            $zip = new ZipArchive();
            if ($zip->open($filepath) !== true) {
                throw new RuntimeException('Konnte ZIP-Backup nicht öffnen.');
            }

            // Nutze Formular-Passwort, ansonsten Fallback auf Config
            $backupCfgRaw = $this->config->get('backup', []);
            $backupCfg = \is_array($backupCfgRaw) ? $backupCfgRaw : [];
            $configPasswordRaw = $backupCfg['zip_password'] ?? '';
            $configPassword = \is_scalar($configPasswordRaw) ? (string) $configPasswordRaw : '';

            if ($customPassword !== null && $customPassword !== '') {
                $zip->setPassword($customPassword);
            } elseif ($configPassword !== '') {
                $zip->setPassword($configPassword);
            }

            // Holt die Datei direkt in den Arbeitsspeicher (Entschlüsselt automatisch, wenn PW stimmt)
            $json = $zip->getFromName('data.json');
            $zip->close();

            if ($json === false) {
                throw new RuntimeException('Fehler beim Entschlüsseln. Falsches Passwort? Bitte gib das korrekte Passwort für dieses alte Backup ein.');
            }
            $data = $this->jsonHelper->decode($json);
        } else {
            // Legacy Unterstützung für alte unverschlüsselte JSON-Backups
            $data = $this->jsonHelper->read($filepath);
        }

        if (!isset($data['tables']) || !\is_array($data['tables'])) {
            throw new RuntimeException('Ungültiges Backup-Format.');
        }

        // Lade alle existierenden Tabellen einmal vorab, um den SHOW TABLES Bug in PDO zu vermeiden
        $allExistingTables = $this->getAllTables();

        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');

            foreach ($data['tables'] as $table => $rows) {
                if (!\is_string($table)) {
                    continue;
                }

                if ($tableName !== null && $table !== $tableName) {
                    continue; // Nur die gewählte Tabelle wiederherstellen
                }

                // Sicherer Check ohne ? Placeholder bei SHOW Kommandos
                if (!\in_array($table, $allExistingTables, true)) {
                    continue;
                }

                if (!\is_array($rows)) {
                    continue;
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

                if ($validRows === []) {
                    if ($mode === 2) {
                        // Exakte Kopie (Wenn Backup leer ist, Tabelle leeren)
                        $this->pdo->exec("TRUNCATE TABLE `$table`");
                    }

                    continue;
                }

                $primaryKeys = $this->getPrimaryKeys($table);

                // Daten, die im Backup fehlen, auf dem Server löschen
                if ($mode === 2) {
                    $this->deleteMissingRecords($table, $validRows, $primaryKeys);
                }

                $firstRow = $validRows[0] ?? null;
                if (!\is_array($firstRow)) {
                    continue;
                }

                $columns = \array_keys($firstRow);
                $colNames = \implode(', ', \array_map(fn (int|string $c): string => "`$c`", $columns));
                $placeholders = \implode(', ', \array_map(fn (int|string $c): string => ":$c", $columns));

                if ($mode === 3) {
                    // Nur fehlende ergänzen
                    $sql = "INSERT IGNORE INTO `$table` ($colNames) VALUES ($placeholders)";
                    $stmt = $this->pdo->prepare($sql);
                    foreach ($validRows as $row) {
                        $stmt->execute($row);
                    }
                } else {
                    // Migrieren (Einfügen + Update)
                    $updateCols = [];
                    foreach ($columns as $c) {
                        $updateCols[] = "`$c` = VALUES(`$c`)";
                    }
                    $updateSql = \implode(', ', $updateCols);
                    $sql = "INSERT INTO `$table` ($colNames) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateSql";
                    $stmt = $this->pdo->prepare($sql);
                    foreach ($validRows as $row) {
                        $stmt->execute($row);
                    }
                }
            }

            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            throw $e;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $primaryKeys
     */
    private function deleteMissingRecords(string $table, array $rows, array $primaryKeys): void
    {
        if (\count($primaryKeys) === 1) {
            $pk = $primaryKeys[0];
            $pkStr = \is_scalar($pk) ? (string) $pk : '';

            $keptIds = [];
            foreach ($rows as $r) {
                if (!\is_array($r)) {
                    continue;
                }
                if (!isset($r[$pkStr])) {
                    continue;
                }
                if (!\is_scalar($r[$pkStr])) {
                    continue;
                }
                $keptIds[] = (string) $r[$pkStr];
            }

            if ($keptIds === []) {
                $this->pdo->exec("TRUNCATE TABLE `$table`");

                return;
            }

            $inStr = \str_repeat('?,', \count($keptIds) - 1) . '?';
            $stmt = $this->pdo->prepare("DELETE FROM `$table` WHERE `$pkStr` NOT IN ($inStr)");
            $stmt->execute($keptIds); // array_values entfernt, da es bereits eine non-empty-list ist
        } else {
            // Composite Key (Oder keine Keys) - Hier ist Truncate der sicherste Weg für eine 1:1 Kopie
            $this->pdo->exec("TRUNCATE TABLE `$table`");
        }
    }

    public function listBackups(): array
    {
        if (!\is_dir($this->backupDir)) {
            return [];
        }

        $scan = \scandir($this->backupDir);
        $files = $scan !== false ? \array_diff($scan, ['.', '..', '.htaccess']) : [];
        $backups = [];

        foreach ($files as $file) {
            $path = $this->backupDir . '/' . $file;
            $size = \filesize($path);
            $date = \filemtime($path);

            if (\str_ends_with($file, '.zip')) {
                $zip = new ZipArchive();
                if ($zip->open($path) === true) {
                    // Liest NUR den unverschlüsselten Meta-Kommentar
                    $comment = $zip->getArchiveComment();
                    $zip->close();

                    $meta = \is_string($comment) ? \json_decode($comment, true) : [];
                    $metaArray = \is_array($meta) ? $meta : [];

                    $typeRaw = $metaArray['type'] ?? 'Unbekannt';
                    $typeStr = \is_scalar($typeRaw) ? (string) $typeRaw : 'Unbekannt';

                    $tablesRaw = $metaArray['tables'] ?? [];
                    $tablesArr = \is_array($tablesRaw) ? $tablesRaw : [];

                    $backups[] = [
                        'filename' => $file,
                        'size' => $size,
                        'date' => $date,
                        'type' => $typeStr,
                        'tables' => $tablesArr,
                    ];
                }
            } elseif (\str_ends_with($file, '.json')) {
                // Legacy .json Dateien
                $data = $this->jsonHelper->read($path);

                $typeRaw = $data['type'] ?? 'unknown';
                $typeStr = \is_scalar($typeRaw) ? (string) $typeRaw : 'unknown';

                $tablesRaw = $data['tables'] ?? [];
                $tablesArr = \is_array($tablesRaw) ? \array_keys($tablesRaw) : [];

                $backups[] = [
                    'filename' => $file,
                    'size' => $size,
                    'date' => $date,
                    'type' => $typeStr,
                    'tables' => $tablesArr,
                ];
            }
        }

        \usort($backups, fn (array $a, array $b): int => ($b['date'] ?? 0) <=> ($a['date'] ?? 0));

        return $backups;
    }

    public function deleteBackup(string $filename): void
    {
        $filepath = $this->backupDir . '/' . \basename($filename);
        if (!\file_exists($filepath)) {
            return;
        }

        @\unlink($filepath);
    }

    /**
     * @return array<int, string>
     */
    public function getAllTables(): array
    {
        $stmt = $this->pdo->query('SHOW TABLES');
        if ($stmt === false) {
            return [];
        }

        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        /** @var array<int, string> $validTables */
        $validTables = [];
        if (\is_array($tables)) {
            foreach ($tables as $t) {
                if (!\is_string($t)) {
                    continue;
                }

                $validTables[] = $t;
            }
        }

        return $validTables;
    }

    /**
     * @return array<int, string>
     */
    private function getPrimaryKeys(string $table): array
    {
        $stmt = $this->pdo->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!\is_array($rows)) {
            return [];
        }

        $keys = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }
            if (!isset($row['Column_name'])) {
                continue;
            }
            if (!\is_string($row['Column_name'])) {
                continue;
            }
            $keys[] = $row['Column_name'];
        }

        return $keys;
    }

    // =========================================================================
    // Off-Site FTP Upload & Local Cleanup
    // =========================================================================

    private function uploadToFtp(string $filepath, string $filename): void
    {
        $backupCfgRaw = $this->config->get('backup', []);
        $backupCfg = \is_array($backupCfgRaw) ? $backupCfgRaw : [];
        $ftpCfgRaw = $backupCfg['ftp'] ?? [];
        $ftpCfg = \is_array($ftpCfgRaw) ? $ftpCfgRaw : [];

        $enabled = $ftpCfg['enabled'] ?? false;
        if ($enabled !== true) {
            return;
        }

        $hostRaw = $ftpCfg['host'] ?? '';
        $host = \is_scalar($hostRaw) ? (string) $hostRaw : '';
        if ($host === '') {
            return;
        }

        $portRaw = $ftpCfg['port'] ?? 21;
        $port = \is_numeric($portRaw) ? (int) $portRaw : 21;

        $userRaw = $ftpCfg['user'] ?? '';
        $user = \is_scalar($userRaw) ? (string) $userRaw : '';

        $passRaw = $ftpCfg['pass'] ?? '';
        $pass = \is_scalar($passRaw) ? (string) $passRaw : '';

        $pathRaw = $ftpCfg['path'] ?? '';
        $path = \is_scalar($pathRaw) ? (string) $pathRaw : '';
        $path = \rtrim($path, '/\\') . '/';

        $ssl = ($ftpCfg['ssl'] ?? false) === true;

        // Großzügiger Timeout (60 Sekunden), damit schlafende HDDs (z.B. FritzNAS) Zeit zum Aufwachen haben
        $timeout = 60;

        try {
            // Verbindungsaufbau (SSL/FTPS falls aktiviert)
            $connId = $ssl ? @\ftp_ssl_connect($host, $port, $timeout) : @\ftp_connect($host, $port, $timeout);
            if ($connId === false) {
                throw new RuntimeException("Verbindung fehlgeschlagen (Timeout nach {$timeout}s).");
            }

            if (@\ftp_login($connId, $user, $pass) === false) {
                throw new RuntimeException('Login fehlgeschlagen.');
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
                if (@\ftp_chdir($connId, $part)) {
                    continue;
                }

                @\ftp_mkdir($connId, $part);
                @\ftp_chdir($connId, $part);
            }

            // Upload
            if (@\ftp_put($connId, $filename, $filepath, \FTP_BINARY) === false) {
                throw new RuntimeException('Upload verweigert.');
            }

            @\ftp_close($connId);
        } catch (Throwable $e) {
            // Wir lassen den Haupt-Cronjob nicht crashen, nur weil der FTP-Server zickt.
            // Stattdessen loggen wir es stillschweigend.
            \error_log('Off-Site Backup (FTP) fehlgeschlagen: ' . $e->getMessage());
        }
    }

    private function cleanupOldBackups(): void
    {
        $backupCfgRaw = $this->config->get('backup', []);
        $backupCfg = \is_array($backupCfgRaw) ? $backupCfgRaw : [];

        $daysRaw = $backupCfg['retention_days'] ?? 365;
        $days = \is_numeric($daysRaw) ? (int) $daysRaw : 365;

        if ($days <= 0) {
            return; // 0 = Niemals löschen
        }

        $threshold = \time() - ($days * 86400); // 86400 Sekunden = 1 Tag
        $scan = \scandir($this->backupDir);
        $files = $scan !== false ? \array_diff($scan, ['.', '..', '.htaccess']) : [];

        foreach ($files as $file) {
            if (!\str_ends_with($file, '.zip') && !\str_ends_with($file, '.json')) {
                continue;
            }

            $path = $this->backupDir . '/' . $file;
            $mtime = \filemtime($path);
            if ($mtime !== false && $mtime >= $threshold) {
                continue;
            }

            @\unlink($path);
        }
    }

    public function getBackupContent(string $filename): ?string
    {
        $filepath = $this->backupDir . '/' . \basename($filename);
        if (\file_exists($filepath)) {
            $content = \file_get_contents($filepath);

            return $content !== false ? $content : null;
        }

        return null;
    }
}
