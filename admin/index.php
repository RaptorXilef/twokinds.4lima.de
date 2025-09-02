<?php
/**
 * Dies ist die Hauptseite des Admin-Bereichs.
 * Sie verwaltet die Anmeldung, Abmeldung, die Änderung der Benutzerdaten, das Hinzufügen und Löschen neuer Benutzer.
 *
 * SICHERHEITSHINWEIS: Diese Dateispeicherung ist NICHT SICHER für eine Produktionsumgebung!
 * Passwörter werden gehasht, aber die Methode ist anfällig für direkte Dateizugriffe.
 * Für eine echte Anwendung ist eine Datenbanklösung erforderlich.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: index.php wird geladen.");

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();
if ($debugMode)
    error_log("DEBUG: Output Buffer in index.php gestartet.");

// Starte die PHP-Sitzung. Dies ist notwendig, um den Anmeldestatus zu speichern.
session_start();
if ($debugMode)
    error_log("DEBUG: Session in index.php gestartet.");

// NEU: Binde die zentrale Sicherheits- und Sitzungsüberprüfung ein.
require_once __DIR__ . '/src/components/security_check.php';


// --- NEU: Konfiguration für Brute-Force-Schutz ---
define('MAX_LOGIN_ATTEMPTS', 3); // Max. Fehlversuche
define('LOGIN_BLOCK_SECONDS', 900); // 15 Minuten Sperrzeit (900 Sekunden)

// --- Pfade zu den Datendateien ---
$usersFile = __DIR__ . '/../../../admin_users.json';
$loginAttemptsFile = __DIR__ . '/../../../login_attempts.json'; // Datei für Login-Versuche

if ($debugMode) {
    error_log("DEBUG: usersFile Pfad: " . $usersFile);
    error_log("DEBUG: loginAttemptsFile Pfad: " . $loginAttemptsFile);
}

// --- Hilfsfunktionen für die Benutzerverwaltung (Simulierte Datenbankzugriffe) ---
// getUsers() und saveUsers() bleiben unverändert...
/**
 * Liest die Benutzerdaten aus der JSON-Datei.
 * @return array Ein assoziatives Array der Benutzerdaten oder ein leeres Array, wenn die Datei nicht existiert oder leer ist.
 */
function getUsers(): array
{
    global $usersFile, $debugMode; // $debugMode auch in der Funktion verfügbar machen
    if ($debugMode)
        error_log("DEBUG: getUsers() aufgerufen.");

    if (!file_exists($usersFile)) {
        if ($debugMode)
            error_log("DEBUG: Benutzerdatei nicht gefunden: " . $usersFile);
        return [];
    }
    if (filesize($usersFile) === 0) {
        if ($debugMode)
            error_log("DEBUG: Benutzerdatei ist leer: " . $usersFile);
        return [];
    }
    // Stelle sicher, dass die Datei lesbar ist
    if (!is_readable($usersFile)) {
        error_log("Fehler: Benutzerdatei nicht lesbar: " . $usersFile);
        if ($debugMode)
            error_log("DEBUG: Benutzerdatei nicht lesbar (Fehler): " . $usersFile);
        return [];
    }
    $content = file_get_contents($usersFile);
    if ($content === false) {
        error_log("Fehler: Konnte Inhalt der Benutzerdatei nicht lesen: " . $usersFile);
        if ($debugMode)
            error_log("DEBUG: Konnte Inhalt der Benutzerdatei nicht lesen (Fehler): " . $usersFile);
        return [];
    }
    $users = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Fehler beim Dekodieren der Benutzer-JSON: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Dekodieren der Benutzer-JSON: " . json_last_error_msg());
        return [];
    }
    if ($debugMode)
        error_log("DEBUG: " . count($users) . " Benutzer aus Datei geladen.");
    return is_array($users) ? $users : [];
}

/**
 * Speichert die Benutzerdaten in die JSON-Datei.
 * @param array $users Das zu speichernde Benutzerarray.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveUsers(array $users): bool
{
    global $usersFile, $debugMode; // $debugMode auch in der Funktion verfügbar machen
    if ($debugMode)
        error_log("DEBUG: saveUsers() aufgerufen.");

    $dir = dirname($usersFile);
    if (!is_dir($dir)) {
        if ($debugMode)
            error_log("DEBUG: Verzeichnis für Benutzerdatei existiert nicht, versuche zu erstellen: " . $dir);
        if (!mkdir($dir, 0755, true)) { // Rekursives Erstellen mit 0755 Rechten
            error_log("Fehler: Konnte Verzeichnis für Benutzerdatei nicht erstellen: " . $dir);
            return false;
        }
        if ($debugMode)
            error_log("DEBUG: Verzeichnis erfolgreich erstellt: " . $dir);
    }

    if (!is_writable($dir)) {
        error_log("Fehler: Verzeichnis für Benutzerdatei nicht schreibbar: " . $dir);
        if ($debugMode)
            error_log("DEBUG: Verzeichnis für Benutzerdatei nicht schreibbar (Fehler): " . $dir);
        return false;
    }
    // JSON_PRETTY_PRINT für bessere Lesbarkeit der Datei.
    $jsonContent = json_encode($users, JSON_PRETTY_PRINT);
    if ($jsonContent === false) {
        error_log("Fehler beim Kodieren der Benutzerdaten in JSON: " . json_last_error_msg());
        if ($debugMode)
            error_log("DEBUG: Fehler beim Kodieren der Benutzerdaten in JSON: " . json_last_error_msg());
        return false;
    }
    $result = file_put_contents($usersFile, $jsonContent);
    if ($result === false) {
        error_log("Fehler beim Schreiben der Benutzerdatei: " . $usersFile);
        if ($debugMode)
            error_log("DEBUG: Fehler beim Schreiben der Benutzerdatei (Fehler): " . $usersFile);
    } else {
        if ($debugMode)
            error_log("DEBUG: Benutzerdaten erfolgreich in Datei gespeichert: " . $usersFile);
    }
    return $result !== false;
}

// --- NEU: Hilfsfunktionen für Login-Versuche ---
/**
 * Liest die Login-Versuche aus der JSON-Datei.
 * @return array Die Daten der Login-Versuche.
 */
function getLoginAttempts(): array
{
    global $loginAttemptsFile, $debugMode;
    if (!file_exists($loginAttemptsFile)) {
        return [];
    }
    $content = file_get_contents($loginAttemptsFile);
    $attempts = json_decode($content, true);
    return is_array($attempts) ? $attempts : [];
}

/**
 * Speichert die Login-Versuche in die JSON-Datei.
 * @param array $attempts Die zu speichernden Daten.
 * @return bool True bei Erfolg, False bei Fehler.
 */
function saveLoginAttempts(array $attempts): bool
{
    global $loginAttemptsFile, $debugMode;
    $dir = dirname($loginAttemptsFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $jsonContent = json_encode($attempts, JSON_PRETTY_PRINT);
    return file_put_contents($loginAttemptsFile, $jsonContent) !== false;
}


// --- Authentifizierungs- und Verwaltungslogik ---

$message = ''; // Wird für Erfolgs- oder Fehlermeldungen verwendet.

// NEU: Nachricht für abgelaufene Session anzeigen
if (isset($_GET['reason']) && $_GET['reason'] === 'session_expired') {
    $message = '<p style="color: orange;">Ihre Sitzung ist aufgrund von Inaktivität abgelaufen. Bitte melden Sie sich erneut an.</p>';
}


// Überprüfen, ob ein Benutzer angemeldet ist.
$loggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$currentUser = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : '';

if ($debugMode) {
    error_log("DEBUG: Aktueller Anmeldestatus: " . ($loggedIn ? 'Angemeldet' : 'Nicht angemeldet'));
    if ($loggedIn)
        error_log("DEBUG: Angemeldeter Benutzer: " . $currentUser);
}

// Logout-Funktion (wird über GET-Parameter ausgelöst)
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if ($debugMode)
        error_log("DEBUG: Logout-Aktion erkannt.");
    session_unset();     // Entfernt alle Session-Variablen
    session_destroy();   // Zerstört die Session
    $loggedIn = false;
    $currentUser = '';
    $message = '<p style="color: green;">Erfolgreich abgemeldet.</p>';
    if ($debugMode)
        error_log("DEBUG: Session zerstört, Weiterleitung zur Login-Seite.");
    ob_end_clean(); // Output Buffer leeren, bevor die Weiterleitung gesendet wird.
    header('Location: index.php'); // Weiterleitung zur Login-Seite
    exit;
}

// Bearbeitung von POST-Anfragen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($debugMode)
        error_log("DEBUG: POST-Anfrage erkannt, Aktion: " . $action);

    switch ($action) {
        // Initialen Admin-Benutzer erstellen (nur wenn noch keine Benutzer existieren)
        case 'create_initial_user':
            if ($debugMode)
                error_log("DEBUG: Aktion: create_initial_user.");
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? ''; // Passwörter werden gehasht, daher nicht filtern.

            $users = getUsers();
            if (empty($users)) { // Nur erstellen, wenn keine Benutzer vorhanden sind
                if (empty($username) || empty($password)) {
                    $message = '<p style="color: red;">Benutzername und Passwort dürfen nicht leer sein.</p>';
                    if ($debugMode)
                        error_log("DEBUG: Fehler: Benutzername oder Passwort leer für initialen Benutzer.");
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$username] = ['passwordHash' => $hashedPassword];
                    if (saveUsers($users)) {
                        $message = '<p style="color: green;">Erster Admin-Benutzer erfolgreich erstellt. Bitte melden Sie sich an.</p>';
                        if ($debugMode)
                            error_log("DEBUG: Initialer Benutzer '" . $username . "' erfolgreich erstellt und gespeichert.");
                        // Weiterleitung zur Login-Seite
                        ob_end_clean();
                        header('Location: index.php');
                        exit;
                    } else {
                        $message = '<p style="color: red;">Fehler beim Speichern des Benutzers。</p>';
                        if ($debugMode)
                            error_log("DEBUG: Fehler beim Speichern des initialen Benutzers.");
                    }
                }
            } else {
                $message = '<p style="color: red;">Ein Admin-Benutzer existiert bereits. Initialisierung nicht möglich。</p>';
                if ($debugMode)
                    error_log("DEBUG: Initialer Benutzer konnte nicht erstellt werden, da bereits Benutzer existieren.");
            }
            break;

        // Anmeldefunktion (mit Brute-Force-Schutz)
        case 'login':
            if ($debugMode)
                error_log("DEBUG: Aktion: login.");

            // --- NEU: Brute-Force-Prüfung ---
            $userIp = $_SERVER['REMOTE_ADDR'];
            $attempts = getLoginAttempts();

            if (isset($attempts[$userIp]) && $attempts[$userIp]['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $timeSinceLastAttempt = time() - $attempts[$userIp]['last_attempt'];
                if ($timeSinceLastAttempt < LOGIN_BLOCK_SECONDS) {
                    $remainingTime = LOGIN_BLOCK_SECONDS - $timeSinceLastAttempt;
                    $message = '<p style="color: red;">Zu viele fehlgeschlagene Login-Versuche. Bitte warten Sie noch ' . ceil($remainingTime / 60) . ' Minute(n).</p>';
                    if ($debugMode)
                        error_log("DEBUG: Login für IP $userIp blockiert. Verbleibende Zeit: $remainingTime Sekunden.");
                    break; // Wichtig: Login-Prozess hier abbrechen
                } else {
                    // Sperrzeit ist abgelaufen, Zähler zurücksetzen
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                    if ($debugMode)
                        error_log("DEBUG: Sperrzeit für IP $userIp abgelaufen, Zähler zurückgesetzt.");
                }
            }

            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'] ?? '';

            $users = getUsers();
            if (isset($users[$username]) && password_verify($password, $users[$username]['passwordHash'])) {
                // --- ERFOLGREICHER LOGIN ---
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['last_activity'] = time(); // NEU: Aktivitätszeitstempel initial setzen
                $loggedIn = true;
                $currentUser = $username;

                // Fehlversuche für diese IP zurücksetzen
                if (isset($attempts[$userIp])) {
                    unset($attempts[$userIp]);
                    saveLoginAttempts($attempts);
                }

                if ($debugMode)
                    error_log("DEBUG: Benutzer '" . $username . "' erfolgreich angemeldet. Fehlversuche für IP $userIp zurückgesetzt.");
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                // --- FEHLGESCHLAGENER LOGIN ---
                if (!isset($attempts[$userIp])) {
                    $attempts[$userIp] = ['attempts' => 0, 'last_attempt' => 0];
                }
                $attempts[$userIp]['attempts']++;
                $attempts[$userIp]['last_attempt'] = time();
                saveLoginAttempts($attempts);

                $remainingAttempts = MAX_LOGIN_ATTEMPTS - $attempts[$userIp]['attempts'];
                $message = '<p style="color: red;">Ungültiger Benutzername oder Passwort. Verbleibende Versuche: ' . $remainingAttempts . '</p>';
                if ($debugMode)
                    error_log("DEBUG: Fehlgeschlagener Login-Versuch für IP $userIp. Versuch Nr. " . $attempts[$userIp]['attempts']);
            }
            break;

        // Benutzername/Passwort ändern (nur für angemeldete Benutzer)
        case 'change_credentials':
            if ($debugMode)
                error_log("DEBUG: Aktion: change_credentials.");
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Ihre Daten zu ändern。</p>';
                if ($debugMode)
                    error_log("DEBUG: Versuchter Datenänderung ohne Anmeldung.");
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
                if ($debugMode)
                    error_log("DEBUG: Ungültiges altes Passwort für Benutzer '" . $currentUsername . "'.");
            } elseif (empty($newUsername) && empty($newPassword)) {
                $message = '<p style="color: orange;">Bitte geben Sie einen neuen Benutzernamen oder ein neues Passwort ein。</p>';
                if ($debugMode)
                    error_log("DEBUG: Keine neuen Anmeldedaten zum Ändern angegeben.");
            } else {
                $userUpdated = false;
                $newUsersArray = $users; // Arbeitskopie der Benutzer

                // Wenn Benutzername geändert wird
                if (!empty($newUsername) && $newUsername !== $currentUsername) {
                    if (isset($newUsersArray[$newUsername])) {
                        $message = '<p style="color: red;">Neuer Benutzername ist bereits vergeben。</p>';
                        if ($debugMode)
                            error_log("DEBUG: Neuer Benutzername '" . $newUsername . "' bereits vergeben.");
                        break; // Abbruch, wenn Benutzername schon existiert
                    }
                    // Verschiebe den alten Eintrag zum neuen Benutzernamen
                    $newUsersArray[$newUsername] = $newUsersArray[$currentUsername];
                    unset($newUsersArray[$currentUsername]);
                    $_SESSION['admin_username'] = $newUsername; // Session aktualisieren
                    $currentUser = $newUsername;
                    $userUpdated = true;
                    if ($debugMode)
                        error_log("DEBUG: Benutzername von '" . $currentUsername . "' zu '" . $newUsername . "' geändert.");
                }

                // Wenn Passwort geändert wird
                if (!empty($newPassword)) {
                    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $newUsersArray[$currentUser]['passwordHash'] = $hashedNewPassword;
                    $userUpdated = true;
                    if ($debugMode)
                        error_log("DEBUG: Passwort für Benutzer '" . $currentUser . "' geändert.");
                }

                if ($userUpdated) {
                    if (saveUsers($newUsersArray)) {
                        $message = '<p style="color: green;">Anmeldedaten erfolgreich aktualisiert。</p>';
                        if ($debugMode)
                            error_log("DEBUG: Anmeldedaten erfolgreich gespeichert.");
                    } else {
                        $message = '<p style="color: red;">Fehler beim Speichern der neuen Anmeldedaten。</p>';
                        if ($debugMode)
                            error_log("DEBUG: Fehler beim Speichern der neuen Anmeldedaten.");
                    }
                } else {
                    $message = '<p style="color: orange;">Keine Änderungen vorgenommen (Benutzername und/oder Passwort nicht unterschiedlich)。</p>';
                    if ($debugMode)
                        error_log("DEBUG: Keine Änderungen an Anmeldedaten vorgenommen.");
                }
            }
            break;

        // Neuen Benutzer hinzufügen (nur für angemeldete Benutzer)
        case 'add_user':
            if ($debugMode)
                error_log("DEBUG: Aktion: add_user.");
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Benutzer hinzuzufügen。</p>';
                if ($debugMode)
                    error_log("DEBUG: Versuchter Benutzerhinzufügung ohne Anmeldung.");
                break;
            }
            $newUsername = filter_input(INPUT_POST, 'add_username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $newPassword = $_POST['add_password'] ?? '';

            $users = getUsers();
            if (empty($newUsername) || empty($newPassword)) {
                $message = '<p style="color: red;">Benutzername und Passwort für den neuen Benutzer dürfen nicht leer sein。</p>';
                if ($debugMode)
                    error_log("DEBUG: Fehler: Benutzername oder Passwort leer für neuen Benutzer.");
            } elseif (isset($users[$newUsername])) {
                $message = '<p style="color: red;">Benutzername "' . htmlspecialchars($newUsername) . '" existiert bereits。</p>';
                if ($debugMode)
                    error_log("DEBUG: Fehler: Benutzername '" . $newUsername . "' existiert bereits.");
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $users[$newUsername] = ['passwordHash' => $hashedPassword];
                if (saveUsers($users)) {
                    $message = '<p style="color: green;">Benutzer "' . htmlspecialchars($newUsername) . '" erfolgreich hinzugefügt。</p>';
                    if ($debugMode)
                        error_log("DEBUG: Benutzer '" . $newUsername . "' erfolgreich hinzugefügt.");
                } else {
                    $message = '<p style="color: red;">Fehler beim Hinzufügen des Benutzers。</p>';
                    if ($debugMode)
                        error_log("DEBUG: Fehler beim Hinzufügen des Benutzers '" . $newUsername . "'.");
                }
            }
            break;

        // Benutzer löschen (nur für angemeldete Benutzer)
        case 'delete_user':
            if ($debugMode)
                error_log("DEBUG: Aktion: delete_user.");
            if (!$loggedIn) {
                $message = '<p style="color: red;">Sie müssen angemeldet sein, um Benutzer zu löschen。</p>';
                if ($debugMode)
                    error_log("DEBUG: Versuchtes Benutzerlöschen ohne Anmeldung.");
                break;
            }
            $userToDelete = filter_input(INPUT_POST, 'user_to_delete', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (empty($userToDelete)) {
                $message = '<p style="color: red;">Kein Benutzer zum Löschen ausgewählt。</p>';
                if ($debugMode)
                    error_log("DEBUG: Fehler: Kein Benutzer zum Löschen ausgewählt.");
            } elseif ($userToDelete === $currentUser) {
                $message = '<p style="color: red;">Sie können Ihren eigenen angemeldeten Benutzer nicht löschen。</p>';
                if ($debugMode)
                    error_log("DEBUG: Fehler: Versuch, angemeldeten Benutzer '" . $currentUser . "' zu löschen.");
            } else {
                $users = getUsers();
                if (isset($users[$userToDelete])) {
                    unset($users[$userToDelete]);
                    if (saveUsers($users)) {
                        $message = '<p style="color: green;">Benutzer "' . htmlspecialchars($userToDelete) . '" erfolgreich gelöscht。</p>';
                        if ($debugMode)
                            error_log("DEBUG: Benutzer '" . $userToDelete . "' erfolgreich gelöscht.");
                    } else {
                        $message = '<p style="color: red;">Fehler beim Löschen des Benutzers。</p>';
                        if ($debugMode)
                            error_log("DEBUG: Fehler beim Löschen des Benutzers '" . $userToDelete . "'.");
                    }
                } else {
                    $message = '<p style="color: red;">Benutzer "' . htmlspecialchars($userToDelete) . '" nicht gefunden。</p>';
                    if ($debugMode)
                        error_log("DEBUG: Fehler: Benutzer '" . $userToDelete . "' zum Löschen nicht gefunden.");
                }
            }
            break;
    }
}

// --- HTML-Struktur und Anzeige ---
// Parameter für den Header
$pageTitle = 'Adminbereich - Login und Benutzerverwaltung'; // Setze den spezifischen Titel für den Adminbereich
$pageHeader = 'Adminbereich - Login und Benutzerverwaltung';
// Setze die Beschreibung für den Adminbereich.
$siteDescription = 'Administrationsbereich für die TwoKinds Fan-Übersetzung. Hier können administrative Aufgaben durchgeführt werden。';

// Binde den gemeinsamen Header ein。
$robotsContent = 'noindex, nofollow';
$headerPath = __DIR__ . '/../src/layout/header.php';
if (file_exists($headerPath)) {
    include $headerPath;
    if ($debugMode)
        error_log("DEBUG: Header in index.php eingebunden.");
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<article>
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
                <?php if ($debugMode)
                    error_log("DEBUG: Nachricht angezeigt: " . strip_tags($message)); ?>
            </div>
        <?php endif; ?>

        <?php
        $existingUsers = getUsers(); // Lade Benutzer jedes Mal neu, um aktuelle Liste zu haben
        if ($debugMode)
            error_log("DEBUG: Anzahl vorhandener Benutzer: " . count($existingUsers));

        if (empty($existingUsers)):
            if ($debugMode)
                error_log("DEBUG: Keine Benutzer gefunden, Formular für initialen Benutzer anzeigen.");
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
        <?php elseif (!$loggedIn):
            if ($debugMode)
                error_log("DEBUG: Benutzer existieren, aber nicht angemeldet. Login-Formular anzeigen.");
            ?>
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
        <?php else:
            if ($debugMode)
                error_log("DEBUG: Benutzer '" . $currentUser . "' ist angemeldet. Verwaltungsformulare anzeigen.");
            ?>
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
                    if ($debugMode)
                        error_log("DEBUG: Zeige Liste von " . count($allUsers) . " Benutzern an.");
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
                        if ($debugMode)
                            error_log("DEBUG: Keine weiteren Benutzer vorhanden (außer eventuell dem angemeldeten).");
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
$footerPath = __DIR__ . '/../src/layout/footer.php';
if (file_exists($footerPath)) {
    include $footerPath;
    if ($debugMode)
        error_log("DEBUG: Footer in index.php eingebunden.");
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
    if ($debugMode)
        error_log("DEBUG: Footer-Datei nicht gefunden, HTML manuell geschlossen.");
}

ob_end_flush(); // Gebe den Output Buffer am Ende des Skripts aus.
if ($debugMode)
    error_log("DEBUG: Output Buffer in index.php geleert und ausgegeben.");
?>