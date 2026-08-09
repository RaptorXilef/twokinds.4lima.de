<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Contracts\Config\ConfigInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Factory zur Erstellung der zentralen PDO-Datenbankverbindung.
 *
 * Kapselt die komplexe Logik des Verbindungsaufbaus, das automatische Anlegen
 * fehlender Datenbanken (Auto-Setup) und das Ausrollen des initialen Tabellenschemas.
 */
final class PdoFactory
{
    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Erstellt und konfiguriert die PDO-Instanz.
     */
    public static function create(ConfigInterface $config): PDO
    {
        $dbRaw = $config->get('database', []);
        $db = \is_array($dbRaw) ? $dbRaw : [];

        $enabled = $db['enabled'] ?? false;
        if ($enabled !== true) {
            throw new RuntimeException(
                'Kritischer Fehler: Datenbank in config/storage.php nicht aktiviert (enabled = false).',
            );
        }

        $port = $db['port'] ?? '';
        $portStr = \is_scalar($port) && (string) $port !== '' ? ';port=' . $port : '';

        $host = \is_string($db['host'] ?? null) ? $db['host'] : 'localhost';
        $dbname = \is_string($db['dbname'] ?? null) ? $db['dbname'] : '';
        $charset = \is_string($db['charset'] ?? null) ? $db['charset'] : 'utf8mb4';
        $user = \is_string($db['user'] ?? null) ? $db['user'] : '';
        $pass = \is_string($db['pass'] ?? null) ? $db['pass'] : '';

        $pdo = self::connectToDatabase($host, $portStr, $dbname, $charset, $user, $pass);

        self::verifyAndRepairSchema($pdo, $config);

        return $pdo;
    }

    // =========================================================================
    // PRIVATE HELPER
    // =========================================================================

    private static function connectToDatabase(
        string $host,
        string $portStr,
        string $dbname,
        string $charset,
        string $user,
        string $pass,
    ): PDO {
        $dsnWithDb = "mysql:host={$host}{$portStr};dbname={$dbname};charset={$charset}";

        try {
            return new PDO($dsnWithDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 2,
            ]);
        } catch (PDOException $e) {
            $mysqlErrorCode = $e->errorInfo[1] ?? null;

            // 1049 = Unknown database (Datenbank existiert noch nicht)
            if ($mysqlErrorCode !== 1049) {
                throw new RuntimeException('MySQL Verbindungsfehler: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }

            return self::createDatabaseAndConnect($host, $portStr, $dbname, $charset, $user, $pass);
        }
    }

    private static function createDatabaseAndConnect(
        string $host,
        string $portStr,
        string $dbname,
        string $charset,
        string $user,
        string $pass,
    ): PDO {
        $dsnWithoutDb = "mysql:host={$host}{$portStr};charset={$charset}";

        try {
            $pdo = new PDO($dsnWithoutDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 2,
            ]);

            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci";
            $pdo->exec($sql);
            $pdo->exec("USE `{$dbname}`");

            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'MySQL Auto-Install Fehler (DB Create): ' . $e->getMessage(),
                (int) $e->getCode(),
                $e,
            );
        }
    }

    private static function verifyAndRepairSchema(PDO $pdo, ConfigInterface $config): void
    {
        $schemaRaw = $config->get('db_schema', []);

        /** @var array<string, mixed> $schema */
        $schema = \is_array($schemaRaw) ? $schemaRaw : [];

        $missingTables = self::checkMissingTables($pdo, $schema);

        if (!$missingTables) {
            return;
        }

        foreach ($schema as $tableName => $sql) {
            try {
                if (\is_string($sql)) {
                    $pdo->exec($sql); // CREATE TABLE IF NOT EXISTS ist sicher mehrfach auszuführen
                }
            } catch (PDOException $ex) {
                throw new RuntimeException(
                    "MySQL Auto-Install Fehler (Tabelle {$tableName} fehlgeschlagen): " . $ex->getMessage(),
                    (int) $ex->getCode(),
                    $ex,
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private static function checkMissingTables(PDO $pdo, array $schema): bool
    {
        try {
            $stmt = $pdo->query('SHOW TABLES');
            if ($stmt === false) {
                return true;
            }

            /** @var array<int, string> $existingTables */
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach (\array_keys($schema) as $requiredTable) {
                if (!\in_array((string) $requiredTable, $existingTables, true)) {
                    return true;
                }
            }

            return false;
        } catch (PDOException) {
            return true;
        }
    }
}
