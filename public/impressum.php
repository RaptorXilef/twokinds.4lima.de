<?php

/**
 * Diese Datei enthält das Impressum der Webseite.
 *
 * @file      ROOT/public/impressum.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 * @since     5.0.0 refactor(Page): Inline-CSS und Tailwind-Logik durch SCSS-Klasse .imprint-container ersetzt.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/init_public.php';

// === 2. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'Impressum';
$siteDescription = 'Impressum und Kontaktinformationen für das deutsche TwoKinds Fanprojekt.';
$robotsContent = 'noindex, follow';

// JavaScript für die E-Mail-Anzeige (CSP-konform mit Event Listener)
$additionalScripts = '
<script nonce="' . htmlspecialchars($nonce) . '" type="text/javascript">
  document.addEventListener(\'DOMContentLoaded\', function() {
    const emailButton = document.getElementById(\'email-button\');
    if (emailButton) {
        emailButton.addEventListener(\'click\', function() {
            const email = \'MCRaptorDragon\' + \'@\' + \'gmail.com\';
            const mailtoLink = \'<a href="mailto:\' + email + \'">\' + email + \'</a>\';
            const container = document.getElementById(\'email-container\');
            if (container) {
                container.innerHTML = mailtoLink;
            }
        });
    }
  });
</script>
';

// === 3. HEADER EINBINDEN (Jetzt mit Path-Klasse) ===
require_once Path::getPartialTemplatePath('header.php');
?>

<article>
    <header>
        <h1 class="page-header">Impressum</h1>
    </header>

    <div class="imprint-container">
        <h3>Verantwortlich für den Inhalt:</h3>
        <p>Felix Maywald (Übersetzer & Webseitenbetreiber)<br>
            Berlin<br>
            Deutschland</p>

        <h3>Kontakt:</h3>
        <p>Sie können mich über folgende Kanäle erreichen:</p>
        <ul>
            <li><a href="https://github.com/RaptorXilef/twokinds.4lima.de" target="_blank"
                    rel="noopener noreferrer">GitHub</a>
            </li>
            <li><a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" rel="noopener noreferrer">InkBunny</a></li>
            <li>E-Mail: Bitte klicken Sie auf den Button, um die E-Mail-Adresse anzuzeigen.
                <div id="email-container">
                    <button id="email-button" type="button">E-Mail anzeigen</button>
                </div>
            </li>
        </ul>

        <h3>Besondere Danksagung:</h3>
        <p>Ein herzlicher Dank gilt Viktor Matthys und Cornelius Lehners. Ihre Arbeit an <a href="https://www.twokinds.de/"
                target="_blank" rel="noopener noreferrer">www.twokinds.de</a> war die Inspiration für diese Seite. Durch
            ihre jahrelange Übersetzung von TwoKinds haben sie mir in einer Zeit geringer Englischkenntnisse den Comic
            nähergebracht und mich motiviert, die Sprache zu erlernen.</p>
        <p>Des Weiteren möchte ich mich bei Thomas Fischbach aufrichtig bedanken, dass er uns allen dieses wunderschöne,
            humorvolle und oft zum Nachdenken anregende Comicwerk kostenfrei zur Verfügung stellt. Als großer Fan besitze
            ich alle seine Werke auch in gedruckter Hardcoverfassung, teilweise sogar signiert. Möge er uns weiterhin lange mit
            seiner großartigen Kunst und seinen Geschichten beglücken. Vielen Dank!</p>

        <h3>Haftungsausschluss:</h3>
        <p>Ich weise Sie darauf hin, dass ich keinen Einfluss auf die Inhalte externer Seiten habe, die hier durch Links
            oder Banner eingebunden sind. Jegliche Haftung für Schäden, die Ihnen oder Dritten durch die Nutzung oder
            Nichtnutzung dieses Internetauftritts entstehen, wird abgelehnt. Im Falle eines Missbrauchs der auf dieser Webseite zur
            Verfügung gestellten Informationen behalte ich mir vor, entsprechende Sanktionen zu verhängen oder die betreffende Person
            von Teilen bzw. der gesamten Webseite auszuschließen. Es kann kein Anspruch auf dauerhafte und unterbrechungsfreie
            Verfügbarkeit der Webseite oder deren Inhalte erhoben werden. Änderungen sind vorbehalten. Eine detaillierte
            Beschreibung der Verwendungsrechte des hier zur Verfügung gestellten Inhalts finden Sie unter dem Punkt
            "Verwendungsrechte (Copyright)".</p>

        <h3>Verwendungsrechte (Copyright):</h3>
        <p>Sämtliche auf dieser Seite zur Verfügung gestellten Comics oder Bilder wurden von Tom Fischbach gezeichnet und
            unter der <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/" target="_blank"
                rel="noopener noreferrer">Creative Commons Attribution-NonCommercial-ShareAlike 3.0 US Lizenz</a>
            veröffentlicht. Weitere Informationen können <a href="http://twokinds.keenspot.com/?p=license" target="_blank"
                rel="noopener noreferrer">seiner Seite über die Lizenzierung</a> (Englisch) entnommen werden. Die
            Veröffentlichung und Verbreitung der hier publizierten Comics und Bilder unterliegt hiermit ebenfalls der <a
                href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/deed.de" target="_blank"
                rel="noopener noreferrer">Creative Commons Namensnennung – Nicht-kommerziell – Weitergabe unter gleichen
                Bedingungen 3.0 USA Lizenzierung</a> – dem identischen, deutschsprachigen Gegenstück zur englischen Version
            dieser CC-Lizenzierungsart.
            <br>
            <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/deed.de" target="_blank"
                rel="noopener noreferrer"><img src="https://licensebuttons.net/l/by-nc-sa/3.0/88x31.png"
                    alt="CC BY-NC-SA 3.0" class="license-image"></a>
        </p>
        <p>
            Der Quellcode dieser Webseite steht unter der <a
                href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de" target="_blank"
                rel="noopener noreferrer">Creative Commons Attribution-NonCommercial-ShareAlike 4.0
                International Lizenz</a>. Dies bedeutet, dass Sie den Code teilen und adaptieren dürfen, solange Sie die
            Namensnennung beibehalten und ihn nicht für kommerzielle Zwecke nutzen.
            <br>
            <a href="https://github.com/RaptorXilef/twokinds.4lima.de" target="_blank" rel="noopener noreferrer"><img
                    src="https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png" alt="CC BY-NC-SA 4.0"
                    class="license-image"></a>
            <br>
            <br>
            Der vollständige Quellcode kann auf meiner GitHub-Seite (<a
                href="https://github.com/RaptorXilef/twokinds.4lima.de" target="_blank"
                rel="noopener noreferrer">github.com/RaptorXilef/twokinds.4lima.de</a>) eingesehen werden.
        </p>

        <h3>Fehler melden:</h3>
        <p>Sollten Ihnen Fehler, technische Probleme oder inhaltliche Ungenauigkeiten auf dieser Webseite auffallen, bitte
            ich Sie, diese über die <a href="https://github.com/RaptorXilef/twokinds.4lima.de/issues" target="_blank"
                rel="noopener noreferrer">Issues-Funktion auf GitHub</a> zu melden. Dies ist der bevorzugte Weg, um
            sicherzustellen, dass Ihr Anliegen schnellstmöglich bearbeitet wird.</p>
        <p>So melden Sie einen Fehler:</p>
        <ul>
            <li>Besuchen Sie das GitHub-Repository der Webseite: <a href="https://github.com/RaptorXilef/twokinds.4lima.de"
                    target="_blank" rel="noopener noreferrer">github.com/RaptorXilef/twokinds.4lima.de</a></li>
            <li>Klicken Sie auf den Tab "Issues" (Probleme).</li>
            <li>Klicken Sie auf "New Issue" (Neues Problem), um einen neuen Bericht zu erstellen.</li>
            <li>Beschreiben Sie den Fehler so detailliert wie möglich.</li>
            <li>Senden Sie den Issue ab.</li>
        </ul>
        <p>Vielen Dank für Ihre Mithilfe, die Webseite kontinuierlich zu verbessern!</p>
    </div>
</article>

<?php require_once Path::getPartialTemplatePath('footer.php'); ?>
