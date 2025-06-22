<?php
// src/templates/admin/archive_generator/output.php
// Zeigt den Output des Archiv-Generators und Navigationslinks

// Variablen werden von loadTemplate übergeben: $archiveNumber, $archiveNumberSubOne, $archiveNumberAddOne
?>
</br></br>>> <a href="archive_generator.php?archiveNumber=<?php echo htmlspecialchars($archiveNumberSubOne); ?>" alt="restart">Weiter mit Nr: <?php echo htmlspecialchars($archiveNumberSubOne); ?>?</a> << ---- >> <a href="archive_generator.php?archiveNumber=<?php echo htmlspecialchars($archiveNumberAddOne); ?>" alt="restart">Weiter mit Nr: <?php echo htmlspecialchars($archiveNumberAddOne); ?>?</a><<
</br></br><a href="archive_generator_start.php" alt="restart">Restart?</a>