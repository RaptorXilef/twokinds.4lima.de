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
// Da Tailwind im HTML-Body direkt geladen wird, fügen wir hier keine weiteren Skripte hinzu,
// außer dem E-Mail-Skript, das im Header benötigt wird.
// Die Tailwind-CSS-Referenz und das Inline-Style-Tag werden direkt im HTML-Body sein.

// Binde den gemeinsamen Header ein.
include __DIR__ . "/src/layout/header.php";
if ($debugMode)
    error_log("DEBUG: Header in impressum.php eingebunden.");
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Grundlegende Stile für den Impressumsbereich */
    body {
        font-family: "Inter", sans-serif;
        /* Hintergrundfarbe und Padding sollten idealerweise global im Hauptlayout definiert sein. */
        /* Hier nur die Schriftart, um Konflikte zu vermeiden. */
    }

    .container {
        /* Tailwind-Klassen für maximale Breite, Zentrierung, Hintergrund, Padding, abgerundete Ecken, Schatten, vertikalen Margin */
        max-width: 48rem;
        /* max-w-3xl */
        margin-left: auto;
        /* mx-auto */
        margin-right: auto;
        /* mx-auto */
        background-color: #ffffff;
        /* bg-white */
        padding: 2rem;
        /* p-8 */
        border-radius: 0.5rem;
        /* rounded-lg */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        /* shadow-lg */
        margin-top: 2rem;
        /* my-8 */
        margin-bottom: 2rem;
        /* my-8 */
        color: #1f2937;
        /* text-gray-800 - Standardtextfarbe für den Container */
    }

    h1,
    h2,
    h3 {
        /* Tailwind-Klassen für Textfarbe, Fettschrift, unteren Margin */
        color: #111827;
        /* text-gray-900 */
        font-weight: 700;
        /* font-bold */
        margin-bottom: 1rem;
        /* mb-4 */
    }

    h1 {
        /* Spezifische Größe für H1 */
        font-size: 2.25rem;
        /* text-3xl */
    }

    h2 {
        /* Spezifische Größe für H2, Border und Padding */
        font-size: 1.5rem;
        /* text-2xl */
        border-bottom: 1px solid #e5e7eb;
        /* border-b */
        padding-bottom: 0.5rem;
        /* pb-2 */
        margin-bottom: 1.5rem;
        /* mb-6 */
    }

    h3 {
        /* Spezifische Größe für H3 */
        font-size: 1.25rem;
        /* text-xl */
    }

    p,
    ul {
        /* Tailwind-Klassen für unteren Margin und Zeilenhöhe */
        margin-bottom: 1rem;
        /* mb-4 */
        line-height: 1.625;
        /* leading-relaxed */
    }

    a {
        /* Tailwind-Klassen für Textfarbe und Hover-Effekt */
        color: #2563eb;
        /* text-blue-600 */
        text-decoration: none;
        /* remove default underline */
    }

    a:hover {
        text-decoration: underline;
        /* underline on hover */
    }

    button {
        /* Tailwind-Klassen für Hintergrund, Textfarbe, Padding, abgerundete Ecken, Schatten, Hover-Effekt */
        background-color: #2563eb;
        /* bg-blue-600 */
        color: #ffffff;
        /* text-white */
        padding-left: 1.5rem;
        /* px-6 */
        padding-right: 1.5rem;
        /* px-6 */
        padding-top: 0.75rem;
        /* py-3 */
        padding-bottom: 0.75rem;
        /* py-3 */
        border-radius: 0.5rem;
        /* rounded-lg */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        /* shadow-md */
        transition-property: background-color;
        /* transition-colors */
        transition-duration: 200ms;
        /* duration-200 */
    }

    button:hover {
        background-color: #1d4ed8;
        /* hover:bg-blue-700 */
    }

    #email-link {
        /* Tailwind-Klasse für oberen Margin */
        margin-top: 0.5rem;
        /* mt-2 */
    }

    .license-text {
        /* Tailwind-Klassen für Textgröße und Textfarbe */
        font-size: 0.875rem;
        /* text-sm */
        color: #4b5563;
        /* text-gray-600 */
    }

    /* Dark Mode Anpassungen */
    body.theme-night {
        /* Hintergrundfarbe und Textfarbe für den Body im Dark Mode */
        background-color: #1a202c;
        /* Dunklerer Hintergrund */
        color: #e2e8f0;
        /* Hellerer Text */
    }

    body.theme-night .container {
        background-color: #2d3748;
        /* Dunklerer Container-Hintergrund */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
        color: #e2e8f0;
        /* Textfarbe für den Container im Dark Mode */
    }

    body.theme-night h1,
    body.theme-night h2,
    body.theme-night h3 {
        color: #f7fafc;
        /* Hellerer Titeltext */
    }

    body.theme-night h2 {
        border-color: #4a5568;
        /* Dunklere Border */
    }

    body.theme-night a {
        color: #63b3ed;
        /* Helleres Blau für Links */
    }

    body.theme-night button {
        background-color: #4299e1;
        /* Helleres Blau für Buttons */
    }

    body.theme-night button:hover {
        background-color: #3182ce;
        /* Noch helleres Blau für Hover */
    }

    body.theme-night .license-text {
        color: #a0aec0;
        /* Hellerer Text für Lizenz */
    }
</style>

<!-- Der div.container wurde entfernt, da er wahrscheinlich durch das Hauptlayout bereitgestellt wird. -->
<!-- Der Inhalt des Impressums beginnt hier direkt. -->
<h2 class="text-3xl font-bold mb-6">Impressum</h2>

<h3 class="text-2xl font-semibold mb-4">Verantwortlich für den Inhalt:</h3>
<p>Felix Maywald (Übersetzer & Webseitenbetreiber)<br>
    Berlin<br>
    Deutschland</p>

<h3 class="text-2xl font-semibold mb-4 mt-6">Kontakt:</h3>
<p>Sie können mich über folgende Kanäle erreichen:</p>
<ul class="list-disc list-inside ml-4">
    <li><a href="https://github.com/RaptorXilef/twokinds.4lima.de" target="_blank" rel="noopener noreferrer">GitHub</a>
    </li>
    <li><a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" rel="noopener noreferrer">InkBunny</a></li>
    <li>E-Mail: Bitte klicken Sie auf den Button, um die E-Mail-Adresse anzuzeigen.
        <div id="email-link">
            <button onclick="zeigeEmail()">E-Mail anzeigen</button>
        </div>
    </li>
</ul>

<h3 class="text-2xl font-semibold mb-4 mt-6">Besondere Danksagung:</h3>
<p>Ein herzlicher Dank gilt Viktor Matthys und Cornelius Lehners. Ihre Arbeit an <a href="https://www.twokinds.de/"
        target="_blank" rel="noopener noreferrer">www.twokinds.de</a> war die Inspiration für diese Seite. Durch ihre
    jahrelange Übersetzung von TwoKinds haben sie mir in einer Zeit geringer Englischkenntnisse den Comic nähergebracht
    und mich motiviert, die Sprache fundiert zu erlernen.</p>
<p>Des Weiteren möchte ich mich bei Thomas Fischbach aufrichtig bedanken, dass er uns allen dieses wunderschöne,
    humorvolle und oft zum Nachdenken anregende Comicwerk kostenfrei zur Verfügung stellt. Als großer Fan besitze ich
    alle seine Werke auch in gedruckter Hardcoverfassung, teilweise sogar signiert. Möge er uns weiterhin lange mit
    seiner großartigen Kunst und seinen Geschichten beglücken. Vielen Dank!</p>

<h3 class="text-2xl font-semibold mb-4 mt-6">Haftungsausschluss:</h3>
<p>Ich weise Sie darauf hin, dass ich keinen Einfluss auf die Inhalte externer Seiten habe, die hier durch Links oder
    Banner eingebunden sind. Jegliche Haftung für Schäden, die Ihnen oder Dritten durch die Nutzung oder Nichtnutzung
    dieses Internetauftritts entstehen, wird abgelehnt. Im Falle eines Missbrauchs der auf dieser Webseite zur Verfügung
    gestellten Informationen behalte ich mir vor, entsprechende Sanktionen zu verhängen oder die betreffende Person von
    Teilen bzw. der gesamten Webseite auszuschließen. Es kann kein Anspruch auf dauerhafte und unterbrechungsfreie
    Verfügbarkeit der Webseite oder deren Inhalte erhoben werden. Änderungen sind vorbehalten. Eine detaillierte
    Beschreibung der Verwendungsrechte des hier zur Verfügung gestellten Inhalts finden Sie unter dem Punkt
    "Verwendungsrechte (Copyright)".</p>

<h3 class="text-2xl font-semibold mb-4 mt-6">Verwendungsrechte (Copyright):</h3>
<p>Sämtliche auf dieser Seite zur Verfügung gestellten Comics oder Bilder wurden von Tom Fischbach gezeichnet und unter
    der <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/" target="_blank"
        rel="noopener noreferrer">Creative Commons Attribution-NonCommercial-ShareAlike 3.0 US Lizenz</a>
    veröffentlicht. Weitere Informationen können <a href="http://twokinds.keenspot.com/?p=license" target="_blank"
        rel="noopener noreferrer">seiner Seite über die Lizenzierung</a> (Englisch) entnommen werden. Die
    Veröffentlichung und Verbreitung der hier publizierten Comics und Bilder unterliegt hiermit ebenfalls der <a
        href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/deed.de" target="_blank"
        rel="noopener noreferrer">Creative Commons Namensnennung – Nicht-kommerziell – Weitergabe unter gleichen
        Bedingungen 3.0 USA Lizenzierung</a> – dem identischen, deutschsprachigen Gegenstück zur englischen Version
    dieser CC-Lizenzierungsart.
    <br>
    <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/deed.de" target="_blank" rel="noopener noreferrer"><img
            src="https://licensebuttons.net/l/by-nc-sa/3.0/88x31.png" alt="CC BY-NC-SA 3.0"
            class="inline-block align-middle ml-2"></a>
</p>

<p class="mt-6">
    Der Quellcode dieser Webseite steht unter der <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de"
        target="_blank" rel="noopener noreferrer">Creative Commons Attribution-NonCommercial-ShareAlike 4.0
        International Lizenz</a>. Dies bedeutet, dass Sie den Code teilen und adaptieren dürfen, solange Sie die
    Namensnennung beibehalten und ihn nicht für kommerzielle Zwecke nutzen.
    <br>
    <a href="https://github.com/RaptorXilef/twokinds.4lima.de" target="_blank" rel="noopener noreferrer"><img
            src="https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png" alt="CC BY-NC-SA 4.0"
            class="inline-block align-middle ml-2"></a>
    <br>
    <br>
    Der vollständige Quellcode kann auf meiner GitHub-Seite (<a href="https://github.com/RaptorXilef/twokinds.4lima.de"
        target="_blank" rel="noopener noreferrer">github.com/RaptorXilef/twokinds.4lima.de</a>) eingesehen werden.
</p>

<h3 class="text-2xl font-semibold mb-4 mt-6">Fehler melden:</h3>
<p>Sollten Ihnen Fehler, technische Probleme oder inhaltliche Ungenauigkeiten auf dieser Webseite auffallen, bitte ich
    Sie, diese über die <a href="https://github.com/RaptorXilef/twokinds.4lima.de/issues" target="_blank"
        rel="noopener noreferrer">Issues-Funktion auf GitHub</a> zu melden. Dies ist der bevorzugte Weg, um
    sicherzustellen, dass Ihr Anliegen schnellstmöglich bearbeitet wird.</p>
<p>So melden Sie einen Fehler:</p>
<ul class="list-disc list-inside ml-4">
    <li>Besuchen Sie das GitHub-Repository der Webseite: <a href="https://github.com/RaptorXilef/twokinds.4lima.de"
            target="_blank" rel="noopener noreferrer">github.com/RaptorXilef/twokinds.4lima.de</a></li>
    <li>Klicken Sie auf den Tab "Issues" (Probleme).</li>
    <li>Klicken Sie auf "New Issue" (Neues Problem), um einen neuen Bericht zu erstellen.</li>
    <li>Beschreiben Sie den Fehler so detailliert wie möglich: Was ist passiert? Wann ist es passiert? Welche Schritte
        führen dazu, dass der Fehler auftritt? Wenn möglich, fügen Sie Screenshots oder Fehlermeldungen hinzu.</li>
    <li>Senden Sie den Issue ab.</li>
</ul>
<p>Vielen Dank für Ihre Mithilfe, die Webseite kontinuierlich zu verbessern!</p>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . "/src/layout/footer.php";
if ($debugMode)
    error_log("DEBUG: Footer in impressum.php eingebunden.");
?>