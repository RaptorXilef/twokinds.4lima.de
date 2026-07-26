<?php

declare(strict_types=1);

namespace App\Application\Contracts;

/**
 * TODO DOCBLOCK
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface RequiresPermissionInterface
{
    /**
     * Gibt den Berechtigungs-Schlüssel zurück, der für die Ausführung dieser Action benötigt wird.
     * Beispiel: 'dashboard.vouchers.remove'
     */
    public function getRequiredPermission(): string;
}
