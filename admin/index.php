<?php
/**
 * Dies ist die Login-Seite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung und die Erstellung des ersten Admin-Benutzers.
 * Diese Version ist gehärtet mit CSP (Nonce) und CSRF-Schutz.
 * 
 * @file      /admin/index.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   2.0.0
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: index.php (Login) wird geladen.");

ob_start();

// --- SICHERHEITSVERBESSERUNG 1: CSP mit Nonce & weitere Header (aus admin_init.php adaptiert) ---
$nonce = bin2hex(random_bytes(16));
$csp = [
    'default-src' => ["'self'"],
    // ERWEITERT: Externe Skript-Quellen für den Admin-Bereich hinzugefügt.
    'script-src' => ["'self'", "'nonce-{$nonce}'", "https://code.jquery.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://www.googletagmanager.com", "https://cdn.twokinds.keenspot.com"],
    // ERWEITERT: Externe Stil-Quellen hinzugefügt.
    'style-src' => ["'self'", "'nonce-{$nonce}'", "https://cdn.twokinds.keenspot.com", "https://cdnjs.cloudflare.com", "https://cdn.jsdelivr.net", "https://fonts.googleapis.com"],
    // ERWEITERT: Externe Schrift-Quellen hinzugefügt.
    'font-src' => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com", "https://fonts.googleapis.com"],
    // ERWEITERT: Externe Bild-Quellen hinzugefügt.
    'img-src' => ["'self'", "data:", "https://cdn.twokinds.keenspot.com", "https://placehold.co"],
    // ERWEITERT: Google Analytics Domain für das Senden von Tracking-Daten hinzugefügt.
    'connect-src' => ["'self'", "https://cdn.twokinds.keenspot.com", "https://*.google-analytics.com"],
    'object-src' => ["'none'"],
    'frame-ancestors' => ["'self'"],
    'base-uri' => ["'self'"],
    'form-action' => ["'self'"],
];

$cspHeader = '';
foreach ($csp as $directive => $sources) {
    $cspHeader .= $directive . ' ' . implode(' ', $sources) . '; ';
}
header("Content-Security-Policy: " . trim($cspHeader));
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");

// --- SICHERHEITSVERBESSERUNG 2: Strikte Session-Konfiguration (aus admin_init.php) ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// --- SICHERHEITSVERBESSERUNG 3: CSRF-Schutz (aus admin_init.php adaptiert) ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Überprüft den CSRF-Token für POST-Anfragen. Bricht bei Fehler ab.
 */
function verify_csrf_token()
{
    global $debugMode;
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("SECURITY WARNING: Ungültiger CSRF-Token bei Login-Versuch von IP: " . $_SERVER['REMOTE_ADDR']);
        die('Ungültige Anfrage. Bitte versuchen Sie es erneut.');
    }
    if ($debugMode)
        error_log("DEBUG: CSRF-Token erfolgreich validiert.");
}

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
    if (!file_exists($usersFile) || filesize($usersFile) === 0)
        return [];
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

function saveUsers(array $users): bool
{
    global $usersFile, $debugMode;
    $dir = dirname($usersFile);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        error_log("Fehler: Konnte Verzeichnis für Benutzerdatei nicht erstellen: " . $dir);
        return false;
    }
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents($usersFile, $jsonContent) !== false;
}

function getLoginAttempts(): array
{
    global $loginAttemptsFile;
    if (!file_exists($loginAttemptsFile))
        return [];
    $content = file_get_contents($loginAttemptsFile);
    $attempts = json_decode($content, true);
    return is_array($attempts) ? $attempts : [];
}

function saveLoginAttempts(array $attempts): bool
{
    global $loginAttemptsFile;
    $dir = dirname($loginAttemptsFile);
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    $jsonContent = json_encode($attempts, JSON_PRETTY_PRINT);
    return file_put_contents($loginAttemptsFile, $jsonContent) !== false;
}

// --- Logik ---
$message = '';

// KORREKTUR: Inline-Stil durch Klasse ersetzt.
if (isset($_GET['reason']) && $_GET['reason'] === 'session_expired') {
    $message = '<p class="message-orange">Ihre Sitzung ist aufgrund von Inaktivität abgelaufen. Bitte melden Sie sich erneut an.</p>';
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: management_user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SICHERHEIT: CSRF-Token bei jeder POST-Anfrage validieren
    verify_csrf_token();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_initial_user':
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? '';
            $users = getUsers();
            if (empty($users)) {
                if (empty($username) || empty($password)) {
                    // KORREKTUR: Inline-Stil durch Klasse ersetzt.
                    $message = '<p class="message-red">Benutzername und Passwort dürfen nicht leer sein.</p>';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = ['passwordHash' => $hashedPassword];
                    if (saveUsers($users)) {
                        // KORREKTUR: Inline-Stil durch Klasse ersetzt.
                        $message = '<p class="message-green">Erster Admin-Benutzer erfolgreich erstellt. Bitte melden Sie sich an.</p>';
                        header('Location: index.php');
                        exit;
                    } else {
                        // KORREKTUR: Inline-Stil durch Klasse ersetzt.
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
                    // KORREKTUR: Inline-Stil durch Klasse ersetzt.
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
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time();
                if (isset($attempts[$userIp])) {
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                }
                header('Location: management_user.php');
                exit;
            } else {
                if (!isset($attempts[$userIp])) {
                    $attempts[$userIp] = ['attempts' => 0, 'last_attempt' => 0];
                }
                $attempts[$userIp]['attempts']++;
                $attempts[$userIp]['last_attempt'] = time();
                saveLoginAttempts($attempts);
                $remainingAttempts = MAX_LOGIN_ATTEMPTS - $attempts[$userIp]['attempts'];
                // KORREKTUR: Inline-Stil durch Klasse ersetzt.
                $message = '<p class="message-red">Ungültiger Benutzername oder Passwort. Verbleibende Versuche: ' . $remainingAttempts . '</p>';
            }
            break;
    }
}

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

        .main-container.lights-off .admin-form-container {
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

        /* Klassen für Nachrichtenfarben */
        .message-red {
            color: red;
        }

        .message-green {
            color: green;
        }

        .message-orange {
            color: orange;
        }

        /* KORREKTUR: Klasse für Formular-Layout */
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
            <form action="index.php" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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
            <form action="index.php" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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