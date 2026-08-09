<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\DTO\SubmitReportRequest;
use App\Application\Exception\ValidationException;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Exception\RateLimitExceededException;
use App\Core\Service\AuthService;
use App\Core\Service\ReportService;
use Throwable;

#[Route('POST', '/api/submit_report')]
final readonly class SubmitReportAction implements ActionInterface
{
    public function __construct(
        private ReportService $reportService,
        private MediaServiceInterface $mediaService,
        private AuthService $auth, // für Session-Check
        private SessionManager $sessionManager, // für ID
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $dto = SubmitReportRequest::fromRequest($request);

            // --- Screenshot Verarbeitung (Max 1500px, WEBP) ---
            $screenshotUrl = null;
            $file = $request->files['report_screenshot'] ?? null;

            if (\is_array($file) && isset($file['error']) && $file['error'] === \UPLOAD_ERR_OK) {
                /** @var array<string, mixed> $validFile */
                $validFile = [];
                foreach ($file as $k => $v) {
                    $validFile[(string) $k] = $v;
                }

                // Logik an Infrastruktur abgegeben
                $screenshotUrl = $this->mediaService->saveReportScreenshot($validFile);
            }

            // Wenn eingeloggt, ID auslesen
            $userId = $this->auth->isLoggedIn() ? $this->sessionManager->getUserId() : null;

            // UnusedLocalVariable behoben, indem wir das Ergebnis einfach nicht abfangen
            $this->reportService->submitReport(
                $dto->comicId,
                $userId, // An Service übergeben!
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
        } catch (Throwable $e) {
            // Log-Logik greift automatisch über den GlobalExceptionHandler, wir geben nur 500 zurück
            return JsonResponse::error('Ein interner Serverfehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
}
