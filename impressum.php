<?php
/**
 * Diese Datei enthält das Impressum der Webseite.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: impressum.php wird geladen.");

// JavaScript für die E-Mail-Anzeige als zusätzliche Skripte an den Header übergeben.
$emailScript = '
<script type="text/javascript">
  // JavaScript-Code zur Generierung der E-Mail-Adresse, um Spam-Bots zu vermeiden.
  function zeigeEmail() {
    var email = \'MCRaptorDragon\' + \'@\' + \'gmail.com\';
    document.getElementById(\'email-link\').innerHTML = \'<a href="mailto:\' + email + \'">\' + email + \'</a>\';
  }
</script>
';

// Setze Parameter für den Header. Der Seitentitel wird im Header automatisch mit Präfix versehen.
$pageTitle = 'Impressum';
$pageHeader = 'Impressum'; // Dieser Wert wird im Hauptinhaltsbereich angezeigt.
$additionalScripts = $emailScript; // Füge das E-Mail-Skript hinzu.
include __DIR__ . "/src/layout/header.php";
if ($debugMode)
    error_log("DEBUG: Header in impressum.php eingebunden.");
?>
<p>
<h3>Verantwortlich für den Inhalt:</h3>
Felix Maywald (<span class="Lizenz">Übersetzer & Webseitenbetriber</span>)<br>
Berlin<br>
Deutschland<br><br><br><br>


<h3>Ein großes Danke an:</h3>
Viktor Matthys und Cornelius Lehners<br>
welche mich mit <a href="https://www.twokinds.de/">www.twokinds.de</a> auf die Idee für diese Seite gebracht haben.<br>
Ihr habt jahrelang TwoKinds übersetzt und mich in der Zeit meiner miserablen Englischkenntnisse dem Comic näher gebracht
und mich animiert, die Sprache richtig zu lernen. <br>
Ebenfalls möchte ich mich bei Thomas Fischbach bedanken, dass er uns allen kostenfrei dieses wunderschöne, lustige und
oft zum Nachdenken anregende Comicwerk zur Verfügung stellt. Ich bin ein riesengroßer Fan und besitze alle Werke auch in
gedruckter Hardcoverfassung, teils mit Signatur. Bitte mach so weiter und beglücke uns noch lange mit deiner tollen
Kunst und Geschichte. Danke!<br>
</p><br><br>
<p>
<h3>Kontakt:</h3>
<span id="Kontakt">
    Ihr könnt mich über <a href="https://inkbunny.net/RaptorXilefSFW">InkBunny</a> <br>
    oder E-mail erreichen:

    <div id="email-link">
        <button onclick="zeigeEmail()">E-Mail anzeigen</button>
    </div>
    <br>
</span>
</p><br>
<p>
<h3>Haftungsausschluss:</h3>
Ich möchte Sie darauf Hinweisen, dass ich keinen Einfluss auf die hier durch Links oder Banner eingebundenen Seiten oder
deren Inhalte haben.<br>
Ebenso lehne ich jegliche Haftung für Schäden, die Ihnen oder Drittpersonen durch die Nutzung oder nicht-Nutzung dieses
Internetauftrittes entstehen, ab.<br>
Im Falle eines Missbrauchs der auf dieser Webseite zur Verfügung gestellten Informationen behalte ich mir vor, jener
missbrauch betreibenden Person Sanktionen aufzuerlegen oder die Person von einem Teil beziehungsweise der gesamten
Webseite auszuschliessen.<br>
Es kann keinen Anspruch auf dauerhafte und unterbrechungsfreie Verfügbarkeit der Webseite oder deren Inhalte erhoben
werden.<br>
Änderungen vorbehalten.<br>
Eine detaillierte Beschreibung bezüglich der Verwendungsrechte des hier zur Verfügung gestellten Inhalts finden Sie
unter dem Punkt "Verwendungsrechte (Copyright)".
</p>
<p>
<h3>Verwendungsrechte (Copyright):</h3>
Sämtliche auf dieser Seite zur Verfügung gestellten Comics oder Bilder wurden von <span class="Lizenz">Tom
    Fischbach</span> gezeichnet und unter der <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/"
    target="_blank"><span class="Lizenz">Creative Commons Attribution-NonCommercial-ShareAlike 3.0 US</span> Lizenz</a>
veröffentlicht. Weitere Informationen können <a href="http://twokinds.keenspot.com/?p=license" target="_blank">seiner
    Seite über die Lizenzierung</a> (Englisch) entnommen werden.<br>
Die Veröffentlichung und Verbreitung der hier publizierten Comics und Bildern unterliegt hiermit ebenfalls der <a
    href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/deed.de" target="_blank"><span class="Lizenz">Creative
        Commons Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 3.0 USA</span>
    Lizenzierung</a> - dem identischen, deutschsprachigen Gegenstück zur englischen Version dieser CC-Lizenzierungsart.
</p>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . "/src/layout/footer.php";
if ($debugMode)
    error_log("DEBUG: Footer in impressum.php eingebunden.");
?>