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
 * @version   4.0.0
 * @since     1.1.0 Umstellung auf zentrale Pfad-Konstanten und direkte Verwendung.
 * @since     2.0.0 Vollständige Integration der init_admin.php, Entfernung des redundanten Logout-Buttons und Stil-Anpassungen.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 */

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
    return file_put_contents($usersFile, $jsonContent) !== false;
}

// --- LOGIK ---
$message = '';
$currentUser = $_SESSION['admin_username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_user':
            $newUsername = filter_input(INPUT_POST, 'add_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['add_password'] ?? '';
            $users = getUsers();
            if (empty($newUsername) || empty($newPassword)) {
                $message = '<p class="status-message status-red">Benutzername und Passwort für den neuen Benutzer dürfen nicht leer sein.</p>';
            } elseif (isset($users[$newUsername])) {
                $message = '<p class="status-message status-red">Benutzername "' . htmlspecialchars($newUsername) . '" existiert bereits.</p>';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $users[$newUsername] = ['passwordHash' => $hashedPassword];
                if (saveUsers($users)) {
                    $message = '<p class="status-message status-green">Benutzer "' . htmlspecialchars($newUsername) . '" erfolgreich hinzugefügt.</p>';
                } else {
                    $message = '<p class="status-message status-red">Fehler beim Hinzufügen des Benutzers.</p>';
                }
            }
            break;

        case 'delete_user':
            $userToDelete = filter_input(INPUT_POST, 'user_to_delete', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            if (empty($userToDelete)) {
                $message = '<p class="status-message status-red">Kein Benutzer zum Löschen ausgewählt.</p>';
            } elseif ($userToDelete === $currentUser) {
                $message = '<p class="status-message status-red">Sie können Ihren eigenen angemeldeten Benutzer nicht löschen.</p>';
            } else {
                $users = getUsers();
                if (isset($users[$userToDelete])) {
                    unset($users[$userToDelete]);
                    if (saveUsers($users)) {
                        $message = '<p class="status-message status-green">Benutzer "' . htmlspecialchars($userToDelete) . '" erfolgreich gelöscht.</p>';
                    } else {
                        $message = '<p class="status-message status-red">Fehler beim Löschen des Benutzers.</p>';
                    }
                } else {
                    $message = '<p class="status-message status-red">Benutzer "' . htmlspecialchars($userToDelete) . '" nicht gefunden.</p>';
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

        .admin-form-container button[name="action"][value="add_user"] {
            background-color: #f0ad4e;
        }

        .admin-form-container .delete-button {
            background-color: #dc3545;
            font-size: 14px;
            padding: 5px 10px;
        }

        .status-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .status-message p {
            margin: 0;
        }

        .status-red {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-green {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        ul {
            list-style-type: none;
            padding-left: 0;
        }

        li {
            margin-bottom: 8px;
            padding: 5px;
            border-bottom: 1px dotted #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        body.theme-night li {
            border-bottom-color: #555;
        }

        span.user-name {
            body.theme-night span.user-name {
                font-weight: bold;
                color: inherit;
            }

            .text-right {
                text-align: right;
            }
        }

        .section-divider {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #eee;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .delete-form {
            margin: 0;
        }
    </style>
    <div class="admin-form-container">
        <h2>Willkommen, <?php echo htmlspecialchars($currentUser); ?>!</h2>

        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <section id="manage-users" class="section-divider">
            <h3>Neuen Benutzer hinzufügen</h3>
            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_user.php'; ?>" method="POST"
                class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="add_user">
                <div>
                    <label for="add_username">Benutzername für neuen Benutzer:</label>
                    <input type="text" id="add_username" name="add_username" required autocomplete="username">
                </div>
                <div>
                    <label for="add_password">Passwort für neuen Benutzer:</label>
                    <input type="password" id="add_password" name="add_password" required autocomplete="new-password">
                </div>
                <button type="submit" class="button">Benutzer hinzufügen</button>
            </form>
        </section>

        <section class="section-divider">
            <h3>Verfügbare Benutzer</h3>
            <ul>
                <?php
                $allUsers = getUsers();
                foreach ($allUsers as $user => $data):
                    ?>
                    <li>
                        <span class="user-name"><?php echo htmlspecialchars($user); ?></span>
                        <?php if ($user !== $currentUser): ?>
                            <form action="<?php echo DIRECTORY_PUBLIC_ADMIN_URL . '/management_user.php'; ?>" method="POST"
                                class="delete-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_to_delete" value="<?php echo htmlspecialchars($user); ?>">
                                <button type="submit" class="button delete-button">Löschen</button>
                            </form>
                        <?php else: ?>
                            <span class="current-user-tag">(Sie)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</article>

<script nonce="<?php echo htmlspecialchars($nonce); ?>">
    document.addEventListener('DOMContentLoaded', function () {
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                const userToDelete = form.querySelector('input[name="user_to_delete"]').value;
                if (!confirm('Sind Sie sicher, dass Sie den Benutzer "' + userToDelete + '" löschen möchten?')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>

<?php require_once Path::getPartialTemplatePath('footer.php');
ob_end_flush(); ?>