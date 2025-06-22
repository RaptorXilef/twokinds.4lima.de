<?php
/**
 * Archiv-Generator Skript
 *
 * Dieses Skript generiert eine Archivdatei für eine Sammlung von Comics oder Bildern.
 * Es erzeugt eine PHP-Datei, die die Struktur des Archivs mit Überschriften, Texten und Bildern darstellt.
 * Das Skript liest Konfigurationsvariablen, Arrays mit Überschriften und Bildinformationen,
 * und generiert dynamisch den HTML-Code für das Archiv basierend auf den angegebenen Informationen.
 * Die generierte Archivdatei wird im übergeordneten Verzeichnis gespeichert.
 * Das Skript erzeugt auch Thumbnails für die Bilder und verwendet Lazy Loading für eine verbesserte Leistung.
 *
 * Konfigurationsvariablen:
 * - $archiveDirInput: Der Pfad zum Archivverzeichnis.
 * - $archiveNameInput: Der Basename für die Archivdateien.
 * - $archiveNumberInput: Die Nummer des Archivs.
 * - $thumbnailsDirForAdmin: Das Verzeichnis für Thumbnails im Administrationsbereich.
 * - $thumbnailsDirForArchiv: Das Verzeichnis für Thumbnails im Archivbereich.
 * - $ComicPHPfileDirForArchiv: Das Verzeichnis für die Comic-PHP-Dateien im Archivbereich.
 *
 * Arrays:
 * - $startupPictures: Ein Array mit den Startbildern für jede Überschrift.
 * - $headings: Ein Array mit den Überschriften, Texten und Anzahl der Bilder für jedes Kapitel.
 *
 * Funktionen:
 * - generiereBildLinks(): Eine Funktion, die den HTML-Code für die Bild-Links generiert.
 *   Sie basiert auf den Startbildern und der Anzahl der Bilder für jedes Kapitel.
 *
 * Das Skript startet mit einer Startmeldung, generiert dann den Inhalt der Archivdatei
 * und speichert den generierten Inhalt in der Datei archive.php im übergeordneten Verzeichnis.
 * Zum Schluss wird eine Abschlussmeldung ausgegeben.
 */


// Funktion zum Generieren des HTML-Codes für die Bild-Links
function generiereBildLinks($startupPicture, $nubmerOfPictures, $thumbnailsDirForAdmin, $ComicPHPfileDirForArchiv, $thumbnailsDirForArchiv) {
    global $pictureStartNumber;
    $html = '';
    $currentImage = $startupPicture;
    while ($pictureStartNumber <= $nubmerOfPictures) {
        $bildDatei = $currentImage . '.jpg';
        if (file_exists($thumbnailsDirForAdmin . $bildDatei)) {
            $html .= '
            <a class="jsdep" href="' . $ComicPHPfileDirForArchiv .  $currentImage . '.php">
                <span>' . $pictureStartNumber . '</span>
                <img src="" alt="' . $pictureStartNumber . '" data-src="' . $thumbnailsDirForArchiv . $bildDatei . '">
            </a>';
            $pictureStartNumber++;
        }
        $currentImage = date('Ymd', strtotime($currentImage . ' +1 day'));
    }
    return $html;
}

/*function generiereBildLinks($startupPicture, $nubmerOfPictures, $thumbnailsDirForAdmin, $ComicPHPfileDirForArchiv, $thumbnailsDirForArchiv) {
    $html = '';
    $pictureNumber = 1;
    $currentImage = $startupPicture;
    while ($pictureNumber <= $nubmerOfPictures) {
        $bildDatei = $currentImage . '.jpg';
        if (file_exists($thumbnailsDirForAdmin . $bildDatei)) {
            $html .= '
            <a class="jsdep" href="' . $ComicPHPfileDirForArchiv .  $currentImage . '.php">
                <span>' . $pictureNumber . '</span>
                <img src="" alt="' . $pictureNumber . '" data-src="' . $thumbnailsDirForArchiv . $bildDatei . '">
            </a>';
            $pictureNumber++;
        }
        $currentImage = date('Ymd', strtotime($currentImage . ' +1 day'));
    }
    return $html;
}*/

// Startmeldung
echo '<h1 style="color: green; font-weight: bold;">Archiv-Generator gestartet</h1>';

// Generiere den Inhalt der archive.php-Datei
ob_start();
?>

<?php foreach ($headings as $chId => $data): ?>
<section class="chapter" data-ch-id="<?php echo $chId; ?>">
    <h2><?php echo $data['ueberschrift']; ?><span class="arrow-left jsdep"></span></h2>
    <p><?php echo $data['text']; ?></p>
    <aside class="chapter-links">
        <?php echo generiereBildLinks($startupPictures[$chId], $data['anzahlBilder'], $thumbnailsDirForAdmin, $ComicPHPfileDirForArchiv, $thumbnailsDirForArchiv); ?>
    </aside>
</section>
<?php endforeach; ?>

<?php
// Speichere den generierten Inhalt in die Datei archive.php im übergeordneten Verzeichnis
$archiveContent = ob_get_clean();

file_put_contents($archiveFileNameOutput, $archiveContent);

// Abschlussmeldung
echo '<h2 style="color: green; font-weight: bold;">Vorgang erfolgreich beendet</h2>';
echo '<p>Die Datei <a href="' . $archiveFileNameOutput . '" target="_blank">' . $archiveFileNameOutput . '</a> wurde erfolgreich erstellt!</p>';
echo '<p>Archivdatei jetzt aufrufen: <a href="' . $archiveFileDirOutputForAdmin . '" target="_blank">' . $archiveFileDirOutputForAdmin . '</a></p>'
?>