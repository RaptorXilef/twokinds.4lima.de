<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\System;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\System\DatabaseMigratorInterface;
use App\Contracts\System\RouteCacheInterface;
use Throwable;

#[Route('GET', '/api/system_update')]
final readonly class SystemUpdateAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private RouteCacheInterface $routeCache,
        private DatabaseMigratorInterface $migrator,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        $tokenRaw = $request->get['token'] ?? '';
        $providedToken = \is_string($tokenRaw) ? $tokenRaw : '';

        $cronRaw = $this->config->get('cron_secret', '');
        $expectedToken = \is_string($cronRaw) ? $cronRaw : '';

        if ($expectedToken === '' || $providedToken !== $expectedToken) {
            return JsonResponse::error('Unautorisiert. Ungültiges Deployment-Token.', 403);
        }

        try {
            // 1. Routing-Cache restlos leeren (via Interface statt nativ)
            $this->routeCache->clearAll();

            // 2. Migrationen triggern. Da DatabaseMigrator Interface aus dem DI Container
            // geladen wird, zwingt uns das ohnehin PDO einmal zu instanziieren und
            // die Check/Repair Schema Routine auszulösen!
            $migrationsCount = $this->migrator->migrate();

            return JsonResponse::success([
                'message' => 'System-Update erfolgreich! Cache geleert & DB-Schema geprüft.',
                'migrations_applied' => $migrationsCount,
            ]);
        } catch (Throwable $e) {
            return JsonResponse::error('Fehler beim System-Update: ' . $e->getMessage(), 500);
        }
    }
}
