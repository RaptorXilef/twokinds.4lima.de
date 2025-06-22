<?php
// src/templates/admin/archive_generator/form.php
// Formular für die Archivgenerator-Startseite

// $errorText und $archiveNumberIdInput werden von loadTemplate übergeben
?>
<?php if (!empty($errorText)): ?>
    <p class="error"><?php echo htmlspecialchars($errorText); ?></p>
<?php endif; ?>

<form method="POST">
    <label>Geben Sie die Nummer der Überschrift an, welche neu generiert werden soll:</label></br></br>
    <input type="text" name="archiveNumber" value="<?php echo htmlspecialchars($archiveNumberIdInput); ?>">
    <button type="submit" name="submit">Archivgenerator starten</button>
</form>