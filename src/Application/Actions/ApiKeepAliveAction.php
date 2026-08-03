<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;

#[ActionRoute('api_keep_alive')]
final readonly class ApiKeepAliveAction implements ActionInterface
{
    public function execute(ServerRequest $request): mixed
    {
        // Da dieser Endpunkt durch die AuthMiddleware geschützt ist,
        // wird die Sitzung im SessionManager automatisch bei jedem Aufruf
        // validiert und die $_SESSION['last_activity'] aktualisiert.
        // Wir müssen hier also nichts weiter tun, als "OK" zurückzusenden.
        return JsonResponse::success(['message' => 'Sitzung verlängert.']);
    }
}
