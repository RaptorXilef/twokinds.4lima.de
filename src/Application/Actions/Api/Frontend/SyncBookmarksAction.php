<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Storage\BookmarkRepositoryInterface;
use App\Core\Entity\Bookmark;
use App\Core\Service\AuthService;

#[Route('POST', '/api/sync_bookmarks')]
final readonly class SyncBookmarksAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private SessionManager $sessionManager,
        private BookmarkRepositoryInterface $bookmarkRepo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->isLoggedIn()) {
            return JsonResponse::error('Nicht eingeloggt.', 401);
        }

        $userId = $this->sessionManager->getUserId();
        // Götter blocken (Sync sofort erfolgreich vortäuschen)
        if (\str_starts_with($userId, 'sys_')) {
            return JsonResponse::success(['status' => 'synced', 'final_ids' => []]);
        }

        // Lokale Lesezeichen aus dem Browser (als JSON-Array gesendet)
        $localIdsRaw = $request->post['local_ids'] ?? '[]';
        $localIdsStr = \is_scalar($localIdsRaw) ? (string) $localIdsRaw : '[]';

        $localIdsArr = \json_decode($localIdsStr, true);
        if (!\is_array($localIdsArr)) {
            $localIdsArr = [];
        }

        // SECURITY FIX: Rigorose Bereinigung (Sanitization) des User-Inputs
        $sanitizedLocalIds = [];
        foreach ($localIdsArr as $dirtyId) {
            if (!\is_scalar($dirtyId)) {
                continue;
            }

            // Akzeptiere NUR exakt 8 Ziffern mit evtl. einem Buchstaben (z.B. 20251024 oder 20251024a)
            $cleanId = \trim((string) $dirtyId);
            if (\preg_match('/^\d{8}[a-z]?$/i', $cleanId) !== 1) {
                continue;
            }

            $sanitizedLocalIds[] = $cleanId;
        }
        $localIds = $sanitizedLocalIds;

        // Was sollen wir tun? (check, merge, db_wins, local_wins)
        $resRaw = $request->post['resolution'] ?? 'check';
        $resolution = \is_scalar($resRaw) ? (string) $resRaw : 'check';

        // Cloud-Daten abrufen
        $dbBookmarks = $this->bookmarkRepo->findByUser($userId);
        $dbIds = \array_map(fn (Bookmark $bookmark): string => $bookmark->comicId, $dbBookmarks);

        // Sortieren zum einfachen Vergleichen
        $sortedLocal = $localIds;
        $sortedDb = $dbIds;
        \sort($sortedLocal);
        \sort($sortedDb);

        // Wenn beide Listen exakt gleich sind, ist alles okay (kein Sync nötig)
        if ($sortedLocal === $sortedDb) {
            return JsonResponse::success([
                'status' => 'synced',
                'final_ids' => $dbIds,
            ]);
        }

        // Wenn wir nur prüfen sollen ("check") und es gibt Unterschiede: Konflikt melden!
        if ($resolution === 'check') {
            return JsonResponse::success([
                'status' => 'conflict',
                'db_ids' => $dbIds,
                'local_ids' => $localIds,
            ]);
        }

        // --- Konfliktlösungen (ohne Else-Expression) ---

        if ($resolution === 'merge') {
            $finalIds = \array_values(\array_unique(\array_merge($localIds, $dbIds)));
            $this->bookmarkRepo->replaceUserBookmarks($userId, $finalIds);

            return JsonResponse::success(['status' => 'resolved', 'final_ids' => $finalIds]);
        }

        if ($resolution === 'local_wins') {
            $this->bookmarkRepo->replaceUserBookmarks($userId, $localIds);

            return JsonResponse::success(['status' => 'resolved', 'final_ids' => $localIds]);
        }

        if ($resolution === 'db_wins') {
            // Wir müssen an der DB nichts ändern, sie hat ja gewonnen.
            return JsonResponse::success(['status' => 'resolved', 'final_ids' => $dbIds]);
        }

        return JsonResponse::error('Ungültige Lösungsmethode.', 400);
    }
}
