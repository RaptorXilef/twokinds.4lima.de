<?php
/**
 * Dies ist die Datenschutzerklärung der TwoKinds-Webseite.
 * Sie informiert über die Datenerfassung und -verarbeitung.
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
$debugMode = false;

if ($debugMode)
    error_log("DEBUG: datenschutzerklaerung.php wird geladen.");

// Setze Parameter für den Header.
$pageTitle = 'Datenschutzerklärung';
$pageHeader = 'Datenschutzerklärung';
$siteDescription = 'Erfahren Sie mehr über den Datenschutz auf der TwoKinds-Webseite.';
$robotsContent = 'noindex, follow'; // Diese Seite sollte nicht von Suchmaschinen indexiert werden

// Hier wird die cookie_consent.js geladen, damit die Funktion showCookieBanner verfügbar ist.
// Der Pfad ist relativ zum baseUrl, der in header.php definiert wird.
// KORREKTUR: Pfad zu cookie_consent.js angepasst, da datenschutzerklaerung.php im Hauptverzeichnis liegt
$additionalScripts = '
    <script type="text/javascript" src="' . htmlspecialchars($baseUrl) . 'src/layout/js/cookie_consent.js?c=' . filemtime(__DIR__ . '/src/layout/js/cookie_consent.js') . '"></script>
    <script>
        // Funktion, um den Cookie-Banner erneut anzuzeigen
        function openCookieSettings() {
            if (typeof window.showCookieBanner === "function") {
                window.showCookieBanner();
            } else {
                console.error("showCookieBanner Funktion nicht verfügbar.");
                // KEIN alert(), stattdessen eine Meldung im Modal oder auf der Seite anzeigen
                // Für dieses Beispiel belassen wir es bei console.error, da kein Modal definiert ist.
            }
        }
    </script>
';

// Binde den gemeinsamen Header ein.
include __DIR__ . "/src/layout/header.php";
if ($debugMode)
    error_log("DEBUG: Header in datenschutzerklaerung.php eingebunden.");
?>

<article>
    <header>
        <h1 class="page-header">Datenschutzerklärung</h1>
    </header>

    <section>
        <h2>1. Einleitung und Verantwortlicher</h2>
        <p>Diese Datenschutzerklärung klärt Sie über die Art, den Umfang und den Zweck der Verarbeitung von
            personenbezogenen Daten (nachfolgend kurz „Daten“) innerhalb unseres Onlineangebotes und der mit ihm
            verbundenen Webseiten, Funktionen und Inhalte sowie externen Onlinepräsenzen, wie z.B. unser Social Media
            Profil, auf. (nachfolgend gemeinsam bezeichnet als „Onlineangebot“).</p>
        <p>Verantwortlicher für die Datenverarbeitung auf dieser Website ist:</p>
        <p>
            [Dein Name/Firmenname]<br>
            [Deine Adresse]<br>
            [Deine E-Mail-Adresse]<br>
            [Deine Telefonnummer (optional)]
        </p>
        <p>Die vollständigen Kontaktdaten findest du in unserem Impressum.</p>
    </section>

    <section>
        <h2>2. Arten der verarbeiteten Daten</h2>
        <ul>
            <li>Nutzungsdaten (z.B. besuchte Webseiten, Zugriffszeiten).</li>
            <li>Meta-/Kommunikationsdaten (z.B. Geräte-Informationen, IP-Adressen).</li>
        </ul>
    </section>

    <section>
        <h2>3. Zwecke der Verarbeitung</h2>
        <p>Wir verarbeiten Ihre Daten zu folgenden Zwecken:</p>
        <ul>
            <li>Bereitstellung des Onlineangebotes, seiner Funktionen und Inhalte.</li>
            <li>Sicherstellung eines reibungslosen Verbindungsaufbaus der Website.</li>
            <li>Auswertung der Systemsicherheit und -stabilität.</li>
            <li>Reichweitenmessung und Marketing (insbesondere Google Analytics, sofern Sie zugestimmt haben).</li>
        </ul>
    </section>

    <section>
        <h2>4. Einsatz von Cookies und Speicherdiensten</h2>
        <p>Diese Website verwendet Cookies. Cookies sind kleine Textdateien, die auf Ihrem Endgerät gespeichert werden.
            Einige der von uns verwendeten Cookies sind so genannte „Session-Cookies“. Sie werden nach Ende Ihres
            Besuchs automatisch gelöscht. Andere Cookies bleiben auf Ihrem Endgerät gespeichert, bis Sie diese löschen.
            Diese Cookies ermöglichen es uns, Ihren Browser beim nächsten Besuch wiederzuerkennen.</p>
        <p>Detaillierte Informationen zu den verwendeten Cookies finden Sie in unseren Cookie-Einstellungen, die Sie
            jederzeit über den untenstehenden Button aufrufen können.</p>

        <div style="text-align: center; margin-top: 20px;">
            <button class="button" onclick="openCookieSettings()">Cookie-Einstellungen ändern</button>
        </div>
    </section>

    <section>
        <h2>5. Google Analytics</h2>
        <p>Diese Website nutzt Funktionen des Webanalysedienstes Google Analytics. Anbieter ist die Google Ireland
            Limited („Google“), Gordon House, Barrow Street, Dublin 4, Irland.</p>
        <p>Google Analytics verwendet „Cookies“. Die durch den Cookie erzeugten Informationen über Ihre Benutzung dieser
            Website werden in der Regel an einen Server von Google in den USA übertragen und dort gespeichert.</p>
        <p>Die Speicherung von Google Analytics-Cookies und die Nutzung dieses Analyse-Tools erfolgen auf Grundlage
            Ihrer Einwilligung (Art. 6 Abs. 1 lit. a DSGVO), die Sie über unseren Cookie-Banner erteilen oder widerrufen
            können. Die Datenübertragung in die USA basiert auf den Standardvertragsklauseln der EU-Kommission.</p>
        <p>Weitere Informationen zum Umgang mit Nutzerdaten bei Google Analytics finden Sie in der
            Datenschutzerklärung von Google: <a href="https://support.google.com/analytics/answer/6004245?hl=de"
                target="_blank">https://support.google.com/analytics/answer/6004245?hl=de</a>.</p>
    </section>

    <section>
        <h2>6. Ihre Rechte</h2>
        <p>Sie haben jederzeit das Recht, unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten
            personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht, die Berichtigung, Sperrung oder Löschung
            dieser Daten zu verlangen. Hierzu sowie zu weiteren Fragen zum Thema Datenschutz können Sie sich jederzeit
            unter der im Impressum angegebenen Adresse an uns wenden.</p>
        <p>Des Weiteren steht Ihnen ein Beschwerderecht bei der zuständigen Aufsichtsbehörde zu.</p>
    </section>

    <section>
        <h2>7. Widerspruch gegen Datenerfassung</h2>
        <p>Sie können die Erfassung Ihrer Daten durch Google Analytics verhindern, indem Sie Ihre Cookie-Einstellungen
            entsprechend anpassen. Die Einwilligung kann jederzeit für die Zukunft widerrufen werden.</p>
    </section>

</article>

<?php
include __DIR__ . "/src/layout/footer.php";
if ($debugMode)
    error_log("DEBUG: Footer in datenschutzerklaerung.php eingebunden.");
?>