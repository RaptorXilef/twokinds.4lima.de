<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Storage\ReportRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\Report;
use App\Core\Entity\User;
use App\Core\Service\AuthService;
use App\Core\Service\ReportService;
use App\Core\ValueObject\ReportId;
use Throwable;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[Route('POST', '/api/update_report_status')]
#[RequiresAuth]
final readonly class UpdateReportStatusAction implements ActionInterface
{
    public function __construct(
        private AuthService $auth,
        private ReportService $reportService,
        private ReportRepositoryInterface $reportRepository,
        private UserRepositoryInterface $userRepository,
        private MailServiceInterface $mailService,
        private ConfigInterface $config,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (!$this->auth->isLoggedIn()) {
            return JsonResponse::error('Unautorisiert.', 401);
        }

        try {
            $idRaw = $request->post['report_id'] ?? '';
            $id = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';

            $statusRaw = $request->post['status'] ?? '';
            $status = \is_scalar($statusRaw) ? \trim((string) $statusRaw) : '';

            // Präzise Rechteprüfung je nach Aktion
            if ($status === 'spam' && !$this->auth->hasPermission('reports.delete')) {
                return JsonResponse::error('Zugriff verweigert. Fehlendes Recht: reports.delete', 403);
            }

            if (\in_array($status, ['open', 'closed'], true) && !$this->auth->hasPermission('reports.resolve')) {
                return JsonResponse::error('Zugriff verweigert. Fehlendes Recht: reports.resolve', 403);
            }

            if ($id === '' || !\in_array($status, ['open', 'closed', 'spam'], true)) {
                return JsonResponse::error('Ungültige Daten übermittelt.', 400);
            }

            $reportIdObj = new ReportId($id);
            $this->reportService->updateReportStatus($reportIdObj, $status);

            if ($status === 'closed') {
                $this->notifyUserIfClosed($reportIdObj);
            }

            return JsonResponse::success(['message' => 'Status erfolgreich aktualisiert.']);
        } catch (Throwable $e) {
            return JsonResponse::error('Fehler beim Aktualisieren: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPER
    // =========================================================================

    private function notifyUserIfClosed(ReportId $reportIdObj): void
    {
        $report = $this->reportRepository->findById($reportIdObj);

        if (!$report instanceof Report || $report->userId === null) {
            return;
        }

        $user = $this->userRepository->findById($report->userId);

        // Nur senden, wenn User existiert und Benachrichtigungen wünscht
        if (!$user instanceof User || !$user->wantsNotificationReport) {
            return;
        }

        $comicIdVal = $report->comicId->value ?? '';
        $pageUrl = \in_array($comicIdVal, ['', '0'], true)
            ? \rtrim($this->config->getBaseUrl(), '/')
            : \rtrim($this->config->getBaseUrl(), '/') . '/comics/' . $comicIdVal;

        $this->mailService->sendTemplate(
            $user->email->value,
            'Dein Fehlerbericht wurde bearbeitet!',
            'report_resolved',
            [
                'username' => $user->username->value,
                'comicId' => $comicIdVal !== '' ? $comicIdVal : 'Allgemeine Webseite',
                'pageUrl' => $pageUrl,
            ],
        );
    }
}
