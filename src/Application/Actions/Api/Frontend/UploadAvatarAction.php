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
use App\Core\Entity\User;

#[Route('POST', '/api/upload_avatar')]
#[RequiresAuth]
final readonly class UploadAvatarAction implements ActionInterface
{
    public function __construct(
        private SessionManager $sessionManager,
        private UserRepositoryInterface $userRepository,
        private ConfigInterface $config,
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
            if (! $user instanceof User) {
                return JsonResponse::error('Benutzer nicht gefunden.', 404);
            }

            if (! isset($request->files['avatar_file']) || $request->files['avatar_file']['error'] !== \UPLOAD_ERR_OK) {
                return JsonResponse::error('Keine Datei oder fehlerhafter Upload.', 400);
            }

            $tmpFile = $request->files['avatar_file']['tmp_name'];
            $info    = @\getimagesize($tmpFile);
            if (! $info) {
                return JsonResponse::error('Die hochgeladene Datei ist kein gültiges Bild.', 400);
            }

            // Zielordner anlegen falls nicht vorhanden
            $targetDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public/assets/images/avatars';
            if (! \is_dir($targetDir)) {
                @\mkdir($targetDir, 0o755, true);
            }

            // Neues Bild erstellen und in 400x400 erzwingen (falls Cropper leicht abweicht)
            $srcImage = match ($info[2]) {
                \IMAGETYPE_JPEG => @\imagecreatefromjpeg($tmpFile),
                \IMAGETYPE_PNG  => @\imagecreatefrompng($tmpFile),
                \IMAGETYPE_WEBP => @\imagecreatefromwebp($tmpFile),
                default         => false,
            };

            if (! $srcImage) {
                return JsonResponse::error('Nicht unterstütztes Bildformat.', 400);
            }

            $finalSize   = 400;
            $targetImage = \imagecreatetruecolor($finalSize, $finalSize);

            // Transparenz für WebP erhalten (falls Quellbild PNG mit transparentem Hintergrund war)
            \imagealphablending($targetImage, false);
            \imagesavealpha($targetImage, true);
            $transparent = \imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            \imagefilledrectangle($targetImage, 0, 0, $finalSize, $finalSize, $transparent);

            \imagecopyresampled($targetImage, $srcImage, 0, 0, 0, 0, $finalSize, $finalSize, $info[0], $info[1]);

            // Alten Avatar löschen, falls existent
            if ($user->avatarUrl !== null && \file_exists($targetDir . '/' . $user->avatarUrl)) {
                @\unlink($targetDir . '/' . $user->avatarUrl);
            }

            // Neue Datei generieren & speichern
            $newFilename = $userId . '_' . \time() . '.webp';
            $success     = \imagewebp($targetImage, $targetDir . '/' . $newFilename, 75); // 75% Qualität für extrem kleine Dateigröße

            if (! $success) {
                return JsonResponse::error('Fehler beim Konvertieren und Speichern des Bildes.', 500);
            }

            // User updaten
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
                $newFilename,
                $user->bio,
                $user->socialLinks,
                $user->publicBookmarks,
            );
            $this->userRepository->save($updatedUser);

            return JsonResponse::success([
                'message'        => 'Profilbild erfolgreich aktualisiert!',
                'new_avatar_url' => $this->config->getBaseUrl() . '/assets/images/avatars/' . $newFilename,
            ]);

        } catch (\Throwable $e) {
            \error_log('Avatar Upload Fatal Error: ' . $e->getMessage());

            return JsonResponse::error('Interner Serverfehler: ' . $e->getMessage(), 500);
        }
    }
}
