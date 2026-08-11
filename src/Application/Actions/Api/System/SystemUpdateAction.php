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
use PDO;
use Throwable;

#[Route('GET', '/api/system_update')]
final readonly class SystemUpdateAction implements ActionInterface
{
    public function __construct(
        private ConfigInterface $config,
        private RouteCacheInterface $routeCache,
        private PDO $pdo,
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
            // 1. Routing-Cache leeren, damit neue Routen sofort aktiv werden
            $this->routeCache->clearOld();
            $cacheFile = $this->config->getStoragePath('cache/routes_v2.php');
            if (\file_exists($cacheFile)) {
                \unlink($cacheFile);
            }

            // 2. PDO wurde durch Dependency Injection bereits geladen.
            // Das triggert intern PdoFactory::verifyAndRepairSchema() für neue Tabellen.
            $this->pdo->query('SELECT 1');

            // 3. Ausstehende SQL-Migrationen (ALTER TABLE etc.) ausführen
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
