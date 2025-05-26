<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}
// Hier kommt der Inhalt
?>

<!DOCTYPE html>
<html>
<head>
  <script>
    function kopiereInZwischenablage() {
      var empfaenger = document.getElementById("ausgabe").innerHTML;
      
      // Erstelle ein unsichtbares Textfeld
      var textfeld = document.createElement("textarea");
      textfeld.value = empfaenger;
      document.body.appendChild(textfeld);
      
      // Kopiere den Inhalt des Textfelds in die Zwischenablage
      textfeld.select();
      document.execCommand("copy");
      
      // Entferne das Textfeld
      document.body.removeChild(textfeld);
      
      // Bestätigung anzeigen
      alert("Der Inhalt wurde in die Zwischenablage kopiert!");
    }
  </script>
</head>
<?php require_once('includes/design/header.php'); ?>
<body>
  <?php
  // Pfad zur CSV-Datei
  $csvDatei = '../e-mail/emailliste.csv';

  // Array zum Speichern der E-Mail-Adressen
  $emails = [];

  // CSV-Datei öffnen und E-Mail-Adressen auslesen
  if (($handle = fopen($csvDatei, 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
      // Annahme: Die E-Mail-Adresse befindet sich im ersten Spaltenwert der CSV-Datei
      $email = $data[0];

      // E-Mail-Adresse zum Array hinzufügen
      $emails[] = $email;
    }

    fclose($handle);
  }

  // E-Mail-Adressen in den "Senden an"-Bereich kopieren
  $empfaenger = implode(', ', $emails);
  ?>
  
  <button onclick="kopiereInZwischenablage()">In Zwischenablage kopieren</button>
  
  <div id="ausgabe"><?php echo $empfaenger; ?></div>
</body>
</html>
<?php
require_once('includes/design/footer.php');
?>