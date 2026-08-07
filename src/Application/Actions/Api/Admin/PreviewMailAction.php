<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Admin;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Mail\MailLogInterface;
use App\Contracts\Storage\MailQueueRepositoryInterface;
use App\Core\Service\AuthService;

#[Route('GET', '/api/preview_mail')]
#[RequiresAuth]
final readonly class PreviewMailAction implements ActionInterface
{
    public function __construct(
        private MailQueueRepositoryInterface $mailQueueRepo,
        private MailLogInterface $mailLogRepo,
        private ConfigInterface $config,
        private AuthService $auth,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        if (! $this->auth->hasPermission('system.manage') && ! $this->auth->hasPermission('admin.access')) {
            return JsonResponse::error('Zugriff verweigert.', 403);
        }

        $idRaw = $request->get['id'] ?? '';
        $id    = \is_scalar($idRaw) ? \trim((string) $idRaw) : '';

        if ($id === '') {
            return JsonResponse::error('Keine Mail-ID angegeben.', 400);
        }

        // Zuerst in der Warteschlange suchen
        $mailData = $this->mailQueueRepo->findById($id);

        // Falls nicht in der Queue, im Log suchen
        if ($mailData === null) {
            $mailData = $this->mailLogRepo->findById($id);
        }

        if ($mailData === null) {
            return JsonResponse::error('E-Mail nicht gefunden.', 404);
        }

        $template   = \is_string($mailData['template'] ?? null) ? $mailData['template'] : '';
        $payloadRaw = $mailData['data'] ?? '{}';
        $payload    = \is_string($payloadRaw) ? \json_decode($payloadRaw, true) : $payloadRaw;

        if (! \is_array($payload)) {
            $payload = [];
        }

        $rootPath = $this->config->get('root_path');
        $rootStr  = \is_string($rootPath) ? $rootPath : '';
        $root     = \rtrim($rootStr, '/\\');

        $fullPath = $root . "/templates/emails/{$template}.phtml";

        if (! \file_exists($fullPath) || $template === '') {
            return JsonResponse::error("Template '{$template}' existiert nicht auf dem Server.", 404);
        }

        \extract($payload, \EXTR_SKIP);
        \ob_start();
        include $fullPath;
        $html = \ob_get_clean();

        // Als JSON verpacktes HTML zurückgeben, passend zum App-Standard
        return JsonResponse::success(['html' => $html]);
    }
}
