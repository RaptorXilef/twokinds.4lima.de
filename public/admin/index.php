<?php

/**
 * Dies ist die Login-Seite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung und die Erstellung des ersten Admin-Benutzers.
 * Diese Version ist gehärtet mit CSP (Nonce) und CSRF-Schutz.
 *
 * @file      ROOT/public/admin/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
@since 4.0.0
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
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

if ($debugMode) {
    error_log("DEBUG: index.php (Login) wird geladen.");
}

// --- Konfiguration für Brute-Force-Schutz ---
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_BLOCK_SECONDS', 900);

// --- Hilfsfunktionen ---
function getUsers(): array
{
    $filePath = Path::getSecretPath('admin_users.json');
    if (!file_exists($filePath) || filesize($filePath) === 0) {
        return [];
    }
    $content = file_get_contents($filePath);
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

function saveUsers(array $users): bool
{
    $filePath = Path::getSecretPath('admin_users.json');
    $dir = dirname($filePath);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        error_log("Fehler: Konnte Verzeichnis für Benutzerdatei nicht erstellen: " . $dir);
        return false;
    }
    return file_put_contents($filePath, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}

function getLoginAttempts(): array
{
    $filePath = Path::getSecretPath('login_attempts.json');
    if (!file_exists($filePath)) {
        return [];
    }
    $attempts = json_decode(file_get_contents($filePath), true);
    return is_array($attempts) ? $attempts : [];
}

function saveLoginAttempts(array $attempts): bool
{
    $filePath = Path::getSecretPath('login_attempts.json');
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($filePath, json_encode($attempts, JSON_PRETTY_PRINT)) !== false;
}

// --- Logik ---
$message = '';
$msgType = 'info'; // Default type

if (isset($_GET['reason'])) {
    if ($_GET['reason'] === 'session_expired') {
        $message = 'Ihre Sitzung ist aufgrund von Inaktivität abgelaufen. Bitte melden Sie sich erneut an.';
        $msgType = 'orange';
    }
    if ($_GET['reason'] === 'session_hijacked') {
        $message = 'Ihre Sitzung wurde aus Sicherheitsgründen beendet. Bitte melden Sie sich erneut an.';
        $msgType = 'red';
    }
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_initial_user':
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? '';
            $users = getUsers();
            if (empty($users)) {
                if (empty($username) || empty($password)) {
                    $message = 'Benutzername und Passwort dürfen nicht leer sein.';
                    $msgType = 'red';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = ['passwordHash' => $hashedPassword];
                    if (saveUsers($users)) {
                        $message = 'Erster Admin-Benutzer erfolgreich erstellt. Bitte melden Sie sich an.';
                        $msgType = 'green';
                        // Wir leiten um, um den POST-Request zu leeren und die Ansicht zu wechseln
                        header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php');
                        exit;
                    } else {
                        $message = 'Fehler beim Speichern des Benutzers.';
                        $msgType = 'red';
                    }
                }
            }
            break;

        case 'login':
            $userIp = $_SERVER['REMOTE_ADDR'];
            $attempts = getLoginAttempts();

            if (isset($attempts[$userIp]) && $attempts[$userIp]['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $timeSinceLastAttempt = time() - $attempts[$userIp]['last_attempt'];
                if ($timeSinceLastAttempt < LOGIN_BLOCK_SECONDS) {
                    $remainingTime = LOGIN_BLOCK_SECONDS - $timeSinceLastAttempt;
                    $message = 'Zu viele fehlgeschlagene Login-Versuche. Bitte warten Sie noch ' . ceil($remainingTime / 60) . ' Minute(n).';
                    $msgType = 'red';
                    break;
                } else {
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                }
            }

            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? '';
            $users = getUsers();

            if (isset($users[$username]) && password_verify($password, $users[$username]['passwordHash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();
                $_SESSION['session_fingerprint'] = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . (substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.'))));

                if (isset($attempts[$userIp])) {
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                }
                session_write_close();
                header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/initial_setup.php');
                exit;
            } else {
                if (!isset($attempts[$userIp])) {
                    $attempts[$userIp] = ['attempts' => 0, 'last_attempt' => 0];
                }
                $attempts[$userIp]['attempts']++;
                $attempts[$userIp]['last_attempt'] = time();
                saveLoginAttempts($attempts);
                $remainingAttempts = MAX_LOGIN_ATTEMPTS - $attempts[$userIp]['attempts'];
                $message = 'Ungültiger Benutzername oder Passwort. Verbleibende Versuche: ' . $remainingAttempts;
                $msgType = 'red';
            }
            break;
    }
}

$pageTitle = 'Adminbereich - Login';
$pageHeader = 'Adminbereich';
$siteDescription = 'Administrationsbereich für die TwoKinds Fan-Übersetzung.';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <!-- Container nutzt nun die neue SCSS-Klasse -->
    <div class="admin-login-container">

        <?php if (!empty($message)) : ?>
            <!-- Nutzung der globalen Status-Klassen -->
            <div class="status-message status-<?php echo $msgType; ?> visible">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php
        $existingUsers = getUsers();
        if (empty($existingUsers)) :
            ?>
            <!-- INITIAL USER CREATION -->
            <h2>Ersten Admin erstellen</h2>
            <!-- FIX: Inline-Style durch Klasse .intro-text ersetzt -->
            <p class="intro-text">Willkommen! Es existiert noch kein Administrator.</p>

            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <div>
                    <label for="create_username">Benutzername:</label>
                    <input type="text" id="create_username" name="username" required autocomplete="username" placeholder="Neuer Benutzername">
                </div>
                <div>
                    <label for="create_password">Passwort:</label>
                    <input type="password" id="create_password" name="password" required autocomplete="new-password" placeholder="Sicheres Passwort">
                </div>
                <button type="submit" name="action" value="create_initial_user">Admin erstellen</button>
            </form>

        <?php else : ?>
            <!-- LOGIN FORM -->
            <h2>Anmeldung</h2>
            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <div>
                    <label for="login_username">Benutzername:</label>
                    <input type="text" id="login_username" name="username" required autocomplete="username" placeholder="Ihr Benutzername">
                </div>
                <div>
                    <label for="login_password">Passwort:</label>
                    <input type="password" id="login_password" name="password" required autocomplete="current-password" placeholder="Ihr Passwort">
                </div>
                <button type="submit" name="action" value="login">Einloggen</button>
            </form>
        <?php endif; ?>

    </div>
</article>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
