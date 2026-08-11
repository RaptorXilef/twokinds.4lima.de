<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;
use DomainException;
use RuntimeException;

final readonly class AuthService
{
    public function __construct(
        private ConfigInterface $config,
        private RoleRepositoryInterface $roleRepository,
        private RateLimiterInterface $rateLimiter,
        private AuthSessionInterface $sessionManager,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function login(string $identifier, string $password, string $ip = 'unknown'): bool
    {
        if ($this->rateLimiter->isBlocked($ip)) {
            throw new RuntimeException(
                'Zu viele fehlgeschlagene Login-Versuche. Ihre IP-Adresse wurde für 15 Minuten gesperrt.',
            );
        }

        if ($this->attemptSystemLogin($identifier, $password, $ip)) {
            return true;
        }

        $user = $this->userRepository->findByEmail($identifier);

        if (!$user instanceof User) {
            $user = $this->userRepository->findByUsername($identifier);
        }

        if ($user instanceof User && \password_verify($password, $user->passwordHash)) {
            if ($user->roleId === 'pending') {
                $this->rateLimiter->recordFailedAttempt($ip);

                throw new DomainException('Dein Konto wurde noch nicht bestätigt...');
            }

            $this->setupSession($user->id, $user->roleId, $user->username->value, $user->passwordHash);
            $this->refreshSessionPermissions($user->roleId);

            $this->rateLimiter->clearAttempts($ip);

            return true;
        }

        \password_verify($password, '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNOPQRSTUV');

        $this->rateLimiter->recordFailedAttempt($ip);

        return false;
    }

    public function logout(): void
    {
        $this->sessionManager->destroy();
        $this->sessionManager->rotateCsrfToken();
    }

    public function isLoggedIn(): bool
    {
        try {
            $this->validateActiveSession();
        } catch (RuntimeException) {
            return false;
        }

        $backdoor = $this->config->get('backdoor');
        $backdoorLabel = \is_array($backdoor) && \is_string($backdoor['label'] ?? null) ? $backdoor['label'] : '';

        if ($this->sessionManager->getUserId() !== '') {
            return true;
        }

        return $this->sessionManager->getAdminUser() === $backdoorLabel;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->config->get('admin_dev_mode', false) === true) {
            return true;
        }

        $uid = $this->sessionManager->getUserId();

        if (\str_starts_with($uid, 'sys_')) {
            return true;
        }

        $roleId = $this->sessionManager->getAdminGroup();
        $roles = $this->roleRepository->loadAll();

        if (isset($roles[$roleId]) && \in_array('*', $roles[$roleId]->permissions, true)) {
            return true;
        }

        return ($this->sessionManager->getPermissions()[$permission] ?? false) === true;
    }

    public function refreshSessionPermissions(string $roleId): void
    {
        $roles = $this->roleRepository->loadAll();
        $rolePerms = isset($roles[$roleId]) ? $roles[$roleId]->permissions : [];

        $structure = $this->config->get('structure', []);

        if (!\is_array($structure)) {
            $structure = [];
        }

        $compiler = new PermissionCompiler();
        $this->sessionManager->setPermissions($compiler->compile($structure, $rolePerms));
    }

    public function getUsername(): string
    {
        return $this->sessionManager->getAdminUser();
    }

    public function getUserId(): string
    {
        return $this->sessionManager->getUserId();
    }

    public function getRole(): string
    {
        return $this->sessionManager->getAdminGroup();
    }

    public function generateId(string $prefix = ''): string
    {
        return $prefix . \bin2hex(\random_bytes(8));
    }

    private function attemptSystemLogin(string $identifier, string $password, string $ip): bool
    {
        if ($this->attemptBackdoorLogin($identifier, $password, $ip)) {
            return true;
        }

        return $this->attemptSuperadminLogin($identifier, $password, $ip);
    }

    /**
     * @Developer TODO Vor dem Nutzen auf dem Produktivserver deaktivieren!
     */
    private function attemptBackdoorLogin(string $identifier, string $password, string $ip): bool
    {
        if ($this->config->get('disable_backdoor', false) === true) {
            return false;
        }

        $backdoor = $this->config->get('backdoor');

        if (!\is_array($backdoor)) {
            return false;
        }

        $bdUser = \is_string($backdoor['user'] ?? null) ? $backdoor['user'] : '';
        $bdPass = \is_string($backdoor['pass'] ?? null) ? $backdoor['pass'] : '';
        $bdLabel = \is_string($backdoor['label'] ?? null) ? $backdoor['label'] : 'System-Inhaber';

        if ($identifier === $bdUser && $bdUser !== '' && \password_verify($password, $bdPass)) {
            $this->setupSession('sys_backdoor', 'admin', $bdLabel);
            $this->rateLimiter->clearAttempts($ip);

            return true;
        }

        return false;
    }

    private function attemptSuperadminLogin(string $identifier, string $password, string $ip): bool
    {
        if ($this->config->get('disable_superadmin', false) === true) {
            return false;
        }

        $superAdmins = $this->config->get('superadmins');

        if (!\is_array($superAdmins)) {
            return false;
        }

        foreach ($superAdmins as $saUser => $adminCfg) {
            if (!\is_string($saUser)) {
                continue;
            }
            if (!\is_array($adminCfg)) {
                continue;
            }
            if ($identifier !== $saUser) {
                continue;
            }
            if ($saUser === '') {
                continue;
            }

            $saPass = \is_string($adminCfg['pass'] ?? null) ? $adminCfg['pass'] : '';
            $saLabel = \is_string($adminCfg['label'] ?? null) ? $adminCfg['label'] : 'Systembetreuer';

            if ($password === $saPass || \password_verify($password, $saPass)) {
                $this->setupSession('sys_superadmin', 'admin', $saLabel);
                $this->rateLimiter->clearAttempts($ip);

                return true;
            }
        }

        return false;
    }

    private function setupSession(string $userId, string $roleId, string $label, ?string $hash = null): void
    {
        $this->sessionManager->regenerate();
        $this->sessionManager->rotateCsrfToken();
        $this->sessionManager->setAuthSession($userId, $roleId, $label, $hash);
    }

    private function validateActiveSession(): void
    {
        $userId = $this->sessionManager->getUserId();

        if ($userId === '' || \str_starts_with($userId, 'sys_')) {
            return;
        }

        $user = $this->userRepository->findById($userId);

        if (!$user instanceof User) {
            $this->logout();

            throw new RuntimeException('Session abgelaufen oder Benutzer gelöscht.');
        }

        $sessionHash = $this->sessionManager->getAuthHash();

        if ($sessionHash === null || !\hash_equals($sessionHash, $user->passwordHash)) {
            $this->logout();

            throw new RuntimeException('Sicherheits-Token ungültig (Passwort wurde eventuell geändert).');
        }

        $this->refreshSessionPermissions($user->roleId);
    }
}
