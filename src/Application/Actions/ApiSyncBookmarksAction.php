<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Core\Service\AuthService;

#[ActionRoute('api_sync_bookmarks')]
final readonly class ApiSyncBookmarksAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private SessionManager $sessionManager,
        private BookmarkRepositoryInterface $bookmarkRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->isLoggedIn()) {
            return JsonResponse::error('Nicht eingeloggt.', 401);
        }

        $userId = $this->sessionManager->getUserId();

        // Lokale Lesezeichen aus dem Browser (als JSON-Array gesendet)
        $localIdsRaw = $request->post['local_ids'] ?? '[]';
        $localIds    = \json_decode((string) $localIdsRaw, true) ?? [];
        if (! \is_array($localIds)) {
            $localIds = [];
        }

        // Was sollen wir tun? (check, merge, db_wins, local_wins)
        $resolution = $request->post['resolution'] ?? 'check';

        // Cloud-Daten abrufen
        $dbBookmarks = $this->bookmarkRepo->findByUser($userId);
        $dbIds       = \array_map(fn ($b) => $b->comicId, $dbBookmarks);

        // Sortieren zum einfachen Vergleichen
        $sortedLocal = $localIds;
        $sortedDb    = $dbIds;
        \sort($sortedLocal);
        \sort($sortedDb);

        // Wenn beide Listen exakt gleich sind, ist alles okay (kein Sync nötig)
        if ($sortedLocal === $sortedDb) {
            return JsonResponse::success([
                'status'    => 'synced',
                'final_ids' => $dbIds,
            ]);
        }

        // Wenn wir nur prüfen sollen ("check") und es Unterschiede gibt: Konflikt melden!
        if ($resolution === 'check') {
            return JsonResponse::success([
                'status'    => 'conflict',
                'db_ids'    => $dbIds,
                'local_ids' => $localIds,
            ]);
        }

        // --- Konfliktlösungen ---
        $finalIds = [];

        if ($resolution === 'merge') {
            $finalIds = \array_values(\array_unique(\array_merge($localIds, $dbIds)));
            $this->bookmarkRepo->replaceUserBookmarks($userId, $finalIds);
        } elseif ($resolution === 'local_wins') {
            $finalIds = $localIds;
            $this->bookmarkRepo->replaceUserBookmarks($userId, $finalIds);
        } elseif ($resolution === 'db_wins') {
            $finalIds = $dbIds;
        // Wir müssen an der DB nichts ändern, sie hat ja gewonnen.
        } else {
            return JsonResponse::error('Ungültige Lösungsmethode.', 400);
        }

        // Dem Browser die finale Liste zum Überschreiben seines LocalStorage schicken
        return JsonResponse::success([
            'status'    => 'resolved',
            'final_ids' => $finalIds,
        ]);
    }
}
