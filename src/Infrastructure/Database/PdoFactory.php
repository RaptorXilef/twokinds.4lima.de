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

        try {
            return new \PDO($dsnWithDb, $db['user'], $db['pass'], [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::ATTR_TIMEOUT            => 2,
            ]);
        } catch (\PDOException $e) {
            $mysqlErrorCode = $e->errorInfo[1] ?? null;

            // Wenn die DB (Fehler 1049) nicht existiert, versuchen wir sie anzulegen
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

                    return $pdo;
                } catch (\PDOException $e2) {
                    throw new \RuntimeException('MySQL Auto-Install Fehler (DB Create): ' . $e2->getMessage());
                }
            }

            throw new \RuntimeException('MySQL Verbindungsfehler: ' . $e->getMessage());
        }
    }
}
