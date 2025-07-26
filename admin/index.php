<?php
/**
 * Dies ist die Hauptseite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung, Abmeldung, die Änderung der Benutzerdaten, das Hinzufügen und Löschen neuer Benutzer.
 * Daten werden zu Demonstrationszwecken in einer lokalen JSON-Datei gespeichert.
 *
 * SICHERHEITSHINWEIS: Diese Dateispeicherung ist NICHT SICHER für eine Produktionsumgebung!
 * Passwörter werden gehasht, aber die Methode ist anfällig für direkte Dateizugriffe.
 * Für eine echte Anwendung ist eine Datenbanklösung erforderlich.
 */

// Starte die PHP-Sitzung. Dies ist notwendig, um den Anmeldestatus zu speichern.
session_start();

// --- Pfad zur simulierten Benutzerdatenbank (JSON-Datei) ---
// WICHTIG: Die Datei liegt nun außerhalb des öffentlichen Web-Verzeichnisses für erhöhte Sicherheit.
// '__DIR__' ist das Verzeichnis der aktuellen Datei (z.B. /Stammverzeichnis/default-website/twokinds/admin/).
// Wir müssen drei Ebenen nach oben gehen, um zum Server-Stammverzeichnis zu gelangen.
$usersFile = __DIR__ . '/../../../admin_users.json';

// --- Hilfsfunktionen für die Benutzerverwaltung (Simulierte Datenbankzugriffe) ---

/**
 * Liest die Benutzerdaten aus der JSON-Datei.
 * @return array Ein assoziatives Array der Benutzerdaten oder ein leeres Array, wenn die Datei nicht existiert oder leer ist.
 */
function getUsers(): array
{
    global $usersFile;
    if (!file_exists($usersFile) || filesize($usersFile) === 0) {
        return [];
    }
    // Stelle sicher, dass die Datei lesbar ist
    if (!is_readable($usersFile)) {
        error_log("Fehler: Benutzerdatei nicht lesbar: " . $usersFile);
        return [];
    }
    $content = file_get_contents($usersFile);
    $users = json_decode($content, true);
    return is_array($users) ? $users : [];
}

/**
 * Speichert die Benutzerdaten in die JSON-Datei.
 * @param array $users Das zu speichernde Benutzerarray.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveUsers(array $users): bool
{
    global $usersFile;
    // Stelle sicher, dass das Verzeichnis schreibbar ist, bevor geschrieben wird.
    // Hier prüfen wir das Verzeichnis, in dem die Users-Datei liegen soll.
    if (!is_writable(dirname($usersFile))) {
        error_log("Fehler: Verzeichnis für Benutzerdatei nicht schreibbar: " . dirname($usersFile));
        return false;
    }
    // JSON_PRETTY_PRINT für bessere Lesbarkeit der Datei.
    $result = file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    if ($result === false) {
        error_log("Fehler beim Schreiben der Benutzerdatei: " . $usersFile);
    }
    return $result !== false;
}

// --- Authentifizierungs- und Verwaltungslogik ---

$message = ''; // Wird für Erfolgs- oder Fehlermeldungen verwendet.

// Überprüfen, ob ein Benutzer angemeldet ist.
$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$currentUser = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : '';

// Logout-Funktion (wird über GET-Parameter ausgelöst)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();     // Entfernt alle Session-Variablen
    session_destroy();   // Zerstört die Session
    $loggedIn = false;
    $currentUser = '';
    $message = '<p style="color: green;">Erfolgreich abgemeldet.</p>';
    header('Location: index.php'); // Weiterleitung zur Login-Seite
    exit;
}

// Bearbeitung von POST-Anfragen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // Initialen Admin-Benutzer erstellen (nur wenn noch keine Benutzer existieren)
        case 'create_initial_user':
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? ''; // Passwörter werden gehasht, daher nicht filtern.

            $users = getUsers();
            if (empty($users)) { // Nur erstellen, wenn keine Benutzer vorhanden sind
                if (empty($username) || empty($password)) {
                    $message = '<p style="color: red;">Benutzername und Passwort dürfen nicht leer sein.</p>';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = ['passwordHash' => $hashedPassword];
                    if (saveUsers($users)) {
                        $message = '<p style="color: green;">Erster Admin-Benutzer erfolgreich erstellt. Bitte melden Sie sich an.</p>';
                        // Weiterleitung zur Login-Seite
                        header('Location: index.php');
                        exit;
                    } else {
                        $message = '<p style="color: red;">Fehler beim Speichern des Benutzers。</p>';
                    }
                }
            } else {
                $message = '<p style="color: red;">Ein Admin-Benutzer existiert bereits. Initialisierung nicht möglich。</p>';
            }
            break;

        // Anmeldefunktion
        case 'login':
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? '';

            $users = getUsers();
            if (isset($users[$username]) && password_verify($password, $users[$username]['passwordHash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $loggedIn = true;
                $currentUser = $username;
                $message = '<p style="color: green;">Erfolgreich angemeldet！</p>';
                // Weiterleitung, um Formularerneutsendung zu vermeiden
                header('Location: index.php');
                exit;
            } else {
                $message = '<p style="color: red;">Ungültiger Benutzername oder Passwort。</p>';
            }
            break;

        // Benutzername/Passwort ändern (nur für angemeldete Benutzer)
        case 'change_credentials':
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Ihre Daten zu ändern。</p>';
                break;
            }
            $currentUsername = $_SESSION['admin_username'];
            $oldPassword = $_POST['old_password'] ?? '';
            $newUsername = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['new_password'] ?? '';

            $users = getUsers();

            // Überprüfe altes Passwort
            if (!isset($users[$currentUsername]) || !password_verify($oldPassword, $users[$currentUsername]['passwordHash'])) {
                $message = '<p style="color: red;">Altes Passwort ist inkorrekt。</p>';
            } elseif (empty($newUsername) && empty($newPassword)) {
                $message = '<p style="color: orange;">Bitte geben Sie einen neuen Benutzernamen oder ein neues Passwort ein。</p>';
            } else {
                $userUpdated = false;
                $newUsersArray = $users; // Arbeitskopie der Benutzer

                // Wenn Benutzername geändert wird
                if (!empty($newUsername) && $newUsername !== $currentUsername) {
                    if (isset($newUsersArray[$newUsername])) {
                        $message = '<p style="color: red;">Neuer Benutzername ist bereits vergeben。</p>';
                        break; // Abbruch, wenn Benutzername schon existiert
                    }
                    // Verschiebe den alten Eintrag zum neuen Benutzernamen
                    $newUsersArray[$newUsername] = $newUsersArray[$currentUsername];
                    unset($newUsersArray[$currentUsername]);
                    $_SESSION['admin_username'] = $newUsername; // Session aktualisieren
                    $currentUser = $newUsername;
                    $userUpdated = true;
                }

                // Wenn Passwort geändert wird
                if (!empty($newPassword)) {
                    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $newUsersArray[$currentUser]['passwordHash'] = $hashedNewPassword;
                    $userUpdated = true;
                }

                if ($userUpdated) {
                    if (saveUsers($newUsersArray)) {
                        $message = '<p style="color: green;">Anmeldedaten erfolgreich aktualisiert。</p>';
                    } else {
                        $message = '<p style="color: red;">Fehler beim Speichern der neuen Anmeldedaten。</p>';
                    }
                } else {
                    $message = '<p style="color: orange;">Keine Änderungen vorgenommen (Benutzername und/oder Passwort nicht unterschiedlich)。</p>';
                }
            }
            break;

        // Neuen Benutzer hinzufügen (nur für angemeldete Benutzer)
        case 'add_user':
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Benutzer hinzuzufügen。</p>';
                break;
            }
            $newUsername = filter_input(INPUT_POST, 'add_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['add_password'] ?? '';

            $users = getUsers();
            if (empty($newUsername) || empty($newPassword)) {
                $message = '<p style="color: red;">Benutzername und Passwort für den neuen Benutzer dürfen nicht leer sein。</p>';
            } elseif (isset($users[$newUsername])) {
                $message = '<p style="color: red;">Benutzername "' . htmlspecialchars($newUsername) . '" existiert bereits。</p>';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $users[$newUsername] = ['passwordHash' => $hashedPassword];
                if (saveUsers($users)) {
                    $message = '<p style="color: green;">Benutzer "' . htmlspecialchars($newUsername) . '" erfolgreich hinzugefügt。</p>';
                } else {
                    $message = '<p style="color: red;">Fehler beim Hinzufügen des Benutzers。</p>';
                }
            }
            break;

        // Benutzer löschen (nur für angemeldete Benutzer)
        case 'delete_user':
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Benutzer zu löschen。</p>';
                break;
            }
            $userToDelete = filter_input(INPUT_POST, 'user_to_delete', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (empty($userToDelete)) {
                $message = '<p style="color: red;">Kein Benutzer zum Löschen ausgewählt。</p>';
            } elseif ($userToDelete === $currentUser) {
                $message = '<p style="color: red;">Sie können Ihren eigenen angemeldeten Benutzer nicht löschen。</p>';
            } else {
                $users = getUsers();
                if (isset($users[$userToDelete])) {
                    unset($users[$userToDelete]);
                    if (saveUsers($users)) {
                        $message = '<p style="color: green;">Benutzer "' . htmlspecialchars($userToDelete) . '" erfolgreich gelöscht。</p>';
                    } else {
                        $message = '<p style="color: red;">Fehler beim Löschen des Benutzers。</p>';
                    }
                } else {
                    $message = '<p style="color: red;">Benutzer "' . htmlspecialchars($userToDelete) . '" nicht gefunden。</p>';
                }
            }
            break;
    }
}

// --- HTML-Struktur und Anzeige ---

// Parameter für den Header
$pageTitle = 'Adminbereich'; // Setze den spezifischen Titel für den Adminbereich
$pageHeader = 'Adminbereich';
// Setze die Beschreibung für den Adminbereich.
$siteDescription = 'Administrationsbereich für die TwoKinds Fan-Übersetzung. Hier können administrative Aufgaben durchgeführt werden。';

// Binde den gemeinsamen Header ein。
$robotsContent = 'noindex, nofollow';
include __DIR__ . '/../src/layout/header.php';
?>

<article>
    <!-- CSS für das Admin-Formular, um Theme-Anpassungen zu ermöglichen -->
    <style>
        /* Container für das Formularfeld */
        .admin-form-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            /* Dezenter Rahmen im hellen Modus (beibehalten) */
            border: 1px solid rgba(221, 221, 221, 0.2);
            border-radius: 8px;
            /* Dezenter Hintergrund im hellen Modus (beibehalten) */
            background-color: rgba(240, 240, 240, 0.05);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            /* Dezenterer Schatten (beibehalten) */
        }

        /* Input-Felder und Labels im hellen Theme */
        .admin-form-container label {
            color: #333;
            /* Dunkler Text für Labels */
        }

        .admin-form-container input[type="text"],
        .admin-form-container input[type="password"],
        .admin-form-container input[type="email"] {
            width: calc(100% - 18px);
            /* Breite anpassen */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            /* Weißer Hintergrund für Input-Felder */
            color: #333;
            /* Dunkler Text in Input-Feldern */
        }

        /* Logout Link im hellen Theme */
        /* Dies ist die Standardfarbe für den Logout-Link, wenn kein Dunkelmodus aktiv ist */
        .logout-link {
            color: rgb(240, 17, 9);
            text-decoration: none;
            font-weight: bold;
        }


        /* --- Regeln für den Dunkelmodus (.main-container.lights-off) --- */

        .main-container.lights-off .admin-form-container {
            /* Hintergrund und Rahmen wie in v10 (letzter Stand, den du als besser empfandest) */
            background-color: rgba(30, 30, 30, 0.2);
            /* Dunkler, transparenter Hintergrund */
            border-color: rgba(80, 80, 80, 0.15);
            /* Angepasster Rahmen */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Angepasster Schatten */
        }

        /* Labels sollen im Dunkelmodus #efefef sein */
        body.theme-night .admin-form-container label {
            color: #efefef !important;
            /* Helleres Grau für Labels im Dark Theme - !important hinzugefügt, um body.theme-night a zu überschreiben */
        }

        /* Überschriften, Absätze und Benutzernamen sollen hell sein im Dark Theme */
        .main-container.lights-off .admin-form-container h2,
        .main-container.lights-off .admin-form-container h3,
        .main-container.lights-off .admin-form-container p,
        .main-container.lights-off .admin-form-container li span.user-name {
            color: #f0f0f0;
            /* Heller Text für diese Elemente im Dark Theme */
        }

        /* Input-Felder im dunklen Theme */
        .main-container.lights-off .admin-form-container input[type="text"],
        .main-container.lights-off .admin-form-container input[type="password"],
        .main-container.lights-off .admin-form-container input[type="email"] {
            background-color: #444;
            /* Dunklerer Hintergrund für Input-Felder im Dark Theme */
            color: #f0f0f0;
            /* Heller Text in Input-Feldern im Dark Theme */
            border-color: #666;
            /* Angepasster Rahmen für Input-Felder im Dark Theme */
        }

        /* Buttons (allgemein) */
        .admin-form-container button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Spezifische Button-Farben (heller Modus Standard) */
        .admin-form-container button[name="action"][value="create_initial_user"] {
            background-color: #4CAF50;
            color: white;
        }

        .admin-form-container button[name="action"][value="login"] {
            background-color: #008CBA;
            color: white;
        }

        .admin-form-container button[name="action"][value="change_credentials"] {
            background-color: #5cb85c;
            color: white;
        }

        .admin-form-container button[name="action"][value="add_user"] {
            background-color: #f0ad4e;
            color: white;
        }

        .admin-form-container button[name="action"][value="delete_user"] {
            background-color: #dc3545;
            color: white;
            font-size: 14px;
            padding: 5px 10px;
        }

        .admin-form-container button:hover {
            opacity: 0.9;
        }

        /* Buttons, die im Dark Theme schwarz bleiben sollen (spezifisch für Dark Mode) */
        .main-container.lights-off .admin-form-container button[name="action"][value="change_credentials"],
        .main-container.lights-off .admin-form-container button[name="action"][value="add_user"] {
            color: #333;
            /* Schwarz im Dark Theme */
        }

        .admin-form-container ul {
            list-style-type: none;
            padding-left: 0;
        }

        .admin-form-container li {
            margin-bottom: 8px;
            padding: 5px;
            border-bottom: 1px dotted #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main-container.lights-off .admin-form-container li {
            border-bottom: 1px dotted #555;
            /* Angepasster Trenner im Dark Theme */
        }

        .admin-form-container li span.user-name {
            font-weight: bold;
        }

        /* (Sie) Text soll im hellen Modus schwarz sein und im Dark Theme weiß */
        .admin-form-container li span.current-user-tag {
            font-size: 0.9em;
            /* Standardgröße */
            color: rgb(0, 0, 0);
            /* Schwarz im hellen Modus für deutlichen Kontrast */
        }

        body.theme-night .admin-form-container li span.current-user-tag {
            color: #f0f0f0 !important;
            /* Heller Tag im Dark Theme - !important hinzugefügt für höchste Priorität */
        }

        /* Logout Link im Dark Theme */
        body.theme-night .admin-form-container .logout-link {
            color: rgb(255, 191, 189) !important;
            /* Kräftigeres Rot im Dark Theme wie gewünscht */
            text-decoration: none;
            /* Sicherstellen, dass es nicht unterstrichen ist */
            font-weight: bold;
            /* Sicherstellen, dass es fett ist */
        }

        .main-container.lights-off .logout-link:hover {
            opacity: 0.8;
            /* Hover-Effekt */
        }


        .admin-form-container .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .admin-form-container .message p {
            margin: 0;
        }

        .admin-form-container .message p[style*="color: red"] {
            color: #a94442;
            background-color: #f2dede;
            border: 1px solid #ebccd1;
        }

        .admin-form-container .message p[style*="color: green"] {
            color: #3c763d;
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
        }

        .admin-form-container .message p[style*="color: orange"] {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
        }
    </style>

    <div class="admin-form-container">
        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; // Zeigt Nachrichten an ?>
            </div>
        <?php endif; ?>

        <?php
        $existingUsers = getUsers(); // Lade Benutzer jedes Mal neu, um aktuelle Liste zu haben
        if (empty($existingUsers)):
            ?>
            <h2>Ersten Admin-Benutzer erstellen</h2>
            <p>Es ist noch kein Admin-Benutzer vorhanden. Bitte erstellen Sie einen.</p>
            <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label for="create_username">Benutzername:</label>
                    <input type="text" id="create_username" name="username" required>
                </div>
                <div>
                    <label for="create_password">Passwort:</label>
                    <input type="password" id="create_password" name="password" required>
                </div>
                <button type="submit" name="action" value="create_initial_user">Admin erstellen</button>
            </form>
        <?php elseif (!$loggedIn): ?>
            <h2>Login</h2>
            <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label for="login_username">Benutzername:</label>
                    <input type="text" id="login_username" name="username" required>
                </div>
                <div>
                    <label for="login_password">Passwort:</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                <button type="submit" name="action" value="login">Login</button>
            </form>
        <?php else: ?>
            <h2>Willkommen, <?php echo htmlspecialchars($currentUser); ?>!</h2>
            <p style="text-align: right;"><a href="?action=logout" class="logout-link">Logout</a></p>

            <section style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee;">
                <h3>Benutzerdaten ändern</h3>
                <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label for="old_password">Altes Passwort (Bestätigung):</label>
                        <input type="password" id="old_password" name="old_password" required>
                    </div>
                    <div>
                        <label for="new_username">Neuer Benutzername (optional):</label>
                        <input type="text" id="new_username" name="new_username"
                            value="<?php echo htmlspecialchars($currentUser); ?>">
                    </div>
                    <div>
                        <label for="new_password">Neues Passwort (optional):</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    <button type="submit" name="action" value="change_credentials">Daten ändern</button>
                </form>
            </section>

            <section id="manage-users" style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee;">
                <h3>Neuen Benutzer hinzufügen</h3>
                <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label for="add_username">Benutzername für neuen Benutzer:</label>
                        <input type="text" id="add_username" name="add_username" required>
                    </div>
                    <div>
                        <label for="add_password">Passwort für neuen Benutzer:</label>
                        <input type="password" id="add_password" name="add_password" required>
                    </div>
                    <button type="submit" name="action" value="add_user">Benutzer hinzufügen</button>
                </form>
            </section>

            <section style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee;">
                <h3>Verfügbare Benutzer</h3>
                <ul>
                    <?php
                    $allUsers = getUsers(); // Lade die aktuelle Benutzerliste
                    if (!empty($allUsers)):
                        foreach ($allUsers as $user => $data):
                            ?>
                            <li>
                                <span class="user-name"><?php echo htmlspecialchars($user); ?></span>
                                <?php if ($user !== $currentUser): // Verhindere, dass der aktuelle Benutzer sich selbst löscht ?>
                                    <form action="index.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_to_delete" value="<?php echo htmlspecialchars($user); ?>">
                                        <button type="submit"
                                            onclick="return confirm('Sind Sie sicher, dass Sie den Benutzer <?php echo htmlspecialchars($user); ?> löschen möchten?');">Löschen</button>
                                    </form>
                                <?php else: ?>
                                    <span class="current-user-tag">(Sie)</span>
                                <?php endif; ?>
                            </li>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <li>Keine weiteren Benutzer vorhanden.</li>
                    <?php endif; ?>
                </ul>
            </section>

        <?php endif; ?>
    </div>
</article>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . '/../src/layout/footer.php';
?>