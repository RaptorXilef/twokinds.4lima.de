<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Service\AuthService;
use App\Core\Service\ReportService;
use App\Core\ValueObject\ReportId;

#[ActionRoute('api_update_report_status')]
final readonly class ApiUpdateReportStatusAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private ReportService $reportService,
        private UserRepositoryInterface $userRepository,
        private MailServiceInterface $mailService,
        private ConfigInterface $config,
        private \PDO $pdo,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->isLoggedIn()) {
            return JsonResponse::error('Unautorisiert.', 401);
        }

        try {
            $id     = \trim((string) ($request->post['report_id'] ?? ''));
            $status = \trim((string) ($request->post['status'] ?? ''));

            // Präzise Rechteprüfung je nach Aktion
            if ($status === 'spam' && ! $this->auth->hasPermission('reports.delete')) {
                return JsonResponse::error('Zugriff verweigert. Fehlendes Recht: reports.delete', 403);
            }
            if (\in_array($status, ['open', 'closed'], true) && ! $this->auth->hasPermission('reports.resolve')) {
                return JsonResponse::error('Zugriff verweigert. Fehlendes Recht: reports.resolve', 403);
            }

            if ($id === '' || ! \in_array($status, ['open', 'closed', 'spam'], true)) {
                return JsonResponse::error('Ungültige Daten übermittelt.', 400);
            }

            // 1. Die harte Logik lagern wir wieder an deinen bewährten ReportService aus!
            $this->reportService->updateReportStatus(new ReportId($id), $status);

            // 2. Auto-E-Mail versenden, wenn der Report auf "erledigt" gesetzt wurde
            if ($status === 'closed') {

                // Wir lesen user_id (statt submitter) aus.
                $stmt = $this->pdo->prepare('SELECT comic_id, user_id FROM reports WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($row && ! empty($row['user_id'])) {
                    $user = $this->userRepository->findById($row['user_id']);

                    // Nur senden, wenn User existiert und Benachrichtigungen wünscht
                    if ($user && $user->wantsNotificationReport) {
                        $pageUrl = ! empty($row['comic_id'])
                            ? \rtrim($this->config->getBaseUrl(), '/') . '/comic/' . $row['comic_id']
                            : \rtrim($this->config->getBaseUrl(), '/');

                        $this->mailService->sendTemplate($user->email->value, 'Dein Fehlerbericht wurde bearbeitet!', 'report_resolved', [
                            'username' => $user->username->value,
                            'comicId'  => $row['comic_id'] ?: 'Allgemeine Webseite',
                            'pageUrl'  => $pageUrl,
                        ]);
                    }
                }
            }

            return JsonResponse::success(['message' => 'Status erfolgreich aktualisiert.']);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Aktualisieren: ' . $e->getMessage(), 500);
        }
    }
}
