<?php

declare(strict_types=1);

namespace App\Application\Actions\Api\Frontend;

use App\Application\Attribute\RequiresAuth;
use App\Application\Attribute\Route;
use App\Application\Contracts\ActionInterface;
use App\Application\Http\ServerRequest;
use App\Application\Response\JsonResponse;
use App\Application\Session\SessionManager;
use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Contracts\System\MediaServiceInterface;
use App\Core\Entity\User;
use InvalidArgumentException;
use Throwable;

#[Route('POST', '/api/upload_avatar')]
#[RequiresAuth]
final readonly class UploadAvatarAction implements ActionInterface
{
    public function __construct(
        private SessionManager $sessionManager,
        private UserRepositoryInterface $userRepository,
        private ConfigInterface $config,
        private MediaServiceInterface $mediaService,
    ) {
    }

    public function execute(ServerRequest $request): mixed
    {
        try {
            $userId = $this->sessionManager->getUserId();
            if (\str_starts_with($userId, 'sys_')) {
                return JsonResponse::error('System-Accounts können keine Avatare hochladen.', 403);
            }

            $user = $this->userRepository->findById($userId);
            if (!$user instanceof User) {
                return JsonResponse::error('Benutzer nicht gefunden.', 404);
            }

            $file = $request->files['avatar_file'] ?? null;
            if (!\is_array($file) || !isset($file['error']) || $file['error'] !== \UPLOAD_ERR_OK) {
                return JsonResponse::error('Keine Datei oder fehlerhafter Upload.', 400);
            }

            /** @var array<string, mixed> $validFile */
            $validFile = [];
            foreach ($file as $k => $v) {
                $validFile[(string) $k] = $v;
            }

            // Gesamte GD/Verzeichnis Logik delegiert!
            $newFilename = $this->mediaService->processAvatarUpload($userId, $user->avatarUrl, $validFile);

            $updatedUser = new User(
                $user->id,
                $user->username,
                $user->email,
                $user->passwordHash,
                $user->roleId,
                $user->createdAt,
                $user->wantsNewsletter,
                $user->wantsNewsletterTranscript,
                $user->wantsNotificationReport,
                $newFilename, // Das neue Bild eintragen
                $user->bio,
                $user->socialLinks,
                $user->publicBookmarks,
            );
            $this->userRepository->save($updatedUser);

            return JsonResponse::success([
                'message' => 'Profilbild erfolgreich aktualisiert!',
                'new_avatar_url' => $this->config->getBaseUrl() . '/assets/images/avatars/' . $newFilename,
            ]);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (Throwable $e) {
            \error_log('Avatar Upload Fatal Error: ' . $e->getMessage());

            return JsonResponse::error('Interner Serverfehler: ' . $e->getMessage(), 500);
        }
    }
}
