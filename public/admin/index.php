<?php

/**
 * Admin Login & Ersteinrichtung (v5.4.2 - Anti-Zombie Edition).
 *
 * @file      ROOT/public/admin/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 4.0.0
 * - Architektur & Core:
 *  - Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *  - Vollständige Integration der `init_admin.php` sowie Entfernung redundanter Sicherheits-Header und Funktionen.
 *
 * - Stabilität & Fixes:
 *  - Behebung einer Race Condition: Session-Daten werden nun explizit (`session_write_close`) vor Weiterleitungen
 *     geschrieben.
 *
 * @since 5.0.0
 * - refactor(UI): Inline-CSS entfernt und durch SCSS-Klassen (.admin-login-container) ersetzt.
 * - refactor(Layout): Nutzung der globalen .status-message Klassen für konsistentes Feedback.
 *
 * - fix(Security): `LOCK_EX` beim Erstellen des ersten Benutzers hinzugefügt.
 * - refactor(Code): Logik zum Lesen/Schreiben von Benutzern in Hilfsfunktionen ausgelagert (DRY).
 * - style(UI): `.intro-text` Klasse für Beschreibungstexte genutzt.
 *
 * - security(Critical): Rate-Limiting (Brute-Force-Schutz) wiederhergestellt und verbessert.
 * - logic: Fehlgeschlagene Anmeldungen werden in `login_attempts.json` protokolliert.
 * - logic: IP-Sperre nach 5 Fehlversuchen für 15 Minuten.
 *
 * - feat(UX): Anzeige von Logout-Gründen (Timeout, Sicherheit, manueller Logout) wiederhergestellt.
 * - fix(Session): Explizite Bereinigung des `$_SESSION`-Arrays nach ID-Regeneration, um Altlasten zu entfernen.
 *
 * - fix(Session): Output Buffering (ob_start) hinzugefügt, um "Doppelter Login"-Probleme durch Header-Fehler zu verhindern.
 * - fix(Session): `ob_end_clean()` vor Redirects, um sauberen Header-Versand zu garantieren.
 * - fix(UX): Logik für Logout-Gründe (`?reason=...`) optimiert, damit diese zuverlässiger angezeigt werden.
 *
 * - fix(UX): Logout-Grund `session_expired` (aus init_admin.php) hinzugefügt.
 * - fix(Session): "Doppelter Login" behoben -> Session wird nun vollständig initialisiert (inkl. Fingerprint & Timestamp), bevor weitergeleitet wird.
 *
 * - fix(UX): Logik für Logout-Meldungen (`?reason=...`) priorisiert, damit sie nicht von der POST-Logik überdeckt wird.
 * - fix(Session): Sichergestellt, dass die Session nur gestartet wird, wenn nötig.
 *
 * - fix(UX): `reason`-Parameter wird nun ganz am Anfang verarbeitet, um Konflikte mit Rate-Limiting oder anderen Meldungen zu vermeiden.
 * - fix(UX): Explizite Behandlung von `session_expired`.
 *
 * - fix(login): Neuaufbau der Loginlogik (SESSION,  COOKIE, ....)
 */

declare(strict_types=1);

// START OUTPUT BUFFERING (Verhindert "Headers already sent" Probleme und Cookie-Fehler)
ob_start();

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- MULTI-TAB ERKENNUNG ---
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    ob_end_clean();
    header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
    exit;
}

// --- KONSTANTEN ---
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_TIME = 900; // 15 Minuten in Sekunden

// --- HILFSFUNKTIONEN (Wiederhergestellt) ---

function hasUsers(): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    if (!file_exists($usersFile) || filesize($usersFile) === 0) {
        return false;
    }
    $users = json_decode(file_get_contents($usersFile), true);
    return is_array($users) && !empty($users);
}

function saveUsersList(array $users): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    if (!is_dir(dirname($usersFile))) {
        mkdir(dirname($usersFile), 0755, true);
    }
    return file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function getLoginAttempts(): array
{
    $file = Path::getSecretPath('login_attempts.json');
    if (!file_exists($file)) {
        return [];
    }
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveLoginAttempts(array $attempts): void
{
    $file = Path::getSecretPath('login_attempts.json');
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    file_put_contents($file, json_encode($attempts, JSON_PRETTY_PRINT), LOCK_EX);
}

function checkRateLimit(string $ip): int
{
    $attempts = getLoginAttempts();
    if (isset($attempts[$ip])) {
        if ($attempts[$ip]['last_attempt'] < time() - LOGIN_LOCKOUT_TIME) {
            return 0;
        }
        if ($attempts[$ip]['count'] >= MAX_LOGIN_ATTEMPTS) {
            return $attempts[$ip]['last_attempt'] + LOGIN_LOCKOUT_TIME - time();
        }
    }
    return 0;
}

function recordFailedAttempt(string $ip): void
{
    $attempts = getLoginAttempts();
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
    }
    $attempts[$ip]['count']++;
    $attempts[$ip]['last_attempt'] = time();
    saveLoginAttempts($attempts);
}

function resetLoginAttempts(string $ip): void
{
    $attempts = getLoginAttempts();
    unset($attempts[$ip]);
    saveLoginAttempts($attempts);
}

// --- LOGIK ---
$message = '';
$messageType = 'info';
$clientIp = $_SERVER['REMOTE_ADDR'];
$usersExist = hasUsers();

// 1. Gründe verarbeiten
if (isset($_GET['reason'])) {
    $reason = $_GET['reason'];
    if ($reason === 'session_expired') {
        $message = 'Sitzung abgelaufen.';
        $messageType = 'warning';
    } elseif ($reason === 'logout') {
        $message = 'Erfolgreich abgemeldet.';
        $messageType = 'success';
    } elseif ($reason === 'session_hijacked') {
        $message = 'Sicherheit: Sitzung beendet.';
        $messageType = 'error';
    }
}

// 2. Rate-Limit Check
$lockoutTime = checkRateLimit($clientIp);
if ($lockoutTime > 0) {
    $message = 'Zu viele Versuche. Bitte ' . ceil($lockoutTime / 60) . ' Min. warten.';
    $messageType = 'error';
}

// 3. POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutTime === 0) {
    if (!verify_csrf_token()) {
        $message = 'Sicherheits-Fehler (CSRF). Bitte Seite neu laden.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_initial_user' && !$usersExist) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($username && $password) {
                $users = [$username => ['passwordHash' => password_hash($password, PASSWORD_DEFAULT)]];
                if (saveUsersList($users)) {
                    $message = 'Admin erstellt. Bitte einloggen.';
                    $messageType = 'success';
                    $usersExist = true;
                }
            }
        } elseif ($action === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $users = json_decode(file_get_contents(Path::getSecretPath('admin_users.json')), true) ?? [];

            if (isset($users[$username]) && password_verify($password, $users[$username]['passwordHash'])) {
                resetLoginAttempts($clientIp);
                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();
                $_SESSION['session_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . substr($_SERVER['REMOTE_ADDR'], 0, (strrpos($_SERVER['REMOTE_ADDR'], '.') ?: 0)));

                session_write_close();
                ob_end_clean();
                header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
                exit;
            }

            recordFailedAttempt($clientIp);
            $message = 'Zugangsdaten falsch.';
            $messageType = 'error';
        }
    }
}

$csrfToken = $_SESSION['csrf_token'];
$alertClass = [
    'info' => 'status-info',
    'success' => 'status-green',
    'error' => 'status-red',
    'warning' => 'status-orange',
][$messageType];

require_once Path::getPartialTemplatePath('header.php');
?>

<div class="admin-login-container page-login">
    <?php if (!empty($message)) : ?>
        <div class="status-message <?= $alertClass; ?> visible">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$usersExist) : ?>
        <h2>Ersteinrichtung</h2>
        <form action="" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <input type="text" name="username" required placeholder="Admin Name">
            <input type="password" name="password" required placeholder="Passwort">
            <button type="submit" name="action" value="create_initial_user" class="button button-green">Einrichten</button>
        </form>
    <?php else : ?>
        <h2>Anmeldung</h2>
        <form action="" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <div class="form-group">
                <label>Benutzername:</label>
                <input type="text" name="username" required placeholder="Dein Benutzername" <?= $lockoutTime > 0 ? 'disabled' : '' ?>>
            </div>
            <div class="form-group">
                <label>Passwort:</label>
                <input type="password" name="password" required placeholder="Dein Passwort" <?= $lockoutTime > 0 ? 'disabled' : '' ?>>
            </div>
            <button type="submit" name="action" value="login" class="button button-blue" <?= $lockoutTime > 0 ? 'disabled' : '' ?>>Einloggen</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
