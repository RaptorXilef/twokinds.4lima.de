<?php
/**
 * Dies ist die Hauptseite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung, Abmeldung, die Änderung der Benutzerdaten und das Hinzufügen neuer Benutzer.
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
function getUsers(): array {
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
function saveUsers(array $users): bool {
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
                        $message = '<p style="color: red;">Fehler beim Speichern des Benutzers.</p>';
                    }
                }
            } else {
                $message = '<p style="color: red;">Ein Admin-Benutzer existiert bereits. Initialisierung nicht möglich.</p>';
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
                $message = '<p style="color: green;">Erfolgreich angemeldet!</p>';
                // Weiterleitung, um Formularerneutsendung zu vermeiden
                header('Location: index.php');
                exit;
            } else {
                $message = '<p style="color: red;">Ungültiger Benutzername oder Passwort.</p>';
            }
            break;

        // Benutzername/Passwort ändern (nur für angemeldete Benutzer)
        case 'change_credentials':
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Ihre Daten zu ändern.</p>';
                break;
            }
            $currentUsername = $_SESSION['admin_username'];
            $oldPassword = $_POST['old_password'] ?? '';
            $newUsername = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['new_password'] ?? '';

            $users = getUsers();

            // Überprüfe altes Passwort
            if (!isset($users[$currentUsername]) || !password_verify($oldPassword, $users[$currentUsername]['passwordHash'])) {
                $message = '<p style="color: red;">Altes Passwort ist inkorrekt.</p>';
            } elseif (empty($newUsername) && empty($newPassword)) {
                $message = '<p style="color: orange;">Bitte geben Sie einen neuen Benutzernamen oder ein neues Passwort ein.</p>';
            } else {
                $userUpdated = false;
                $newUsersArray = $users; // Arbeitskopie der Benutzer

                // Wenn Benutzername geändert wird
                if (!empty($newUsername) && $newUsername !== $currentUsername) {
                    if (isset($newUsersArray[$newUsername])) {
                        $message = '<p style="color: red;">Neuer Benutzername ist bereits vergeben.</p>';
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
                        $message = '<p style="color: green;">Anmeldedaten erfolgreich aktualisiert.</p>';
                    } else {
                        $message = '<p style="color: red;">Fehler beim Speichern der neuen Anmeldedaten.</p>';
                    }
                } else {
                    $message = '<p style="color: orange;">Keine Änderungen vorgenommen (Benutzername und/oder Passwort nicht unterschiedlich).</p>';
                }
            }
            break;
        
        // Neuen Benutzer hinzufügen (nur für angemeldete Benutzer)
        case 'add_user':
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Benutzer hinzuzufügen.</p>';
                break;
            }
            $newUsername = filter_input(INPUT_POST, 'add_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['add_password'] ?? '';

            $users = getUsers();
            if (empty($newUsername) || empty($newPassword)) {
                $message = '<p style="color: red;">Benutzername und Passwort für den neuen Benutzer dürfen nicht leer sein.</p>';
            } elseif (isset($users[$newUsername])) {
                $message = '<p style="color: red;">Benutzername "' . htmlspecialchars($newUsername) . '" existiert bereits.</p>';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $users[$newUsername] = ['passwordHash' => $hashedPassword];
                if (saveUsers($users)) {
                    $message = '<p style="color: green;">Benutzer "' . htmlspecialchars($newUsername) . '" erfolgreich hinzugefügt.</p>';
                } else {
                    $message = '<p style="color: red;">Fehler beim Hinzufügen des Benutzers.</p>';
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
$siteDescription = 'Administrationsbereich für die TwoKinds Fan-Übersetzung. Hier können administrative Aufgaben durchgeführt werden.';

// Binde den gemeinsamen Header ein.
include __DIR__ . '/../src/layout/header.php';
?>

<article>
    <header>
        <h1 class="page-header"><?php echo htmlspecialchars($pageHeader); ?></h1>
    </header>

    <div style="max-width: 500px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <?php echo $message; // Zeigt Nachrichten an ?>

        <?php
        $existingUsers = getUsers(); // Lade Benutzer jedes Mal neu, um aktuelle Liste zu haben
        if (empty($existingUsers)):
        ?>
            <h2>Ersten Admin-Benutzer erstellen</h2>
            <p>Es ist noch kein Admin-Benutzer vorhanden. Bitte erstellen Sie einen.</p>
            <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label for="create_username" style="display: block; margin-bottom: 5px; font-weight: bold;">Benutzername:</label>
                    <input type="text" id="create_username" name="username" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label for="create_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Passwort:</label>
                    <input type="password" id="create_password" name="password" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <button type="submit" name="action" value="create_initial_user" style="padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease;">Admin erstellen</button>
            </form>
        <?php elseif (!$loggedIn): ?>
            <h2>Login</h2>
            <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label for="login_username" style="display: block; margin-bottom: 5px; font-weight: bold;">Benutzername:</label>
                    <input type="text" id="login_username" name="username" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <label for="login_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Passwort:</label>
                    <input type="password" id="login_password" name="password" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <button type="submit" name="action" value="login" style="padding: 10px 15px; background-color: #008CBA; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease;">Login</button>
            </form>
        <?php else: ?>
            <h2>Willkommen, <?php echo htmlspecialchars($currentUser); ?>!</h2>
            <p style="text-align: right;"><a href="?action=logout" style="color: #d9534f; text-decoration: none; font-weight: bold;">Logout</a></p>

            <section style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee;">
                <h3>Benutzerdaten ändern</h3>
                <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label for="old_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Altes Passwort (Bestätigung):</label>
                        <input type="password" id="old_password" name="old_password" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="new_username" style="display: block; margin-bottom: 5px; font-weight: bold;">Neuer Benutzername (optional):</label>
                        <input type="text" id="new_username" name="new_username" value="<?php echo htmlspecialchars($currentUser); ?>" style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="new_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Neues Passwort (optional):</label>
                        <input type="password" id="new_password" name="new_password" style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <button type="submit" name="action" value="change_credentials" style="padding: 10px 15px; background-color: #5cb85c; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease;">Daten ändern</button>
                </form>
            </section>

            <section style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee;">
                <h3>Neuen Benutzer hinzufügen</h3>
                <form action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label for="add_username" style="display: block; margin-bottom: 5px; font-weight: bold;">Benutzername für neuen Benutzer:</label>
                        <input type="text" id="add_username" name="add_username" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div>
                        <label for="add_password" style="display: block; margin-bottom: 5px; font-weight: bold;">Passwort für neuen Benutzer:</label>
                        <input type="password" id="add_password" name="add_password" required style="width: calc(100% - 18px); padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <button type="submit" name="action" value="add_user" style="padding: 10px 15px; background-color: #f0ad4e; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background-color 0.3s ease;">Benutzer hinzufügen</button>
                </form>
            </section>

            <section style="margin-top: 30px; padding-top: 20px; border-top: 1px dashed #eee;">
                <h3>Verfügbare Benutzer</h3>
                <ul style="list-style-type: disc; padding-left: 20px;">
                    <?php
                    $allUsers = getUsers(); // Lade die aktuelle Benutzerliste
                    if (!empty($allUsers)):
                        foreach ($allUsers as $user => $data):
                    ?>
                        <li style="margin-bottom: 5px;"><?php echo htmlspecialchars($user); ?></li>
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
