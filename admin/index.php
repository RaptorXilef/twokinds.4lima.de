<?php
/**
 * Dies ist die Login-Seite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung und die Erstellung des ersten Admin-Benutzers.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: index.php (Login) wird geladen.");

ob_start();
session_start();

// Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
require_once __DIR__ . '/src/components/security_check.php';

// --- Konfiguration für Brute-Force-Schutz ---
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_BLOCK_SECONDS', 900);

// --- Pfade zu den Datendateien ---
$usersFile = __DIR__ . '/../../../admin_users.json';
$loginAttemptsFile = __DIR__ . '/../../../login_attempts.json';

// --- Hilfsfunktionen (unverändert) ---
function getUsers(): array
{
    global $usersFile, $debugMode;
    if (!file_exists($usersFile))
        return [];
    if (filesize($usersFile) === 0)
        return [];
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

function saveUsers(array $users): bool
{
    global $usersFile, $debugMode;
    $dir = dirname($usersFile);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            error_log("Fehler: Konnte Verzeichnis für Benutzerdatei nicht erstellen: " . $dir);
            return false;
        }
    }
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents($usersFile, $jsonContent) !== false;
}

function getLoginAttempts(): array
{
    global $loginAttemptsFile;
    if (!file_exists($loginAttemptsFile)) {
        return [];
    }
    $content = file_get_contents($loginAttemptsFile);
    $attempts = json_decode($content, true);
    return is_array($attempts) ? $attempts : [];
}

function saveLoginAttempts(array $attempts): bool
{
    global $loginAttemptsFile;
    $dir = dirname($loginAttemptsFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $jsonContent = json_encode($attempts, JSON_PRETTY_PRINT);
    return file_put_contents($loginAttemptsFile, $jsonContent) !== false;
}

// --- Logik ---
$message = '';

if (isset($_GET['reason']) && $_GET['reason'] === 'session_expired') {
    $message = '<p style="color: orange;">Ihre Sitzung ist aufgrund von Inaktivität abgelaufen. Bitte melden Sie sich erneut an.</p>';
}

// Wenn bereits eingeloggt, direkt zum Dashboard (user management) weiterleiten
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: management_user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_initial_user':
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? '';
            $users = getUsers();
            if (empty($users)) {
                if (empty($username) || empty($password)) {
                    $message = '<p style="color: red;">Benutzername und Passwort dürfen nicht leer sein.</p>';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = ['passwordHash' => $hashedPassword];
                    if (saveUsers($users)) {
                        $message = '<p style="color: green;">Erster Admin-Benutzer erfolgreich erstellt. Bitte melden Sie sich an.</p>';
                        header('Location: index.php');
                        exit;
                    } else {
                        $message = '<p style="color: red;">Fehler beim Speichern des Benutzers.</p>';
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
                    $message = '<p style="color: red;">Zu viele fehlgeschlagene Login-Versuche. Bitte warten Sie noch ' . ceil($remainingTime / 60) . ' Minute(n).</p>';
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
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();
                if (isset($attempts[$userIp])) {
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                }
                header('Location: management_user.php'); // Weiterleitung nach erfolgreichem Login
                exit;
            } else {
                if (!isset($attempts[$userIp])) {
                    $attempts[$userIp] = ['attempts' => 0, 'last_attempt' => 0];
                }
                $attempts[$userIp]['attempts']++;
                $attempts[$userIp]['last_attempt'] = time();
                saveLoginAttempts($attempts);
                $remainingAttempts = MAX_LOGIN_ATTEMPTS - $attempts[$userIp]['attempts'];
                $message = '<p style="color: red;">Ungültiger Benutzername oder Passwort. Verbleibende Versuche: ' . $remainingAttempts . '</p>';
            }
            break;
    }
}

// --- HTML-Struktur und Anzeige ---
$pageTitle = 'Adminbereich - Login';
$pageHeader = 'Adminbereich - Login';
$siteDescription = 'Administrationsbereich für die TwoKinds Fan-Übersetzung.';
$robotsContent = 'noindex, nofollow';
$headerPath = __DIR__ . '/../src/layout/header.php';
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden.');
}
?>
<article>
    <style>
        /* Stile bleiben hier für Konsistenz, können aber in eine zentrale CSS-Datei ausgelagert werden */
        .admin-form-container { max-width: 500px; margin: 20px auto; padding: 20px; border: 1px solid rgba(221, 221, 221, 0.2); border-radius: 8px; background-color: rgba(240, 240, 240, 0.05); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        .main-container.lights-off .admin-form-container { background-color: rgba(30, 30, 30, 0.2); border-color: rgba(80, 80, 80, 0.15); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .admin-form-container label { color: #333; }
        body.theme-night .admin-form-container label { color: #efefef !important; }
        .admin-form-container input[type="text"], .admin-form-container input[type="password"] { width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff; color: #333; }
        .main-container.lights-off .admin-form-container input[type="text"], .main-container.lights-off .admin-form-container input[type="password"] { background-color: #444; color: #f0f0f0; border-color: #666; }
        .admin-form-container button { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; color: white; }
        .admin-form-container button[value="create_initial_user"] { background-color: #4CAF50; }
        .admin-form-container button[value="login"] { background-color: #008CBA; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 5px; font-weight: bold; }
        .message p { margin: 0; }
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
                    <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <label for="create_username">Benutzername:</label>
                            <input type="text" id="create_username" name="username" required autocomplete="username">
                        </div>
                        <div>
                            <label for="create_password">Passwort:</label>
                            <input type="password" id="create_password" name="password" required autocomplete="new-password">
                        </div>
                        <button type="submit" name="action" value="create_initial_user">Admin erstellen</button>
                    </form>
        <?php else: ?>
                    <h2>Login</h2>
                    <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <label for="login_username">Benutzername:</label>
                            <input type="text" id="login_username" name="username" required autocomplete="username">
                        </div>
                        <div>
                            <label for="login_password">Passwort:</label>
                            <input type="password" id="login_password" name="password" required autocomplete="current-password">
                        </div>
                        <button type="submit" name="action" value="login">Login</button>
                    </form>
        <?php endif; ?>
    </div>
</article>
<?php
$footerPath = __DIR__ . '/../src/layout/footer.php';
if (file_exists($footerPath)) {
    include $footerPath;
}
ob_end_flush();
?>

