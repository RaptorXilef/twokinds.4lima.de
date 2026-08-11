<?php

declare(strict_types=1);

namespace App\Contracts\System;

interface DatabaseMigratorInterface
{
    /**
     * Führt alle noch nicht angewendeten Migrationen aus.
     *
     * @return int Die Anzahl der erfolgreich angewendeten Migrationen.
     */
    public function migrate(): int;
}
