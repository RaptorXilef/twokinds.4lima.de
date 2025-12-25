<?php

/**
 * Dies ist die Login-Seite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung und die Erstellung des ersten Admin-Benutzers.
 * Diese Version ist gehärtet mit CSP (Nonce), CSRF-Schutz und Rate-Limiting.
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
 */

declare(strict_types=1);

// START OUTPUT BUFFERING (Verhindert "Headers already sent" Probleme und Cookie-Fehler)
ob_start();

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// === KONSTANTEN FÜR SICHERHEIT ===
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_TIME = 900; // 15 Minuten in Sekunden

// --- HILFSFUNKTIONEN ---

function hasUsers(): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    if (!file_exists($usersFile) || filesize($usersFile) === 0) {
        return false;
    }
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true);
    return is_array($users) && !empty($users);
}

function saveUsersList(array $users): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    $dir = dirname($usersFile);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        error_log("Fehler: Konnte Verzeichnis für Benutzerdatei nicht erstellen: " . $dir);
        return false;
    }
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents($usersFile, $jsonContent, LOCK_EX) !== false;
}

function getLoginAttempts(): array
{
    $file = Path::getSecretPath('login_attempts.json');
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveLoginAttempts(array $attempts): void
{
    $file = Path::getSecretPath('login_attempts.json');
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($file, json_encode($attempts, JSON_PRETTY_PRINT), LOCK_EX);
}

function checkRateLimit(string $ip): int
{
    $attempts = getLoginAttempts();
    $hasChanges = false;
    foreach ($attempts as $key => $data) {
        if ($data['last_attempt'] >= time() - LOGIN_LOCKOUT_TIME) {
            continue;
        }

        unset($attempts[$key]);
        $hasChanges = true;
    }
    if ($hasChanges) {
        saveLoginAttempts($attempts);
    }

    if (isset($attempts[$ip])) {
        if ($attempts[$ip]['count'] >= MAX_LOGIN_ATTEMPTS) {
            $timeLeft = $attempts[$ip]['last_attempt'] + LOGIN_LOCKOUT_TIME - time();
            return max(0, $timeLeft);
        }
    }
    return 0;
}

function recordFailedAttempt(string $ip): void
{
    $attempts = getLoginAttempts();
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = [
            'count' => 0,
            'last_attempt' => time(),
        ];
    }
    $attempts[$ip]['count']++;
    $attempts[$ip]['last_attempt'] = time();
    saveLoginAttempts($attempts);
}

function resetLoginAttempts(string $ip): void
{
    $attempts = getLoginAttempts();
    if (!isset($attempts[$ip])) {
        return;
    }

    unset($attempts[$ip]);
    saveLoginAttempts($attempts);
}

// --- LOGIK ---
$message = '';
$messageType = 'info';
$clientIp = $_SERVER['REMOTE_ADDR'];

// Prüfen, ob bereits Benutzer existieren
$usersExist = hasUsers();

// Wenn bereits eingeloggt, direkt weiterleiten
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    ob_end_clean();
    header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
    exit;
}

// --- 0. URL-GRÜNDE PRÜFEN (Höchste Priorität für Feedback) ---
// Wir prüfen das VOR allem anderen, damit es nicht überschrieben wird.
if (isset($_GET['reason'])) {
    $reason = htmlspecialchars($_GET['reason']); // XSS Schutz
    switch ($reason) {
        case 'timeout':
        case 'session_expired': // Entspricht dem Wert aus init_admin.php
            $message = 'Sie wurden aufgrund von Inaktivität automatisch abgemeldet.';
            $messageType = 'warning';
            break;
        case 'security':
        case 'session_hijacked':
            $message = 'Sitzung aus Sicherheitsgründen beendet.';
            $messageType = 'error';
            break;
        case 'logout':
            $message = 'Erfolgreich abgemeldet.';
            $messageType = 'success';
            break;
    }
}

// --- 1. Rate Limit Prüfung ---
// Nur prüfen, wenn wir nicht gerade eine Erfolgsmeldung (Logout) anzeigen
if (empty($message) || $messageType !== 'success') {
    $lockoutTime = checkRateLimit($clientIp);
    if ($lockoutTime > 0) {
        $minutes = ceil($lockoutTime / 60);
        $message = "Zu viele fehlgeschlagene Anmeldeversuche. Bitte warten Sie $minutes Minute(n).";
        $messageType = 'error';
    }
}

// --- 2. Verarbeitung von POST-Requests (Login / Setup) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($lockoutTime ?? 0) === 0) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = 'Ungültiger CSRF-Token.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_initial_user' && !$usersExist) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $message = 'Bitte Benutzername und Passwort angeben.';
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $users = [$username => ['passwordHash' => $hashedPassword]];

                if (saveUsersList($users)) {
                    $message = 'Admin-Benutzer erfolgreich erstellt. Bitte einloggen.';
                    $messageType = 'success';
                    $usersExist = true;
                } else {
                    $message = 'Fehler beim Speichern des Benutzers.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'login') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            $usersFile = Path::getSecretPath('admin_users.json');
            $users = [];
            if (file_exists($usersFile)) {
                $users = json_decode(file_get_contents($usersFile), true) ?? [];
            }

            if (isset($users[$username]) && password_verify($password, $users[$username]['passwordHash'])) {
                resetLoginAttempts($clientIp);

                session_regenerate_id(true);
                $_SESSION = [];

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();

                $fingerprint = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.')));
                $_SESSION['session_fingerprint'] = $fingerprint;

                session_write_close();
                ob_end_clean();

                header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
                exit;
            }

            recordFailedAttempt($clientIp);
            sleep(1);

            $attemptsLeft = MAX_LOGIN_ATTEMPTS - (getLoginAttempts()[$clientIp]['count'] ?? 0);

            if ($attemptsLeft <= 0) {
                $message = "Zu viele fehlgeschlagene Versuche. Zugang gesperrt.";
            } else {
                $message = "Ungültige Zugangsdaten. Noch $attemptsLeft Versuch(e).";
            }
            $messageType = 'error';
        }
    }
}

// Map message types to CSS classes
$cssClassMap = [
    'info' => 'status-info',
    'success' => 'status-green',
    'error' => 'status-red',
    'warning' => 'status-orange',
];
$alertClass = $cssClassMap[$messageType] ?? 'status-info';

$pageTitle = 'Admin Login';
$pageHeader = 'Adminbereich';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
ob_end_flush();
?>

    <div class="admin-login-container">

        <?php if (!empty($message)) : ?>
            <div class="status-message <?php echo $alertClass; ?> visible">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$usersExist) : ?>
            <!-- INITIAL SETUP FORM -->
            <h2>Ersteinrichtung</h2>
            <p class="intro-text">Willkommen! Es existiert noch kein Administrator-Konto.<br>Bitte erstelle jetzt den ersten Zugang.</p>

            <form action="<?php /*echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php';*/ ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-group">
                    <label for="create_username">Benutzername:</label>
                    <input type="text" id="create_username" name="username" required autocomplete="username" placeholder="Neuer Benutzername">
                </div>

                <div class="form-group">
                    <label for="create_password">Passwort:</label>
                    <input type="password" id="create_password" name="password" required autocomplete="new-password" placeholder="Sicheres Passwort">
                </div>

                <button type="submit" name="action" value="create_initial_user" class="button button-green">Admin erstellen</button>
            </form>

        <?php else : ?>
            <!-- LOGIN FORM -->
            <h2>Anmeldung</h2>
            <p class="intro-text">Bitte melde dich an, um auf den Admin-Bereich zuzugreifen.</p>

            <!-- Formular deaktivieren, wenn gesperrt -->
            <?php $isLocked = ($lockoutTime ?? 0) > 0; ?>

            <form action="<?php /*echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php';*/ ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-group">
                    <label for="login_username">Benutzername:</label>
                    <input type="text" id="login_username" name="username" required autocomplete="username" placeholder="Dein Benutzername" <?php echo $isLocked ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="login_password">Passwort:</label>
                    <input type="password" id="login_password" name="password" required autocomplete="current-password" placeholder="Dein Passwort" <?php echo $isLocked ? 'disabled' : ''; ?>>
                </div>

                <button type="submit" name="action" value="login" class="button button-blue" <?php echo $isLocked ? 'disabled' : ''; ?>>Einloggen</button>
            </form>
        <?php endif; ?>

    </div>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
