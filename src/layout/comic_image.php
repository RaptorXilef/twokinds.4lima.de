<?php
/**
 * Zeigt das Comic-Bild und dessen Metadaten an.
 * Benötigt $comicName und $currentComicId.
 *
 * @param string $currentComicId Die ID (Datum) des aktuell angezeigten Comics.
 * @param string $comicName Der Name des aktuell angezeigten Comics.
 */

// Überprüfe, ob die benötigten Variablen gesetzt sind
if (!isset($currentComicId) || !isset($comicName)) {
    echo '<p style="color: red;">Fehler: Comic-Informationen nicht verfügbar.</p>';
} else {
    $imageHiresPath = "./comic_hires/{$currentComicId}.jpg";
    $imagePath = "./comic/{$currentComicId}.png";
?>
<a href="<?php echo $imageHiresPath; ?>" target="_blank">
    <img src="<?php echo $imagePath; ?>"
        title="<?php echo htmlspecialchars($comicName); ?>"
        alt="Comic Page: <?php echo htmlspecialchars($comicName); ?>"
        width="825">
</a>
<?php
}
?>