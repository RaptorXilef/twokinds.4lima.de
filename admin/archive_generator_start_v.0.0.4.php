<?php
session_start(); // Session starten
ob_start(); // Aktiviere den Output Buffering-Modus


// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}


require_once('includes/design/header.php');


// Hier kommt der Inhalt




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Überprüfen, ob das Formular abgeschickt wurde

    // Prüfen, ob die Nummer eingegeben wurde
    if (isset($_POST['archiveNumber']) && !empty($_POST['archiveNumber'])) {
        $archiveNumberIdInput = $_POST['archiveNumber']; // Nummer speichern

        // Dateien laden und Funktionen ausf�hren, Dabei Variable �bergeben
        $redirectUrl = 'archive_generator.php?archiveNumberIdInput=' . urlencode($archiveNumberIdInput);
            header('Location: ' . $redirectUrl);
            ob_end_flush(); // Sendet den gespeicherten Output an den Browser und beendet den Output Buffering-Modus
            exit;
        // Hier kannst du den Code einf�gen, der nach dem Laden der Dateien und Ausf�hren der Funktionen ausgef�hrt werden soll

        // Die Nummer in der Session speichern
        $_SESSION['archiveNumberIdInput'] = $archiveNumberIdInput;

        // Redirect zur gleichen Seite, um den Reload zu vermeiden
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $errorText = 'Sie haben keine Nummer angegeben. Bitte versuchen Sie es erneut!';
    }
} elseif (isset($_SESSION['archiveNumberIdInput'])) {
    // �berpr�fen, ob die Nummer in der Session gespeichert wurde
    $archiveNumberIdInput = $_SESSION['archiveNumberIdInput'];

    // Die Nummer aus der Session l�schen
    unset($_SESSION['archiveNumberIdInput']);
}
?>


    <title>Archivgenerator</title>
    <style>
        .error {
            color: red;
        }
    </style>

<body>
    <?php if (isset($errorText)): ?>
        <p class="error"><?php echo $errorText; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Geben Sie die Nummer der Überschrift an, welche neu generiert werden soll:</label></br></br>
        <input type="text" name="archiveNumber" value="<?php echo isset($archiveNumberIdInput) ? $archiveNumberIdInput : ''; ?>">
        <button type="submit" name="submit">Archivgenerator starten</button>
    </form>



</br></br>
<h2>Existierende Generatoren:</h2>
<?php
$ausgabeOrdnerinhaltArchivOrdner = 'includes/homepage-generators/archive';
echo '</br><p>Ordnerpfad: admin/' . $ausgabeOrdnerinhaltArchivOrdner . ' </p></br>';

// Scandir-Funktion zum Auslesen des Ordners
$ausgabeOrdnerinhaltArchivVerzeichnis = scandir($ausgabeOrdnerinhaltArchivOrdner);

// Iteriere über die Dateien im Ordner
foreach ($ausgabeOrdnerinhaltArchivVerzeichnis as $ausgabeOrdnerinhaltArchivDatei) {
  // Ignoriere "." und ".." (Standardordner in einem Verzeichnis)
  if ($ausgabeOrdnerinhaltArchivDatei != "." && $ausgabeOrdnerinhaltArchivDatei != "..") {
    echo $ausgabeOrdnerinhaltArchivDatei . "<br>"; // Gib den Dateinamen aus
  }
}



require('includes/design/footer.php');
?>