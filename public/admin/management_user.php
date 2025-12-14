<?php

/**
 * Diese Seite dient der Verwaltung von Benutzern (Hinzufügen, Löschen, Anzeigen).
 *
 * @file      ROOT/public/admin/management_user.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since 2.0.0 - 4.0.0
 *    ARCHITEKTUR & CORE
 *    - Umstellung auf die dynamische Path-Helfer-Klasse und zentrale Pfad-Konstanten.
 *    - Vollständige Integration der `init_admin.php` zur Zentralisierung der Admin-Logik.
 *
 *    UI & CLEANUP
 *    - Entfernung redundanter Elemente (z.B. doppelter Logout-Button).
 *    - Anpassung der Stile für ein einheitliches Design.
 *
 * @since 5.0.0
 * - refactor(UI): Inline-CSS entfernt und durch SCSS-Klassen (.user-management-container) ersetzt.
 * - refactor(Code): HTML-Struktur bereinigt und an 7-1 Pattern angepasst.
 * - style(UX): Icons und bessere visuelle Trennung der Sektionen hinzugefügt.
 * - fix(Security): `LOCK_EX` beim Speichern der Benutzerdatei hinzugefügt.
 * - style(UI): Layout der Willkommens-Nachricht optimiert.
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
    // WICHTIG: LOCK_EX verhindert, dass zwei Prozesse gleichzeitig schreiben und die Datei korrumpieren
    return file_put_contents($usersFile, $jsonContent, LOCK_EX) !== false;
}

// --- LOGIK ---
$message = '';
$messageType = 'info';
$currentUser = $_SESSION['admin_username'] ?? 'Unbekannt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_user':
            $newUsername = filter_input(INPUT_POST, 'add_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['add_password'] ?? '';
            $users = getUsers();
            if (empty($newUsername) || empty($newPassword)) {
                $message = 'Benutzername und Passwort dürfen nicht leer sein.';
                $messageType = 'red';
            } elseif (isset($users[$newUsername])) {
                $message = 'Benutzername "' . htmlspecialchars($newUsername) . '" existiert bereits.';
                $messageType = 'red';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $users[$newUsername] = ['passwordHash' => $hashedPassword];
                if (saveUsers($users)) {
                    $message = 'Benutzer "' . htmlspecialchars($newUsername) . '" erfolgreich hinzugefügt.';
                    $messageType = 'green';
                } else {
                    $message = 'Fehler beim Hinzufügen des Benutzers.';
                    $messageType = 'red';
                }
            }
            break;

        case 'delete_user':
            $userToDelete = filter_input(INPUT_POST, 'user_to_delete', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (empty($userToDelete)) {
                $message = 'Kein Benutzer zum Löschen ausgewählt.';
                $messageType = 'red';
            } elseif ($userToDelete === $currentUser) {
                $message = 'Sie können Ihren eigenen angemeldeten Benutzer nicht löschen.';
                $messageType = 'red';
            } else {
                $users = getUsers();
                if (isset($users[$userToDelete])) {
                    unset($users[$userToDelete]);
                    if (saveUsers($users)) {
                        $message = 'Benutzer "' . htmlspecialchars($userToDelete) . '" erfolgreich gelöscht.';
                        $messageType = 'green';
                    } else {
                        $message = 'Fehler beim Löschen des Benutzers.';
                        $messageType = 'red';
                    }
                } else {
                    $message = 'Benutzer "' . htmlspecialchars($userToDelete) . '" nicht gefunden.';
                    $messageType = 'red';
                }
            }
            break;
    }
}

// --- HTML-Struktur und Anzeige ---
$pageTitle = 'Adminbereich - Benutzerverwaltung';
$pageHeader = 'Benutzerverwaltung';
$robotsContent = 'noindex, nofollow';

require_once Path::getPartialTemplatePath('header.php');
?>

    <!-- Container nutzt die existierende SCSS-Klasse -->
    <div class="user-management-container">
        <h2>Benutzerverwaltung</h2>
        <p class="text-center mb-15">Angemeldet als: <strong><?php echo htmlspecialchars($currentUser); ?></strong></p>

        <?php if (!empty($message)) : ?>
            <div class="status-message status-<?php echo $messageType; ?> visible">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- SEKTION 1: NEUER BENUTZER -->
        <section class="section-divider">
            <h3><i class="fas fa-user-plus"></i> Neuen Benutzer hinzufügen</h3>
            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_user.php'; ?>" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="add_user">

                <div class="form-group">
                    <label for="add_username">Benutzername:</label>
                    <input type="text" id="add_username" name="add_username" required autocomplete="username" placeholder="Neuer Name">
                </div>

                <div class="form-group">
                    <label for="add_password">Passwort:</label>
                    <input type="password" id="add_password" name="add_password" required autocomplete="new-password" placeholder="Neues Passwort">
                </div>

                <button type="submit" class="button button-green">Benutzer hinzufügen</button>
            </form>
        </section>

        <!-- SEKTION 2: BENUTZERLISTE -->
        <section class="section-divider">
            <h3><i class="fas fa-users"></i> Verfügbare Benutzer</h3>
            <ul class="user-list">
                <?php
                $allUsers = getUsers();
                foreach ($allUsers as $user => $data) :
                    ?>
                    <li>
                        <span class="user-name"><?php echo htmlspecialchars($user); ?></span>

                        <?php if ($user !== $currentUser) : ?>
                            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_user.php'; ?>" method="POST" class="delete-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_to_delete" value="<?php echo htmlspecialchars($user); ?>">
                                <button type="submit" class="button delete-button" title="Benutzer löschen"><i class="fas fa-trash-alt"></i> Löschen</button>
                            </form>
                        <?php else : ?>
                            <span class="current-user-tag">(Aktuell angemeldet)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                const userToDelete = form.querySelector('input[name="user_to_delete"]').value;
                if (!confirm('Sind Sie sicher, dass Sie den Benutzer "' + userToDelete + '" unwiderruflich löschen möchten?')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
