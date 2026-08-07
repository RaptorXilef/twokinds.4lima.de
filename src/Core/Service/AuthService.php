<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Security\RateLimiterInterface;
use App\Contracts\Storage\RoleRepositoryInterface;
use App\Contracts\Storage\UserRepositoryInterface;
use App\Core\Entity\User;

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
            throw new \RuntimeException('Zu viele fehlgeschlagene Login-Versuche. Ihre IP-Adresse wurde für 15 Minuten gesperrt.');
        }

        // 1. Backdoor / Dev-Admin prüfen
        $backdoor = $this->config->get('backdoor');
        if (\is_array($backdoor) && $identifier === ($backdoor['user'] ?? '') && \password_verify($password, $backdoor['pass'] ?? '')) {
            $this->setupSession('sys_backdoor', 'admin', $backdoor['label'] ?? 'System-Inhaber');
            $this->rateLimiter->clearAttempts($ip);

            return true;
        }

        // HIER IST SYSTEMBETREUER!
        $superCfg = $this->config->get('superadmin');
        if (\is_array($superCfg) && $identifier === ($superCfg['user'] ?? '')) {
            $storedPass = $superCfg['pass'] ?? '';
            // Prüft entweder auf den Klartext (altes KGA-Design) oder Hash
            if ($password === $storedPass || \password_verify($password, $storedPass)) {
                $this->setupSession('sys_superadmin', 'admin', $superCfg['label'] ?? 'Systembetreuer');
                $this->rateLimiter->clearAttempts($ip);

                return true;
            }
        }

        // 2. Regulären User suchen (via Username ODER E-Mail)
        $user = $this->userRepository->findByEmail($identifier);
        if ($user === null) {
            $user = $this->userRepository->findByUsername($identifier);
        }

        // 3. Passwort prüfen
        if ($user instanceof User && \password_verify($password, $user->passwordHash)) {
            // Wenn der User nicht verifiziert ist, abbrechen!
            if ($user->roleId === 'pending') {
                $this->rateLimiter->recordFailedAttempt($ip);

                throw new \DomainException('Dein Konto wurde noch nicht bestätigt...');
            }

            $this->setupSession($user->id, $user->roleId, $user->username->value, $user->passwordHash);
            $this->refreshSessionPermissions($user->roleId);

            $this->rateLimiter->clearAttempts($ip);

            return true;
        }

        // Timing-Attack-Prevention: Immer verify ausführen, auch wenn User nicht gefunden wurde
        \password_verify($password, '$2y$10$abcdefghijklmnopqrstuvABCDEFGHIJKLMNOPQRSTUV');
        $this->rateLimiter->recordFailedAttempt($ip);

        return false;
    }

    public function logout(): void
    {
        $this->sessionManager->destroy();
        $this->sessionManager->rotateCsrfToken();
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
        if ($user === null) {
            $this->logout();

            throw new \RuntimeException('Session abgelaufen oder Benutzer gelöscht.');
        }

        $sessionHash = $this->sessionManager->getAuthHash();
        if ($sessionHash === null || ! \hash_equals($sessionHash, $user->passwordHash)) {
            $this->logout();

            throw new \RuntimeException('Sicherheits-Token ungültig (Passwort wurde eventuell geändert).');
        }

        $this->refreshSessionPermissions($user->roleId);
    }

    public function isLoggedIn(): bool
    {
        try {
            $this->validateActiveSession();
        } catch (\RuntimeException) {
            return false;
        }

        return $this->sessionManager->getUserId() !== ''
            || $this->sessionManager->getAdminUser() === ($this->config->get('backdoor')['label'] ?? '');
    }

    public function hasPermission(string $permission): bool
    {
        // Wenn admin_dev_mode aktiv ist, hat jeder vollen Zugriff
        if ($this->config->get('admin_dev_mode', false)) {
            return true;
        }

        $uid = $this->sessionManager->getUserId();

        if (\str_starts_with($uid, 'sys_')) {
            return true;
        }

        $roleId = $this->sessionManager->getAdminGroup();
        $roles  = $this->roleRepository->loadAll();

        // Prüfe auf Superadmin-Sternchen (*)
        if (isset($roles[$roleId]) && \in_array('*', $roles[$roleId]->permissions, true)) {
            return true;
        }

        // Strikter Boolean-Return, falls das Array mixed zurückgibt
        return ($this->sessionManager->getPermissions()[$permission] ?? false) === true;
    }

    public function refreshSessionPermissions(string $roleId): void
    {
        $roles     = $this->roleRepository->loadAll();
        $rolePerms = isset($roles[$roleId]) ? $roles[$roleId]->permissions : [];

        $structure = $this->config->get('structure', []);
        $compiler  = new PermissionCompiler();

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
}
