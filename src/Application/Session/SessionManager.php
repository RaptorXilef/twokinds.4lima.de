<?php

declare(strict_types=1);

namespace App\Application\Session;

use App\Contracts\Security\AuthSessionInterface;

/**
 * Kapselt alle Zugriffe auf den globalen $_SESSION State.
 * Verhindert direkte Array-Mutationen in den Actions (Leaky Abstractions).
 *
 * SPDX-License-Identifier: LicenseRef-Proprietary
 */
final class SessionManager implements AuthSessionInterface
{
    private const int MAX_LIFETIME = 43200; // 12 Stunden absolutes Maximum
    // private const int IDLE_TIMEOUT = 7200;  // 2 Stunden Inaktivität führt zum Logout
    // ÄNDERUNG: Reduziert auf 30 Min. (Bietet 10 Min Puffer für den 20-Minuten JS-Timer)
    private const int IDLE_TIMEOUT = 1800;  // 30 Minuten Inaktivität

    public function __construct()
    {
        if (\session_status() === \PHP_SESSION_NONE) {
            \session_start();
        }
        $this->enforceServerSideTimeout();
    }

    /**
     * Setzt strikte serverseitige Timeouts durch, da moderne Browser
     * "lifetime=0" Session-Cookies oft absichtlich wiederherstellen.
     */
    private function enforceServerSideTimeout(): void
    {
        $now = \time();

        if (! isset($_SESSION['session_created'])) {
            $_SESSION['session_created'] = $now;
            $_SESSION['last_activity']   = $now;

            return;
        }

        // Prüfen, ob der Nutzer gerade authentifizierte Daten in der Session hat
        $isAuthenticated = ! empty($_SESSION['user_id']) || ! empty($_SESSION['admin_user']);

        // Idle Timeout: User war zu lange inaktiv
        if (($now - $_SESSION['last_activity']) > self::IDLE_TIMEOUT) {
            // Wir zerstören die Session nach 30 Min NUR, wenn sensible Admin-Daten drin liegen!
            // Gast-Sessions (für das CSRF Token auf der Loginseite) lassen wir am Leben.
            if ($isAuthenticated) {
                $this->destroy();
                \session_start();
            }
            $_SESSION['session_created'] = $now;
            $_SESSION['last_activity']   = $now;

            return;
        }

        // Absolute Timeout: Session existiert insgesamt zu lange
        if (($now - $_SESSION['session_created']) > self::MAX_LIFETIME) {
            $this->destroy();
            \session_start();
            $_SESSION['session_created'] = $now;
            $_SESSION['last_activity']   = $now;

            return;
        }

        $_SESSION['last_activity'] = $now;
    }

    public function setFormData(array $data): void
    {
        $_SESSION['form_data'] = $data;
    }

    public function getFormData(): array
    {
        return $_SESSION['form_data'] ?? [];
    }

    public function clearFormData(): void
    {
        unset($_SESSION['form_data']);
    }

    public function setEditState(string $email, string $token): void
    {
        $_SESSION['verified_email'] = $email;
        $_SESSION['edit_token']     = $token;
    }

    public function getVerifiedEmail(): ?string
    {
        return $_SESSION['verified_email'] ?? null;
    }

    public function getEditToken(): ?string
    {
        return $_SESSION['edit_token'] ?? null;
    }

    public function clearEditState(): void
    {
        unset($_SESSION['verified_email'], $_SESSION['edit_token']);
    }

    public function setAdminFilters(array $filters): void
    {
        $_SESSION['admin_filters'] = $filters;
    }

    public function getAdminFilters(): array
    {
        return $_SESSION['admin_filters'] ?? [];
    }

    public function clearAdminFilters(): void
    {
        unset($_SESSION['admin_filters']);
    }

    public function setHistoryEmail(string $email): void
    {
        $_SESSION['user_history_email'] = $email;
    }

    public function getHistoryEmail(): ?string
    {
        return $_SESSION['user_history_email'] ?? null;
    }

    public function clearHistoryEmail(): void
    {
        unset($_SESSION['user_history_email']);
    }

    public function updateAdminUsername(string $newName): void
    {
        $_SESSION['admin_user'] = $newName;
    }

    // --- AUTH & SECURITY ---
    public function regenerate(): void
    {
        \session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (\ini_get('session.use_cookies')) {
            $p = \session_get_cookie_params();
            \setcookie(\session_name(), '', \time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        \session_destroy();
    }

    public function setAuthSession(string $userId, string $groupId, string $label, ?string $hash = null): void
    {
        $_SESSION['user_id']     = $userId;
        $_SESSION['admin_user']  = $label;
        $_SESSION['admin_group'] = $groupId;
        if ($hash) {
            $_SESSION['auth_hash'] = $hash;
        }
    }

    public function getAuthHash(): ?string
    {
        return $_SESSION['auth_hash'] ?? null;
    }

    public function setPermissions(array $perms): void
    {
        $_SESSION['compiled_permissions'] = $perms;
    }

    public function getPermissions(): array
    {
        return $_SESSION['compiled_permissions'] ?? [];
    }

    public function getUserId(): string
    {
        return (string) ($_SESSION['user_id'] ?? '');
    }

    public function getAdminGroup(): string
    {
        return (string) ($_SESSION['admin_group'] ?? 'guest');
    }

    public function getAdminUser(): string
    {
        return (string) ($_SESSION['admin_user'] ?? 'Unbekannt');
    }

    // --- INFRASTRUCTURE ---
    public function setAnalyticsId(string $id): void
    {
        $_SESSION['ga4_client_id'] = $id;
    }

    public function getAnalyticsId(): ?string
    {
        return $_SESSION['ga4_client_id'] ?? null;
    }

    public function initCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = \bin2hex(\random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function getCsrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Rotiert das CSRF-Token (wichtig bei Authentifizierungs-Wechseln).
     */
    public function rotateCsrfToken(): void
    {
        $_SESSION['csrf_token'] = \bin2hex(\random_bytes(32));
    }

    /**
     * Speichert eine Flash-Message in der Session.
     * $type ist z.B. 'success', 'error', 'warning', 'info'
     */
    public function addFlash(string $type, string $message): void
    {
        $_SESSION['flashes'][$type][] = $message;
    }

    /**
     * Liest alle Flash-Messages aus und löscht sie danach sofort.
     */
    public function getFlashes(): array
    {
        $flashes = $_SESSION['flashes'] ?? [];
        unset($_SESSION['flashes']);

        return $flashes;
    }
}
