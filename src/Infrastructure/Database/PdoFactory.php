<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Contracts\Config\ConfigInterface;

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
     * @return \PDO|null Die aktive Verbindung oder null, wenn MySQL deaktiviert ist oder fehlschlägt.
     */
    public static function create(ConfigInterface $config): \PDO
    {
        $db = $config->get('database', []);

        if (! isset($db['enabled']) || $db['enabled'] === false) {
            throw new \RuntimeException('Kritischer Fehler: Die Datenbank ist in der config/storage.php nicht aktiviert (enabled = false) oder nicht konfiguriert.');
        }

        $portStr   = ! empty($db['port']) ? ";port={$db['port']}" : '';
        $dsnWithDb = "mysql:host={$db['host']}{$portStr};dbname={$db['dbname']};charset={$db['charset']}";

        $pdo = null;

        try {
            // ACHTUNG: Hier KEIN "return" mehr! Wir speichern die Verbindung nur in $pdo,
            // damit das Script danach die Tabellen-Prüfung ausführt.
            $pdo = new \PDO($dsnWithDb, $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_TIMEOUT            => 2,
            ]);
        } catch (\PDOException $e) {
            $mysqlErrorCode = $e->errorInfo[1] ?? null;

            // 1049 = Unknown database (Datenbank existiert noch nicht)
            if ($mysqlErrorCode === 1049) {
                $dsnWithoutDb = "mysql:host={$db['host']}{$portStr};charset={$db['charset']}";

                try {
                    $pdo = new \PDO($dsnWithoutDb, $db['user'], $db['pass'], [
                        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES   => false,
                        \PDO::ATTR_TIMEOUT            => 2,
                    ]);

                    $sql = "CREATE DATABASE IF NOT EXISTS `{$db['dbname']}` CHARACTER SET {$db['charset']} COLLATE {$db['charset']}_unicode_ci";
                    $pdo->exec($sql);
                    $pdo->exec("USE `{$db['dbname']}`");
                } catch (\PDOException $e2) {
                    throw new \RuntimeException('MySQL Auto-Install Fehler (DB Create): ' . $e2->getMessage());
                }
            } else {
                throw new \RuntimeException('MySQL Verbindungsfehler: ' . $e->getMessage());
            }
        }

        // Korrekte Prüfung auf TwoKinds-Tabellen (nicht mehr "users")
        try {
            $pdo->query('SELECT 1 FROM `comics` LIMIT 1');
        } catch (\PDOException) {
            // Wenn die Tabelle fehlt, bügeln wir das Schema drüber
            $schema = $config->get('db_schema', []);
            foreach ($schema as $tableName => $sql) {
                try {
                    $pdo->exec($sql);
                } catch (\PDOException $ex) {
                    throw new \RuntimeException("MySQL Auto-Install Fehler (Tabelle $tableName konnte nicht angelegt werden): " . $ex->getMessage());
                }
            }
        }

        return $pdo;
    }
}
