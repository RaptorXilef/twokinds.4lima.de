<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Storage\StorageInterface;
use App\Contracts\System\JsonHelperInterface;
use App\Core\Entity\Permit;

/**
 * MySQL-Implementierung des Storage-Interfaces.
 *
 * Persistenz-Engine für relationale SQL-Datenbanken (MySQL / MariaDB).
 * Nutzt vorbereitete Statements (Prepared Statements) mit benannten Parametern zum Schutz
 * vor SQL-Injections und implementiert performante, datenbankseitige String-Säuberungen bei Suchen.
 * Kontext: Enterprise-Datenhaltungs-Backend für performante Großbetriebe.
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final readonly class MySqlStorage implements StorageInterface
{
    use StorageMapperTrait;

    public function __construct(
        private \PDO $pdo,
        private JsonHelperInterface $jsonHelper,
    ) {
    }

    // --- Public Write ---

    /**
     * Speichert oder aktualisiert eine Genehmigung via SQL-`REPLACE INTO` Statement.
     * Flacht die Objektstrukturen über das integrierte Trait ab.
     *
     * @param Permit $permit Das zu persistierende Genehmigungs-Objekt.
     *
     * @return bool True bei fehlerfreier SQL-Ausführung.
     */
    public function save(Permit $permit): bool
    {
        // 1. Array mit allen Daten holen (z. B. ['code' => 'XYZ', 'preis' => 5.0, ...])
        $data = $this->flattenEntity($permit);

        // 2. Alle Schlüsselnamen extrahieren
        $columns = \array_keys($data);

        // 3. SQL-Teile vollautomatisch generieren!
        // Aus ['code', 'preis'] wird: "`code`, `preis`"
        $colString = \implode(', ', \array_map(fn ($c) => "`$c`", $columns));

        // Aus ['code', 'preis'] wird: ":code, :preis"
        $valString = \implode(', ', \array_map(fn ($c) => ":$c", $columns));

        // Aus ['code', 'preis'] wird: "`code` = VALUES(`code`), `preis` = VALUES(`preis`)"
        $updString = \implode(', ', \array_map(fn ($c) => "`$c` = VALUES(`$c`)", $columns));

        // 4. Den fertigen SQL-String zusammensetzen
        $sql = "INSERT INTO `permits` ($colString) VALUES ($valString) ON DUPLICATE KEY UPDATE $updString";

        // 5. Ausführen (PDO ordnet die :platzhalter automatisch dem $data-Array zu)
        return $this->pdo->prepare($sql)->execute($data);
    }

    /**
     * Löscht eine Genehmigung unwiderruflich aus der MySQL-Datenbank.
     *
     * @param string $code Der eindeutige Hash/Code der Genehmigung.
     *
     * @return bool True, wenn der Datensatz erfolgreich gelöscht wurde.
     */
    public function delete(string $code): bool
    {
        // Nutze rowCount() statt dem reinen execute()-Ergebnis,
        // um den echten Lösch-Status (True/False) ans System zurückzugeben.
        $stmt = $this->pdo->prepare('DELETE FROM `permits` WHERE code = ?');
        $stmt->execute([$code]);

        return $stmt->rowCount() > 0;
    }

    // TODO DOCBLOCK
    public function deleteMultiple(array $codes): int
    {
        if (empty($codes)) {
            return 0;
        }

        $placeholders = \implode(',', \array_fill(0, \count($codes), '?'));
        $stmt         = $this->pdo->prepare("DELETE FROM `permits` WHERE code IN ($placeholders)");
        $stmt->execute(\array_values($codes));

        return $stmt->rowCount();
    }

    // --- Public Read ---

    /**
     * Ruft alle in der Tabelle `permits` hinterlegten Zeilen ab.
     *
     * @return array<int, Permit> Liste aller hydrierten Genehmigungs-Objekte.
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `permits`');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return \array_map($this->mapToEntity(...), $rows);
    }

    // --- Public Migrations ---

    /**
     * Migriert alle Datenbank-Datensätze in eine alternative Speicher-Engine.
     *
     * @param StorageInterface $target Das Ziel-Repository (z.B. JsonStorage).
     *
     * @return int Anzahl transferierter Datensätze.
     */
    public function migrateTo(StorageInterface $target): int
    {
        $count = 0;
        foreach ($this->getAll() as $permit) {
            if (! $target->save($permit)) {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    public function import(array $data): void
    {
        foreach ($data as $key => $item) {
            if (! isset($item['code'])) {
                $item['code'] = $key;
            }
            $this->save($this->mapToEntity($item));
        }
    }
}
