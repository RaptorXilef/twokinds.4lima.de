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
    /**
     * Erstellt und konfiguriert die PDO-Instanz.
     *
     * @param ConfigInterface $config Die Systemkonfiguration.
     *
     * @return PDO Die aktive Verbindung.
     */
    public static function create(ConfigInterface $config): PDO
    {
        $dbRaw = $config->get('database', []);
        $db = \is_array($dbRaw) ? $dbRaw : [];

        $enabled = $db['enabled'] ?? false;
        if ($enabled !== true) {
            throw new RuntimeException(
                'Kritischer Fehler: Die Datenbank ist in der config/storage.php nicht aktiviert (enabled = false) oder nicht konfiguriert.', // phpcs:ignore Generic.Files.LineLength.TooLong
            );
        }

        $port = $db['port'] ?? '';
        $portStr = \is_scalar($port) && (string) $port !== '' ? ';port=' . $port : '';

        $host = \is_string($db['host'] ?? null) ? $db['host'] : 'localhost';
        $dbname = \is_string($db['dbname'] ?? null) ? $db['dbname'] : '';
        $charset = \is_string($db['charset'] ?? null) ? $db['charset'] : 'utf8mb4';
        $user = \is_string($db['user'] ?? null) ? $db['user'] : '';
        $pass = \is_string($db['pass'] ?? null) ? $db['pass'] : '';

        $dsnWithDb = "mysql:host={$host}{$portStr};dbname={$dbname};charset={$charset}";

        $pdo = null;

        try {
            $pdo = new PDO($dsnWithDb, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 2,
            ]);
        } catch (PDOException $e) {
            $mysqlErrorCode = $e->errorInfo[1] ?? null;

            // 1049 = Unknown database (Datenbank existiert noch nicht)
            if ($mysqlErrorCode !== 1049) {
                throw new RuntimeException(
                    'MySQL Verbindungsfehler: ' . $e->getMessage(),
                    (int) $e->getCode(),
                    $e,
                );
            }

            // Datenbank existiert nicht, versuche sie anzulegen
            $dsnWithoutDb = "mysql:host={$host}{$portStr};charset={$charset}";

            try {
                $pdo = new PDO($dsnWithoutDb, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2,
                ]);

                $sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci"; // phpcs:ignore Generic.Files.LineLength.TooLong
                $pdo->exec($sql);
                $pdo->exec("USE `{$dbname}`");
            } catch (PDOException $e2) {
                throw new RuntimeException(
                    'MySQL Auto-Install Fehler (DB Create): ' . $e2->getMessage(),
                    (int) $e->getCode(),
                    $e,
                );
            }
        }

        // --- INTELLIGENTE SELBST-REPARATUR ---
        $schemaRaw = $config->get('db_schema', []);
        $schema = \is_array($schemaRaw) ? $schemaRaw : [];
        $missingTables = false;

        try {
            // Lade eine Liste aller aktuell existierenden Tabellen
            $stmt = $pdo->query('SHOW TABLES');
            if ($stmt !== false) {
                /** @var array<int, string> $existingTables */
                $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Vergleiche sie mit dem Schema
                foreach (\array_keys($schema) as $requiredTable) {
                    if (!\in_array((string) $requiredTable, $existingTables, true)) {
                        $missingTables = true;
                        break; // Sobald eine fehlt, lösen wir die Reparatur aus
                    }
                }
            } else {
                $missingTables = true;
            }
        } catch (PDOException) {
            // Falls SHOW TABLES fehlschlägt, gehen wir auf Nummer sicher
            $missingTables = true;
        }

        if ($missingTables) {
            foreach ($schema as $tableName => $sql) {
                try {
                    if (\is_string($sql)) {
                        $pdo->exec($sql); // CREATE TABLE IF NOT EXISTS ist sicher mehrfach auszuführen
                    }
                } catch (PDOException $ex) {
                    throw new RuntimeException(
                        'MySQL Auto-Install Fehler (Tabelle ' . $tableName . ' konnte nicht angelegt werden): ' . $ex->getMessage(), // phpcs:ignore Generic.Files.LineLength.TooLong
                        (int) $ex->getCode(),
                        $ex,
                    );
                }
            }
        }

        return $pdo;
    }
}
