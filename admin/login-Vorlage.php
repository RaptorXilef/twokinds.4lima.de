<?php
session_start();

// Überprüfung der Anmeldeinformationen
function checkCredentials($username, $password) {
    // Definieren Sie hier Ihre vordefinierten Benutzerdaten
    $validUsernames = array('F M');
    $validPasswords = array('$2y$10$g3iClo0od4PD71bhNJFasuDPdVp8lrR7RhI3k6mYXIofA.inQuSiy'); // Beispiel-Hash
    $validSalts = array('salt'); // Beispiel-Salt

    // Überprüfen, ob der Benutzername existiert und das Passwort übereinstimmt
    $index = array_search($username, $validUsernames);
    if ($index !== false) {
        $hashedPassword = $validPasswords[$index];
        $salt = $validSalts[$index]; // Salt-Wert
        if (password_verify($password . $username . $salt, $hashedPassword)) {
            return true;
        }
    }

    return false;
}

// Überprüfen, ob das Anmeldeformular gesendet wurde
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Überprüfen der Anmeldeinformationen
    if (checkCredentials($username, $password)) {
        // Authentifizierung erfolgreich, Sitzung starten und zur index.php weiterleiten
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit();
    } else {
        // Authentifizierung fehlgeschlagen, setzen Sie die Fehlermeldung
        $errorMessage = "Ungültige Anmeldeinformationen. Bitte versuchen Sie es erneut.";
    }
}

// Anmeldeformular anzeigen

if (isset($errorMessage)) {
    echo "<h1 style='color: red;'>$errorMessage</h1>";
    echo "<br>";
}
?>
<form method="post" action="login.php">
    <input type="text" name="username" placeholder="Benutzername" required><br>
    <input type="password" name="password" placeholder="Passwort" required><br>
    <input type="checkbox" onclick="showPassword()"> Passwort anzeigen<br>
    <input type="submit" name="submit" value="Anmelden">
</form>

<script>
    function showPassword() {
        var passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
        } else {
            passwordInput.type = "password";
        }
    }
</script>
