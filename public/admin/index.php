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
 * @version   4.0.1
 * @since     2.1.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     3.0.0 Vollständige Integration der init_admin.php, Entfernung redundanter Sicherheits-Header und Funktionen.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     4.0.1 Behebt das Problem mit der Race Condition. session_write_close(); -> Session-Daten explizit schreiben, BEVOR wir umleiten.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
// Lädt alle Konfigurationen, Sicherheits-Header, Session-Einstellungen und CSRF-Schutz.
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

if ($debugMode)
    error_log("DEBUG: index.php (Login) wird geladen.");

// --- Konfiguration für Brute-Force-Schutz ---
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_BLOCK_SECONDS', 900);

// --- Hilfsfunktionen (jetzt mit Path-Klasse) ---
function getUsers(): array
{
    $filePath = Path::getSecretPath('admin_users.json');
    if (!file_exists($filePath) || filesize($filePath) === 0)
        return [];
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
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

function getLoginAttempts(): array
{
    $filePath = Path::getSecretPath('login_attempts.json');
    if (!file_exists($filePath))
        return [];
    $content = file_get_contents($filePath);
    $attempts = json_decode($content, true);
    return is_array($attempts) ? $attempts : [];
}

function saveLoginAttempts(array $attempts): bool
{
    $filePath = Path::getSecretPath('login_attempts.json');
    $dir = dirname($filePath);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    $jsonContent = json_encode($attempts, JSON_PRETTY_PRINT);
    return file_put_contents($filePath, $jsonContent) !== false;
}

// --- Logik ---
$message = '';

if (isset($_GET['reason'])) {
    if ($_GET['reason'] === 'session_expired') {
        $message = '<p class="message-orange">Ihre Sitzung ist aufgrund von Inaktivität abgelaufen. Bitte melden Sie sich erneut an.</p>';
    }
    if ($_GET['reason'] === 'session_hijacked') {
        $message = '<p class="message-red">Ihre Sitzung wurde aus Sicherheitsgründen beendet. Bitte melden Sie sich erneut an.</p>';
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
                    $message = '<p class="message-red">Benutzername und Passwort dürfen nicht leer sein.</p>';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = ['passwordHash' => $hashedPassword];
                    if (saveUsers($users)) {
                        $message = '<p class="message-green">Erster Admin-Benutzer erfolgreich erstellt. Bitte melden Sie sich an.</p>';
                        header('Location: ' . DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'); // Leitet um, um die "Erstellen"-Ansicht zu entfernen
                        exit;
                    } else {
                        $message = '<p class="message-red">Fehler beim Speichern des Benutzers.</p>';
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
                    $message = '<p class="message-red">Zu viele fehlgeschlagene Login-Versuche. Bitte warten Sie noch ' . ceil($remainingTime / 60) . ' Minute(n).</p>';
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
                session_regenerate_id(true); // Schutz vor Session Fixation beim Login
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();

                // Session-Fingerprint setzen (aus admin_init)
                $_SESSION['session_fingerprint'] = md5(($_SERVER['HTTP_USER_AGENT'] ?? '') . (substr($_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.'))));

                if (isset($attempts[$userIp])) {
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                }
                // NEU: Session-Daten explizit schreiben, BEVOR wir umleiten.
                // Das behebt die Race Condition.
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
                $message = '<p class="message-red">Ungültiger Benutzername oder Passwort. Verbleibende Versuche: ' . $remainingAttempts . '</p>';
            }
            break;
    }
}

$pageTitle = 'Adminbereich - Login';
$pageHeader = 'Adminbereich - Login';
$siteDescription = 'Administrationsbereich für die TwoKinds Fan-Übersetzung.';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>
<article>
    <style nonce="<?php echo htmlspecialchars($nonce); ?>">
        .admin-form-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid rgba(221, 221, 221, 0.2);
            border-radius: 8px;
            background-color: rgba(240, 240, 240, 0.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        body.theme-night .admin-form-container {
            background-color: rgba(30, 30, 30, 0.2);
            border-color: rgba(80, 80, 80, 0.15);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .admin-form-container label {
            color: #333;
        }

        body.theme-night .admin-form-container label {
            color: #efefef !important;
        }

        .admin-form-container input[type="text"],
        .admin-form-container input[type="password"] {
            width: calc(100% - 18px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            color: #333;
        }

        .main-container.lights-off .admin-form-container input[type="text"],
        .main-container.lights-off .admin-form-container input[type="password"] {
            background-color: #444;
            color: #f0f0f0;
            border-color: #666;
        }

        .admin-form-container button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            color: white;
        }

        .admin-form-container button[value="create_initial_user"] {
            background-color: #4CAF50;
        }

        .admin-form-container button[value="login"] {
            background-color: #008CBA;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .message p {
            margin: 0;
        }

        .message-red {
            color: #a94442;
            background-color: #f2dede;
            border: 1px solid #ebccd1;
        }

        .message-green {
            color: #3c763d;
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
        }

        .message-orange {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
    </style>
    <div class="admin-form-container">
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php
        $existingUsers = getUsers();
        if (empty($existingUsers)):
            ?>
            <h2>Ersten Admin-Benutzer erstellen</h2>
            <p>Es ist noch kein Admin-Benutzer vorhanden. Bitte erstellen Sie einen.</p>
            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <div>
                    <label for="create_username">Benutzername:</label>
                    <input type="text" id="create_username" name="username" required autocomplete="username">
                </div>
                <div>
                    <label for="create_password">Passwort:</label>
                    <input type="password" id="create_password" name="password" required autocomplete="new-password">
                </div>
                <button type="submit" name="action" value="create_initial_user" class="button">Admin erstellen</button>
            </form>
        <?php else: ?>
            <h2>Login</h2>
            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/index.php'; ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <div>
                    <label for="login_username">Benutzername:</label>
                    <input type="text" id="login_username" name="username" required autocomplete="username">
                </div>
                <div>
                    <label for="login_password">Passwort:</label>
                    <input type="password" id="login_password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" name="action" value="login" class="button">Login</button>
            </form>
        <?php endif; ?>
    </div>
</article>
<?php require_once Path::getPartialTemplatePath('footer.php');
ob_end_flush(); ?>