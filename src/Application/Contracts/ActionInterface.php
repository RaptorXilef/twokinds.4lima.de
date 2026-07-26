<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\Http\ServerRequest;

/**
 * Interface für alle ausführbaren Action-Klassen (Single Action Controller).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface ActionInterface
{
    /**
     * Führt die definierte Aktion aus.
     *
     * @return mixed Statusmeldung oder Ergebnis der Ausführung.
     */
    public function execute(ServerRequest $request): mixed;
}
