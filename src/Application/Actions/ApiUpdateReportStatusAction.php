<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Service\ReportService;
use App\Core\ValueObject\ReportId;

#[ActionRoute('api_update_report_status')]
final readonly class ApiUpdateReportStatusAction implements ActionInterface
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $id     = \trim((string) ($request->post['report_id'] ?? ''));
            $status = \trim((string) ($request->post['status'] ?? ''));

            if ($id === '' || ! \in_array($status, ['open', 'closed', 'spam'], true)) {
                return JsonResponse::error('Ungültige Daten übermittelt.', 400);
            }

            $this->reportService->updateReportStatus(new ReportId($id), $status);

            return JsonResponse::success(['message' => 'Status erfolgreich aktualisiert.']);
        } catch (\Throwable $e) {
            return JsonResponse::error('Fehler beim Aktualisieren: ' . $e->getMessage(), 500);
        }
    }
}
