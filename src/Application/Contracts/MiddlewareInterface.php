<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Application\Http\ServerRequest;

/**
 * Interface für alle HTTP-Middlewares (Türsteher).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
interface MiddlewareInterface
{
    /**
     * Verarbeitet den Request und entscheidet, ob er an die nächste Schicht weitergegeben wird.
     *
     * @param callable $next Die nächste Middleware oder die finale Action.
     */
    public function process(ServerRequest $request, callable $next): mixed;
}
