<?php

/**
 * Diese Seite erlaubt einem angemeldeten Benutzer, die eigenen Anmeldedaten zu ändern.
 *
 * @file      ROOT/public/admin/management_login.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Vollständige Integration der `init_admin.php` zur Zentralisierung der Admin-Logik.
 *    - Code-Modernisierung und direkte Verwendung von Konstanten.
 *
 *    UI & CLEANUP
 *    - Entfernung redundanter Elemente (z.B. doppelter Logout-Button).
 *
 * @since 5.0.0
 * - refactor(UI): Inline-CSS entfernt und durch SCSS-Klasse .login-management-container ersetzt.
 * - refactor(Code): HTML-Struktur bereinigt, JS modernisiert (kein Inline-HTML im JS).
 * - fix(Standard): Nutzung globaler Status-Klassen (.status-message).
 * - fix(Security): `LOCK_EX` beim Speichern der Benutzerdatei hinzugefügt.
 * - style(UI): Layout der Willkommens-Nachricht an andere Admin-Seiten angepasst.
 */

declare(strict_types=1);

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === ZENTRALE ADMIN-INITIALISIERUNG ===
require_once __DIR__ . '/../../src/components/admin/init_admin.php';

// --- HILFSFUNKTIONEN ---
/**
 * Ruft die Liste aller Admin-Benutzer aus der JSON-Datei ab.
 */
function getUsers(): array
{
    $usersFile = Path::getSecretPath('admin_users.json');
    if (!file_exists($usersFile) || filesize($usersFile) === 0) {
        return [];
    }
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

/**
 * Speichert die Admin-Benutzer in der JSON-Datei.
 */
function saveUsers(array $users): bool
{
    $usersFile = Path::getSecretPath('admin_users.json');
    $dir = dirname($usersFile);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        error_log("Fehler: Konnte Verzeichnis für Benutzerdatei nicht erstellen: " . $dir);
        return false;
    }
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    // WICHTIG: LOCK_EX verhindert Datenkorruption bei gleichzeitigem Zugriff
    return file_put_contents($usersFile, $jsonContent, LOCK_EX) !== false;
}

// --- LOGIK ---
$message = '';
$messageType = 'info';
$currentUser = $_SESSION['admin_username'] ?? 'Unbekannt';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_credentials') {
    verify_csrf_token();

    $oldPassword = $_POST['old_password'] ?? '';
    $newUsername = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';
    $users = getUsers();

    if ($newPassword !== $confirmNewPassword) {
        $message = 'Die neuen Passwörter stimmen nicht überein.';
        $messageType = 'red';
    } elseif (!isset($users[$currentUser]) || !password_verify($oldPassword, $users[$currentUser]['passwordHash'])) {
        $message = 'Das aktuelle Passwort ist inkorrekt.';
        $messageType = 'red';
    } elseif (empty($newUsername) && empty($newPassword)) {
        $message = 'Bitte geben Sie einen neuen Benutzernamen oder ein neues Passwort ein.';
        $messageType = 'orange';
    } else {
        $userUpdated = false;
        $newUsersArray = $users;

        // Benutzernamen ändern
        if (!empty($newUsername) && $newUsername !== $currentUser) {
            if (isset($newUsersArray[$newUsername])) {
                $message = 'Neuer Benutzername ist bereits vergeben.';
                $messageType = 'red';
            } else {
                $newUsersArray[$newUsername] = $newUsersArray[$currentUser];
                unset($newUsersArray[$currentUser]);
                $_SESSION['admin_username'] = $newUsername;
                $currentUser = $newUsername; // Update für Anzeige
                $userUpdated = true;
            }
        }

        // Passwort ändern (nur wenn kein Fehler vorliegt)
        if (!empty($newPassword) && empty($message)) {
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $newUsersArray[$currentUser]['passwordHash'] = $hashedNewPassword;
            $userUpdated = true;
        }

        if ($userUpdated && empty($message)) {
            if (saveUsers($newUsersArray)) {
                $message = 'Anmeldedaten erfolgreich aktualisiert.';
                $messageType = 'green';
            } else {
                $message = 'Fehler beim Speichern der neuen Anmeldedaten.';
                $messageType = 'red';
            }
        }
    }
}

// --- HTML-Struktur und Anzeige ---
$pageTitle = 'Adminbereich - Anmeldedaten ändern';
$pageHeader = 'Eigene Anmeldedaten ändern';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <!-- Container nutzt die existierende SCSS-Klasse -->
    <div class="login-management-container">
        <h2>Zugangsdaten verwalten</h2>
        <!-- Konsistente Anzeige des eingeloggten Users -->
        <p class="text-center mb-15">Angemeldet als: <strong><?php echo htmlspecialchars($currentUser); ?></strong></p>

        <!-- Status Message Container (PHP & JS) -->
        <div id="status-message-box" class="status-message status-<?php echo $messageType; ?> <?php echo !empty($message) ? 'visible' : ''; ?>">
            <?php echo $message; ?>
        </div>

        <section class="section-divider">
            <h3>Daten ändern</h3>
            <form id="change-credentials-form"
                  action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_login.php' . $dateiendungPHP; ?>"
                  method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="change_credentials">

                <div>
                    <label for="new_username">Neues Login (Benutzername):</label>
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

                <button type="submit" class="button button-green">Daten ändern</button>
            </form>
        </section>
    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.getElementById('change-credentials-form').addEventListener('submit', function (event) {
        const newPassword = document.getElementById('new_password').value;
        const confirmNewPassword = document.getElementById('confirm_new_password').value;
        const messageBox = document.getElementById('status-message-box');

        if (newPassword !== '' || confirmNewPassword !== '') {
            if (newPassword !== confirmNewPassword) {
                event.preventDefault();

                // JS Validation Feedback
                messageBox.textContent = 'Die neuen Passwörter stimmen nicht überein.';
                messageBox.className = 'status-message status-red visible';

                // Optional: Scroll to message
                messageBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
