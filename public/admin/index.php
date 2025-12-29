<?php

/**
 * Admin Login & Ersteinrichtung (v5.0.0 - Brute-Force Protected).
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
 * - Wiederherstellung des Brute-Force-Schutzes (Rate-Limiting).
 * - Implementierung von Tarpitting (sleep) bei Fehlversuchen.
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

// --- KONSTANTEN FÜR SICHERHEIT ---
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_TIME = 900; // 15 Minuten Sperre

// --- HILFSFUNKTIONEN (Sicherheits-Ebene) ---

/**
 * Prüft, ob bereits Administrator-Accounts existieren.
 */
function hasUsers(): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    if (!file_exists($usersFile) || filesize($usersFile) === 0) {
        return false;
    }
    $users = json_decode(file_get_contents($usersFile), true);
    return is_array($users) && !empty($users);
}

/**
 * Speichert die Liste der Administratoren.
 */
function saveUsersList(array $users): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    if (!is_dir(dirname($usersFile))) {
        mkdir(dirname($usersFile), 0755, true);
    }
    return file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

/**
 * Lädt die aktuellen Login-Versuche aus dem Dateisystem.
 */
function getLoginAttempts(): array
{
    $file = Path::getSecretPath('login_attempts.json');
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/**
 * Speichert Login-Versuche (Brute-Force-Schutz).
 */
function saveLoginAttempts(array $attempts): void
{
    $file = Path::getSecretPath('login_attempts.json');
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    file_put_contents($file, json_encode($attempts, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Prüft das Rate-Limit für die aktuelle IP.
 * @return int Verbleibende Sekunden der Sperre (0 = nicht gesperrt).
 */
function checkRateLimit(string $ip): int
{
    $attempts = getLoginAttempts();
    if (isset($attempts[$ip])) {
        // Sperre abgelaufen?
        if ($attempts[$ip]['last_attempt'] < time() - LOGIN_LOCKOUT_TIME) {
            return 0;
        }
        if ($attempts[$ip]['count'] >= MAX_LOGIN_ATTEMPTS) {
            return $attempts[$ip]['last_attempt'] + LOGIN_LOCKOUT_TIME - time();
        }
    }
    return 0;
}

/**
 * Protokolliert einen fehlgeschlagenen Versuch.
 */
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

/**
 * Setzt die Versuche nach erfolgreichem Login zurück.
 */
function resetLoginAttempts(string $ip): void
{
    $attempts = getLoginAttempts();
    if (!isset($attempts[$ip])) {
        return;
    }

    unset($attempts[$ip]);
    saveLoginAttempts($attempts);
}

// --- LOGIK-VERARBEITUNG ---
$message = '';
$messageType = 'info';
$clientIp = $_SERVER['REMOTE_ADDR'];
$usersExist = hasUsers();

// 1. SICHERHEITS-CHECK (HTTPS & BROWSER)
$isSecureConnection = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

$securityWarnings = [];

// 1.1. Check: HTTPS (Kritisch)
if (!$isSecureConnection) {
    $securityWarnings[] = [
        'type' => 'status-orange', // Orange für Warnung
        'icon' => 'fa-unlock-alt',
        'text' => '<strong>Unsichere Verbindung:</strong> Die Seite wird über unverschlüsseltes HTTP geladen. Ihr Passwort könnte im Netzwerk mitgelesen werden.',
    ];
}

// 1.2. Check: Bekannte veraltete Browser (Beispiel für IE)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
    $securityWarnings[] = [
        'type' => 'status-red', // Rot für technisches Risiko
        'icon' => 'fa-exclamation-triangle',
        'text' => '<strong>Browser-Risiko:</strong> Sie nutzen einen veralteten Browser (IE). Dies stellt ein Sicherheitsrisiko dar und könnte die Funktion des Admin-Bereichs beeinträchtigen.',
    ];
}

// 1.3. Check: Cookies (Wichtig für die Funktion)
// Da wir PHP nutzen, sehen wir Cookies erst nach dem ersten Refresh.
// Ein Hinweis ist aber gut, falls $_COOKIE leer ist und es kein Redirect war.
if (empty($_COOKIE) && !isset($_GET['reason'])) {
    $securityWarnings[] = [
        'type' => 'status-info',
        'icon' => 'fa-cookie-bite',
        'text' => '<strong>Hinweis:</strong> Bitte stellen Sie sicher, dass Cookies in Ihrem Browser aktiviert sind, um angemeldet zu bleiben.',
    ];
}

// 2. INPUT-PERSISTENZ VORBEREITUNG
$lastUser = '';
$lastPass = '';

// 3. GRÜNDE AUS URL VERARBEITEN (z.B. nach Logout oder Timeout)
if (isset($_GET['reason'])) {
    $reason = $_GET['reason'];
    if ($reason === 'session_expired') {
        $message = 'Sitzung abgelaufen.';
        $messageType = 'warning';
    } elseif ($reason === 'logout') {
        $message = 'Erfolgreich abgemeldet.';
        $messageType = 'success';
    } elseif ($reason === 'session_hijacked') {
        $message = 'Sicherheitswarnung: Sitzung wurde beendet.';
        $messageType = 'error';
    }
}

// 4. BRUTE-FORCE CHECK
$lockoutSeconds = checkRateLimit($clientIp);
if ($lockoutSeconds > 0) {
    $minutes = ceil($lockoutSeconds / 60);
    $message = "Zu viele Fehlversuche. Zugang für $minutes Min. gesperrt.";
    $messageType = 'error';
}

// 5. POST-VERARBEITUNG (Login / Ersteinrichtung)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutSeconds === 0) {
    if (!verify_csrf_token()) {
        $message = 'Sicherheits-Fehler (CSRF). Bitte laden Sie die Seite neu.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // UX-SICHERHEIT: Werte NUR bei HTTPS für die Wiederherstellung puffern
        if ($isSecureConnection) {
            $lastUser = $_POST['username'] ?? '';
            $lastPass = $_POST['password'] ?? '';
        }

        // --- AKTION: Ersteinrichtung ---
        if ($action === 'create_initial_user' && !$usersExist) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if (!empty($username) && !empty($password)) {
                $users = [$username => ['passwordHash' => password_hash($password, PASSWORD_DEFAULT)]];
                if (saveUsersList($users)) {
                    $message = 'Administrator erfolgreich erstellt. Sie können sich nun anmelden.';
                    $messageType = 'success';
                    $usersExist = true;
                }
            } else {
                $message = 'Bitte geben Sie einen Namen und ein Passwort an.';
                $messageType = 'error';
            }
        }
        // --- AKTION: Normaler Login ---
        elseif ($action === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $usersFile = Path::getSecretPath('admin_users.json');
            $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

            if (isset($users[$username]) && password_verify($password, $users[$username]['passwordHash'])) {
                // ERFOLG -> Weiterleitung
                resetLoginAttempts($clientIp);
                session_regenerate_id(true);
                $_SESSION = []; // Alte Session-Daten komplett verwerfen

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();

                // Fingerprint mit IP-Netzwerk-Teil (die ersten 3 Oktette) für bessere Stabilität
                $ipSegment = substr($clientIp, 0, (strrpos($clientIp, '.') ?: 0));
                $_SESSION['session_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . $ipSegment);

                session_write_close();
                ob_end_clean();
                header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
                exit;
            } else {
                // --- FEHLSCHLAG ---
                recordFailedAttempt($clientIp);
                sleep(1); // Tarpitting: Verlangsamt Brute-Force Bots

                // Versuche neu laden und berechnen
                $attempts = getLoginAttempts();
                $remaining = MAX_LOGIN_ATTEMPTS - ($attempts[$clientIp]['count'] ?? 0);

                if ($remaining > 0) {
                    $message = "Ungültige Zugangsdaten. Sie haben noch $remaining Versuch(e).";
                } else {
                    $message = "Zu viele Fehlversuche. Ihr Zugang wurde gesperrt.";
                }
                $messageType = 'error';
            }
        }
    }
}

// UI-Vorbereitung
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

    <?php foreach ($securityWarnings as $warn) : ?>
        <div class="status-message <?= $warn['type']; ?> visible" style="text-align: left; margin-bottom: 10px;">
            <i class="fas <?= $warn['icon']; ?>" style="margin-right: 10px; width: 20px; text-align: center;"></i>
            <?= $warn['text']; ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($message)) : ?>
        <div class="status-message <?= $alertClass; ?> visible">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$usersExist) : ?>
        <h2>Ersteinrichtung</h2>
        <p class="intro-text">Willkommen! Bitte erstellen Sie den ersten Administrator-Zugang.</p>
        <form action="" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">
            <div class="form-group">
                <label for="reg_user">Admin-Name:</label>
                <input type="text" id="reg_user" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="reg_pass">Passwort:</label>
                <input type="password" id="reg_pass" name="password" required autocomplete="new-password">
            </div>
            <button type="submit" name="action" value="create_initial_user" class="button button-green">Admin erstellen</button>
        </form>
    <?php else : ?>
        <h2>Anmeldung</h2>
        <?php $isLocked = ($lockoutSeconds > 0); ?>
        <form action="" method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="log_user">Benutzername:</label>
                <input type="text" id="log_user" name="username" required autocomplete="username"
                    value="<?= htmlspecialchars($lastUser) ?>" <?= $isLocked ? 'disabled' : '' ?>>
            </div>

            <div class="form-group">
                <label for="log_pass">Passwort:</label>
                <div class="password-wrapper">
                    <input type="password" id="log_pass" name="password" required autocomplete="current-password"
                        value="<?= htmlspecialchars($lastPass) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                    <button type="button" id="togglePassword" class="password-toggle" title="Passwort anzeigen">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="action" value="login" class="button button-blue" <?= $isLocked ? 'disabled' : '' ?>>
                <?= $isLocked ? 'Gesperrt' : 'Einloggen' ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<script nonce="<?= $nonce ?>">
    // JavaScript für das Umschalten der Passwort-Sichtbarkeit
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#log_pass');
    const eyeIcon = document.querySelector('#eyeIcon');

    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            // Typ umschalten
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Icon umschalten
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    }
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
