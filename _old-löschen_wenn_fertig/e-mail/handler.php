<?php
if (isset($_POST['action'])) {
  $action = $_POST['action'];

  if ($action === 'hinzufuegen') {
    // Code zum Hinzufügen der E-Mail-Adresse
    if (isset($_POST['email'])) {
      $email = $_POST['email'];
      $ipAdresse = $_SERVER['REMOTE_ADDR']; // IP-Adresse des Clients
      
      // Pfad zur CSV-Datei
      $csvDatei = 'emailliste.csv';
      $backupCsvDatei = 'emailliste-Backup.csv';
      
      // Überprüfen, ob die E-Mail-Adresse bereits vorhanden ist
      $csvInhalt = file_get_contents($csvDatei);
      if (strpos($csvInhalt, $email) !== false) {
        // Die E-Mail-Adresse ist bereits vorhanden
        echo '<h1 style="color: #FF0000;">Sie haben diese E-Mail-Adresse bereits eingetragen.</h1>';
        // JavaScript für die Weiterleitung nach 10 Sekunden
        echo '<script>
          setTimeout(function() {
            window.location.href = "../index.php";
          }, 4000);
        </script>';
        exit;
      }
      
      // E-Mail-Adresse zur CSV-Datei hinzufügen
      $handle = fopen($csvDatei, 'a');
      fputcsv($handle, [$email]);
      fclose($handle);

      // E-Mail-Adresse und IP-Adresse zur Backup-CSV-Datei hinzufügen
      $backupHandle = fopen($backupCsvDatei, 'a');
      fputcsv($backupHandle, [$email . ';' . $ipAdresse]);
      fclose($backupHandle);
      
      echo '<h1 style="color: #1B4815;">E-Mail-Adresse erfolgreich eingetragen</h1><p style="color: #17620C;">Vielen Dank für das Eintragen Ihrer E-Mail-Adresse! Mit dem Hochladen der nächsten übersetzten Seite werden Sie eine Benachrichtigung erhalten.';
      // JavaScript für die Weiterleitung nach 10 Sekunden
      echo '<script>
        setTimeout(function() {
          window.location.href = "../index.php";
        }, 8000);
      </script>';
      exit;
    }
  } elseif ($action === 'loeschen') {
    // Code zum Löschen der E-Mail-Adresse
    if (isset($_POST['email'])) {
      $email = $_POST['email'];
      
      // Pfad zur CSV-Datei
      $csvDatei = 'emailliste.csv';
      
      // E-Mail-Adresse aus CSV-Datei entfernen
      $csvInhalt = file_get_contents($csvDatei);
      $neuerInhalt = str_replace($email, '', $csvInhalt);
      file_put_contents($csvDatei, $neuerInhalt);
      
      echo '<h1 style="color: #1B4815;">E-Mail-Adresse erfolgreich gelöscht</h1><p style="color: #17620C;">Ihre E-Mail-Adresse wurde erfolgreich gelöscht! Sie erhalten in Zukunft keine Benachrichtigung mehr, wenn eine neue Seite hochgeladen wurde.</p>';
      // JavaScript für die Weiterleitung nach 10 Sekunden
      echo '<script>
        setTimeout(function() {
          window.location.href = "../index.php";
        }, 10000);
      </script>';
      exit;
    }
  }
}
?>