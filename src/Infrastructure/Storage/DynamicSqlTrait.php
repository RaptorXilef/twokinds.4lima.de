<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

trait DynamicSqlTrait
{
    /**
     * Generiert und führt ein dynamisches UPSERT (Insert on Duplicate Key Update) aus.
     *
     * @param string $table Der Name der Tabelle.
     * @param array<string, mixed> $data Assoziatives Array mit Spaltennamen als Keys und den entsprechenden Werten.
     * @param array<int, string> $excludeUpdate Array mit Spaltennamen, die beim UPDATE ignoriert werden.
     */
    protected function executeUpsert(string $table, array $data, array $excludeUpdate = ['id']): bool
    {
        if ($data === []) {
            return false;
        }

        $columns = \array_keys($data);

        // 1. INSERT-Teil aufbauen (`col1`, `col2`) VALUES (:col1, :col2)
        $colString = \implode(', ', \array_map(fn (string $col): string => "`$col`", $columns));
        $valString = \implode(', ', \array_map(fn (string $col): string => ":$col", $columns));

        // 2. UPDATE-Teil aufbauen, exklusive der geschützten Spalten
        $updateCols = \array_filter($columns, fn (string $col): bool => !\in_array($col, $excludeUpdate, true));
        $updString = \implode(
            ', ',
            \array_map(fn (string $col): string => "`$col` = VALUES(`$col`)", $updateCols),
        );

        $sql = "INSERT INTO `$table` ($colString) VALUES ($valString)";
        if ($updString !== '' && $updString !== '0') {
            $sql .= " ON DUPLICATE KEY UPDATE $updString";
        }

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($data);
    }
}
