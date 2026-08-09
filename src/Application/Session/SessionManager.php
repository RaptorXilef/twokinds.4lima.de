<?php

declare(strict_types=1);

namespace App\Application\Session;

use App\Contracts\Security\AuthSessionInterface;
use App\Contracts\Utils\ClockInterface;

/**
 * Kapselt alle Zugriffe auf den globalen $_SESSION State.
 * Verhindert direkte Array-Mutationen in den Actions (Leaky Abstractions).
 */
final readonly class SessionManager implements AuthSessionInterface
{
    private const int MAX_LIFETIME = 43200; // 12 Stunden absolutes Maximum
    // private const int IDLE_TIMEOUT = 7200;  // 2 Stunden Inaktivität führt zum Logout
    // ÄNDERUNG: Reduziert auf 30 Min. (Bietet 10 Min Puffer für den 20-Minuten JS-Timer)
    private const int IDLE_TIMEOUT = 1800;  // 30 Minuten Inaktivität

    public function __construct(private ClockInterface $clock)
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
        $now = $this->clock->now()->getTimestamp();

        if (!isset($_SESSION['session_created'])) {
            $_SESSION['session_created'] = $now;
            $_SESSION['last_activity'] = $now;

            return;
        }

        // Prüfen, ob der Nutzer gerade authentifizierte Daten in der Session hat
        $isAuthenticated = (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '')
                        || (isset($_SESSION['admin_user']) && $_SESSION['admin_user'] !== '');

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $lastActivityInt = \is_numeric($lastActivity) ? (int) $lastActivity : 0;

        // Idle Timeout: User war zu lange inaktiv
        if ($now - $lastActivityInt > self::IDLE_TIMEOUT) {
            // Wir zerstören die Session nach 30 Min NUR, wenn sensible Admin-Daten drin liegen!
            // Gast-Sessions (für das CSRF Token auf der Loginseite) lassen wir am Leben.
            if ($isAuthenticated) {
                $this->destroy();
            }
            $_SESSION['session_created'] = $now;
            $_SESSION['last_activity'] = $now;

            return;
        }

        $sessionCreated = $_SESSION['session_created'] ?? 0;
        $sessionCreatedInt = \is_numeric($sessionCreated) ? (int) $sessionCreated : 0;

        // Absolute Timeout: Session existiert insgesamt zu lange
        if ($now - $sessionCreatedInt > self::MAX_LIFETIME) {
            $this->destroy();
            $_SESSION['session_created'] = $now;
            $_SESSION['last_activity'] = $now;

            return;
        }

        $_SESSION['last_activity'] = $now;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setFormData(array $data): void
    {
        $_SESSION['form_data'] = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormData(): array
    {
        if (isset($_SESSION['form_data']) && \is_array($_SESSION['form_data'])) {
            /** @var array<string, mixed> $data */
            $data = $_SESSION['form_data'];

            return $data;
        }

        return [];
    }

    public function clearFormData(): void
    {
        unset($_SESSION['form_data']);
    }

    public function setEditState(string $email, string $token): void
    {
        $_SESSION['verified_email'] = $email;
        $_SESSION['edit_token'] = $token;
    }

    public function getVerifiedEmail(): ?string
    {
        return isset($_SESSION['verified_email']) && \is_string($_SESSION['verified_email']) ? $_SESSION['verified_email'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong
    }

    public function getEditToken(): ?string
    {
        return isset($_SESSION['edit_token']) && \is_string($_SESSION['edit_token']) ? $_SESSION['edit_token'] : null;
    }

    public function clearEditState(): void
    {
        unset($_SESSION['verified_email'], $_SESSION['edit_token']);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function setAdminFilters(array $filters): void
    {
        $_SESSION['admin_filters'] = $filters;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminFilters(): array
    {
        if (isset($_SESSION['admin_filters']) && \is_array($_SESSION['admin_filters'])) {
            /** @var array<string, mixed> $data */
            $data = $_SESSION['admin_filters'];

            return $data;
        }

        return [];
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
        return isset($_SESSION['user_history_email']) && \is_string($_SESSION['user_history_email']) ? $_SESSION['user_history_email'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong
    }

    public function clearHistoryEmail(): void
    {
        unset($_SESSION['user_history_email']);
    }

    public function updateAdminUsername(string $newName): void
    {
        $_SESSION['admin_user'] = $newName;
    }

    public function regenerate(): void
    {
        \session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (\ini_get('session.use_cookies') === '1') {
            // PHPStan Level 9 versteht exakt die Signatur von session_get_cookie_params.
            // Daher sind Casts und isset() hier komplett überflüssig!
            $p = \session_get_cookie_params();

            $cookieParams = [
                'expires' => $this->clock->now()->getTimestamp() - 42000,
                'path' => $p['path'],
                'domain' => $p['domain'],
                'secure' => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ];

            // session_name gibt in PHP 8 einen non-falsy-string zurück
            $sessionName = \session_name();
            $name = \is_string($sessionName) ? $sessionName : 'PHPSESSID';

            \setcookie($name, '', $cookieParams);
        }
        \session_destroy();

        if (\session_status() !== \PHP_SESSION_NONE) {
            return;
        }

        \session_start();
    }

    public function setAuthSession(string $userId, string $groupId, string $label, ?string $hash = null): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['admin_user'] = $label;
        $_SESSION['admin_group'] = $groupId;
        if ($hash === null) {
            return;
        }

        $_SESSION['auth_hash'] = $hash;
    }

    public function getAuthHash(): ?string
    {
        return isset($_SESSION['auth_hash']) && \is_string($_SESSION['auth_hash']) ? $_SESSION['auth_hash'] : null;
    }

    /**
     * @param array<mixed> $perms
     */
    public function setPermissions(array $perms): void
    {
        $_SESSION['compiled_permissions'] = $perms;
    }

    /**
     * @return array<string, bool>
     */
    public function getPermissions(): array
    {
        if (isset($_SESSION['compiled_permissions']) && \is_array($_SESSION['compiled_permissions'])) {
            /** @var array<string, bool> $perms */
            $perms = $_SESSION['compiled_permissions'];

            return $perms;
        }

        return [];
    }

    public function getUserId(): string
    {
        return isset($_SESSION['user_id']) && \is_string($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
    }

    public function getAdminGroup(): string
    {
        return isset($_SESSION['admin_group']) && \is_string($_SESSION['admin_group']) ? $_SESSION['admin_group'] : 'guest'; // phpcs:ignore Generic.Files.LineLength.TooLong
    }

    public function getAdminUser(): string
    {
        return isset($_SESSION['admin_user']) && \is_string($_SESSION['admin_user']) ? $_SESSION['admin_user'] : 'Unbekannt'; // phpcs:ignore Generic.Files.LineLength.TooLong
    }

    // --- INFRASTRUCTURE ---
    public function setAnalyticsId(string $id): void
    {
        $_SESSION['ga4_client_id'] = $id;
    }

    public function getAnalyticsId(): ?string
    {
        return isset($_SESSION['ga4_client_id']) && \is_string($_SESSION['ga4_client_id']) ? $_SESSION['ga4_client_id'] : null; // phpcs:ignore Generic.Files.LineLength.TooLong
    }

    public function initCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token']) || !\is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
            $_SESSION['csrf_token'] = \bin2hex(\random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function getCsrfToken(): string
    {
        return isset($_SESSION['csrf_token']) && \is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
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
        if (!isset($_SESSION['flashes']) || !\is_array($_SESSION['flashes'])) {
            $_SESSION['flashes'] = [];
        }
        if (!isset($_SESSION['flashes'][$type]) || !\is_array($_SESSION['flashes'][$type])) {
            $_SESSION['flashes'][$type] = [];
        }

        $_SESSION['flashes'][$type][] = $message;
    }

    /**
     * Liest alle Flash-Messages aus und löscht sie danach sofort.
     *
     * @return array<string, array<int, string>>
     */
    public function getFlashes(): array
    {
        if (isset($_SESSION['flashes']) && \is_array($_SESSION['flashes'])) {
            /** @var array<string, array<int, string>> $flashes */
            $flashes = $_SESSION['flashes'];
            unset($_SESSION['flashes']);

            return $flashes;
        }

        return [];
    }
}
