<?php
/**
 * Diese Seite erlaubt einem angemeldeten Benutzer, die eigenen Anmeldedaten zu ändern.
 * 
 * @file      /admin/management_login.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.0
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG (enthält Nonce und CSRF-Setup) ===
require_once __DIR__ . '/src/components/admin_init.php';

// --- Pfad zur Benutzerdatei ---
$usersFile = __DIR__ . '/../../../admin_users.json';

// --- Hilfsfunktionen (unverändert) ---
function getUsers(): array
{
    global $usersFile;
    if (!file_exists($usersFile) || filesize($usersFile) === 0)
        return [];
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

function saveUsers(array $users): bool
{
    global $usersFile;
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    return file_put_contents($usersFile, $jsonContent) !== false;
}

// --- Logik ---
$message = '';
$currentUser = $_SESSION['admin_username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_credentials') {
    // SICHERHEIT: CSRF-Token validieren
    verify_csrf_token();

    $oldPassword = $_POST['old_password'] ?? '';
    $newUsername = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';
    $users = getUsers();

    if ($newPassword !== $confirmNewPassword) {
        $message = '<p class="message-red">Die neuen Passwörter stimmen nicht überein.</p>';
    } elseif (!isset($users[$currentUser]) || !password_verify($oldPassword, $users[$currentUser]['passwordHash'])) {
        $message = '<p class="message-red">Das aktuelle Passwort ist inkorrekt.</p>';
    } elseif (empty($newUsername) && empty($newPassword)) {
        $message = '<p class="message-orange">Bitte geben Sie einen neuen Benutzernamen oder ein neues Passwort ein.</p>';
    } else {
        $userUpdated = false;
        $newUsersArray = $users;

        if (!empty($newUsername) && $newUsername !== $currentUser) {
            if (isset($newUsersArray[$newUsername])) {
                $message = '<p class="message-red">Neuer Benutzername ist bereits vergeben.</p>';
            } else {
                $newUsersArray[$newUsername] = $newUsersArray[$currentUser];
                unset($newUsersArray[$currentUser]);
                $_SESSION['admin_username'] = $newUsername;
                $currentUser = $newUsername;
                $userUpdated = true;
            }
        }

        if (!empty($newPassword) && empty($message)) {
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $newUsersArray[$currentUser]['passwordHash'] = $hashedNewPassword;
            $userUpdated = true;
        }

        if ($userUpdated && empty($message)) {
            if (saveUsers($newUsersArray)) {
                $message = '<p class="message-green">Anmeldedaten erfolgreich aktualisiert.</p>';
            } else {
                $message = '<p class="message-red">Fehler beim Speichern der neuen Anmeldedaten.</p>';
            }
        }
    }
}

// --- HTML-Struktur und Anzeige ---
$pageTitle = 'Adminbereich - Anmeldedaten ändern';
$pageHeader = 'Eigene Anmeldedaten ändern';
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
            background-color: #5cb85c;
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
            color: red;
        }

        .message-green {
            color: green;
        }

        .message-orange {
            color: orange;
        }

        .text-right {
            text-align: right;
        }

        .section-divider {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #eee;
        }

        .form-hr {
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
    </style>
    <div class="admin-form-container">
        <h2>Willkommen, <?php echo htmlspecialchars($currentUser); ?>!</h2>
        <p class="text-right"><a href="?action=logout&token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                class="logout-link">Logout</a></p>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <section class="section-divider">
            <h3>Benutzerdaten ändern</h3>
            <form id="change-credentials-form" action="management_login.php" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="change_credentials">
                <div>
                    <label for="new_username">Benutzername:</label>
                    <input type="text" id="new_username" name="new_username"
                        value="<?php echo htmlspecialchars($currentUser); ?>" autocomplete="username">
                </div>
                <div>
                    <label for="new_password">Neues Passwort (optional):</label>
                    <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                </div>
                <div>
                    <label for="confirm_new_password">Neues Passwort bestätigen:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" autocomplete="off">
                </div>
                <hr class="form-hr">
                <div>
                    <label for="old_password">Aktuelles Passwort (zur Bestätigung):</label>
                    <input type="password" id="old_password" name="old_password" required
                        autocomplete="current-password">
                </div>
                <button type="submit">Daten ändern</button>
            </form>
        </section>
    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    // Fügt eine einfache clientseitige Validierung hinzu, um zu prüfen, ob die neuen Passwörter übereinstimmen.
    document.getElementById('change-credentials-form').addEventListener('submit', function (event) {
        const newPassword = document.getElementById('new_password').value;
        const confirmNewPassword = document.getElementById('confirm_new_password').value;

        // Führe die Prüfung nur aus, wenn ein neues Passwort eingegeben wird.
        if (newPassword !== '' || confirmNewPassword !== '') {
            if (newPassword !== confirmNewPassword) {
                // Verwende eine benutzerfreundlichere Meldung statt alert()
                const messageContainer = document.querySelector('.message');
                // Erstelle eine neue Nachricht, falls kein Container existiert
                const messageDiv = messageContainer || document.createElement('div');
                messageDiv.className = 'message';
                messageDiv.innerHTML = '<p class="message-red">Die neuen Passwörter stimmen nicht überein.</p>';

                // Füge die Nachricht nur hinzu, wenn sie noch nicht da war
                if (!messageContainer) {
                    const form = document.getElementById('change-credentials-form');
                    form.parentNode.insertBefore(messageDiv, form);
                }

                event.preventDefault();
            }
        }
    });
</script>

<?php
$footerPath = __DIR__ . '/../src/layout/footer.php';
if (file_exists($footerPath)) {
    include $footerPath;
}
ob_end_flush();
?>