<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt
?>

<form method="post" action="">
    <input type="text" name="username" placeholder="Benutzername" required> _____________<br>
    <input type="password" name="password" id="password" placeholder="Passwort" required>
    <input type="checkbox" onclick="toggleFieldVisibility('password')"> <label>Anzeigen</label><br>
    <input type="password" name="salt" id="salt" placeholder="Salt-Wert" required>
    <input type="checkbox" onclick="toggleFieldVisibility('salt')"> <label>Anzeigen</label><br>
    <input type="submit" name="submit" value="Generieren">
</form>

<script>
    function toggleFieldVisibility(fieldName) {
        var field = document.getElementById(fieldName);
        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }
</script>
</br></br></br></br>
<?php
if (isset($_POST['submit'])) {
    $password = $_POST['password'];
    $username = $_POST['username'];
    $salt = $_POST['salt'];

    // Leerzeichen aus dem Benutzernamen entfernen
    // $username = str_replace(' ', '', $username);

    $hashedPassword = password_hash($password . $username . $salt, PASSWORD_DEFAULT);


    echo '<p style="text-align: left; font-size: 14px;">';
    echo '<strong>Benutzername:</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $username . '<br>';
    echo '<strong>Gehashtes und gesalzenes Passwort:</strong> ' . $hashedPassword . '<br>';
    echo '<strong>Salt-Wert:</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $salt;
    echo '</p>';
}




require_once('includes/design/footer.php');

?>