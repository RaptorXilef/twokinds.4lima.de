<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Attribute\ActionRoute;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SubmitReportRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Core\Exception\RateLimitExceededException;
use App\Core\Service\MediaService;
use App\Core\Service\ReportService;

#[ActionRoute('api_submit_report')]
final readonly class ApiSubmitReportAction implements ActionInterface
{
    public function __construct(
        private ReportService $reportService,
        private MediaService $mediaService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SubmitReportRequest::fromRequest($request);

            // --- Screenshot Verarbeitung (Max 1500px, WEBP) ---
            $screenshotUrl = null;
            if (isset($request->files['report_screenshot']) && $request->files['report_screenshot']['error'] === \UPLOAD_ERR_OK) {
                $file      = $request->files['report_screenshot'];
                $targetDir = __DIR__ . '/../../../public/assets/images/reports';

                if (! \is_dir($targetDir)) {
                    \mkdir($targetDir, 0o777, true);
                }

                $fileName = 'rep_' . \uniqid() . '.webp';
                if ($this->mediaService->generateScaledImage($file['tmp_name'], $targetDir . '/' . $fileName, 1500)) {
                    $screenshotUrl = $fileName;
                }
            }

            $report = $this->reportService->submitReport(
                $dto->comicId,
                $dto->ipAddress,
                $dto->submitterName,
                $dto->wantsCredit,
                $dto->reportType,
                $screenshotUrl,
                $dto->description,
                $dto->transcriptSuggestion,
                $dto->transcriptOriginal,
                $dto->debugInfo,
            );

            return JsonResponse::success(['message' => 'Vielen Dank! Deine Meldung wurde erfolgreich übermittelt.']);

        } catch (ValidationException $e) {
            if ($e->getMessage() === 'HONEYPOT_TRIGGERED') {
                // Den Bot im Glauben lassen, es hätte funktioniert
                return JsonResponse::success(['message' => 'Meldung erfolgreich übermittelt.']);
            }

            return JsonResponse::error($e->getMessage(), 400);

        } catch (RateLimitExceededException $e) {
            return JsonResponse::error($e->getMessage(), 429);

        } catch (\Throwable $e) {
            // Log-Logik greift automatisch über den GlobalExceptionHandler, wir geben nur 500 zurück
            return JsonResponse::error('Ein interner Serverfehler ist aufgetreten.', 500);
        }
    }
}
