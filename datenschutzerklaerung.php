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

// Binde den gemeinsamen Header ein. DIES MUSS VOR JEDER VERWENDUNG VON $baseUrl ODER ANDEREN VARIABLEN AUS DEM HEADER ERFOLGEN!
include __DIR__ . "/src/layout/header.php";
if ($debugMode)
    error_log("DEBUG: Header in datenschutzerklaerung.php eingebunden.");

// Setze Parameter für den Header. Diese werden vom Header.php verwendet, wenn sie vor dem Include definiert werden.
// Wenn sie nach dem Include definiert werden, überschreiben sie die Standardwerte im Header.
// Hier definieren wir sie vor dem Include, damit header.php sie direkt nutzen kann.
$pageTitle = 'Datenschutzerklärung';
$pageHeader = 'Datenschutzerklärung';
$siteDescription = 'Erfahren Sie mehr über den Datenschutz auf der TwoKinds-Webseite.';
$robotsContent = 'noindex, follow'; // Diese Seite sollte nicht von Suchmaschinen indexiert werden

// Hier wird die cookie_consent.js geladen, damit die Funktion showCookieBanner verfügbar ist.
// Der Pfad ist relativ zum baseUrl, der in header.php definiert wird.
// KORREKTUR: Pfad zu cookie_consent.js angepasst, da datenschutzerklaerung.php im Hauptverzeichnis liegt
// Der zusätzliche Script-Block kann nun entfallen, da showCookieBanner global verfügbar ist.
$additionalScripts = ''; // Leere den additionalScripts-Block, da die Funktion global ist.
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
        <br>
        <p>Verantwortlicher für die Datenverarbeitung auf dieser Website ist:</p>
        <p>
            <!--[Dein Name/Firmenname]<br>
            [Deine Adresse]<br>
            [Deine E-Mail-Adresse]<br>
            [Deine Telefonnummer (optional)]-->
            Felix Maywald
        </p>
        <p>Die vollständigen Kontaktdaten findest du in unserem <a
                href="<?php echo htmlspecialchars($baseUrl); ?>impressum.php">Impressum</a>.</p>
    </section>

    <section>
        <h2>2. Arten der verarbeiteten Daten</h2>
        <ul>
            <li>Nutzungsdaten (z.B. besuchte Webseiten, Zugriffszeiten).</li>
            <li>Meta-/Kommunikationsdaten (z.B. Geräte-Informationen, IP-Adressen).</li>
            <li>Einwilligungsstatus für Cookies und vergleichbare Technologien.</li>
            <li>Informationen zu Lesezeichen und Archiv-Ansichten (lokal gespeichert).</li>
            <li>Sitzungsdaten (für den Admin-Bereich).</li>
            <li>Ihre Theme-Präferenz (lokal gespeichert).</li>
        </ul>
    </section>

    <section>
        <h2>3. Zwecke der Verarbeitung</h2>
        <p>Wir verarbeiten Ihre Daten zu folgenden Zwecken:</p>
        <ul>
            <li>Bereitstellung des Onlineangebotes, seiner Funktionen und Inhalte.</li>
            <li>Sicherstellung eines reibungslosen Verbindungsaufbaus der Website.</li>
            <li>Speicherung Ihrer Präferenzen (z.B. Cookie-Einwilligung, Lesezeichen, Archiv-Ansicht, Theme).</li>
            <li>Authentifizierung und Verwaltung von Benutzersitzungen (Admin-Bereich).</li>
            <li>Auswertung der Systemsicherheit und -stabilität.</li>
            <li>Reichweitenmessung und Marketing (insbesondere Google Analytics, sofern Sie zugestimmt haben).</li>
        </ul>
    </section>

    <section>
        <h2>4. Einsatz von Cookies und Speicherdiensten</h2>
        <p>Diese Website verwendet Cookies und vergleichbare Technologien (z.B. Local Storage), um die Funktionalität
            sicherzustellen und die Nutzung zu analysieren. Cookies sind kleine Textdateien, die auf Ihrem Endgerät
            gespeichert werden.</p>

        <h3>Technisch notwendige Funktionen und Speicherungen</h3>
        <p>Diese Elemente sind für den grundlegenden Betrieb unserer Webseite und die Bereitstellung von Funktionen,
            die Sie explizit anfordern, unerlässlich. Ihre Nutzung basiert auf unserem berechtigten Interesse (Art. 6
            Abs. 1 lit. f DSGVO)
            oder der Erfüllung eines Vertrages (Art. 6 Abs. 1 lit. b DSGVO) und erfordert keine gesonderte Einwilligung.
            Dazu gehören:</p>
        <ul>
            <li><b>Ihre Einwilligung (<code class="mono">cookie_consent</code>):</b> Ein Eintrag im Local Storage Ihres
                Browsers speichert Ihre
                Entscheidung bezüglich der Cookie-Einstellungen (akzeptiert/abgelehnt für Analyse-Cookies). Dies ist
                notwendig, damit der Cookie-Banner nicht bei jedem Besuch erneut erscheint.
                <br><em>Gespeicherte Daten:</em> Ein JSON-Objekt, das den Status Ihrer Einwilligung für jede
                Cookie-Kategorie enthält (z.B. <code class="mono">{"necessary": true, "analytics": true/false}</code>).
            </li>
            <li><b>Theme-Präferenz (<code class="mono">themePref</code>):</b> Speichert Ihre gewählte Theme-Einstellung
                (Hell/Dunkel/Systemstandard) im lokalen Speicher Ihres Browsers.
                <br><em>Gespeicherte Daten:</em> Eine numerische ID des gewählten Themes (0 für Systemstandard, 1 für
                Hell, 2 für Dunkel) oder kein Eintrag, wenn der Systemstandard gewählt ist.
            </li>
            <li><b>Lesezeichen (<code class="mono">comicBookmarks</code>):</b> Wenn Sie die Lesezeichen-Funktion nutzen,
                werden Ihre persönlichen
                Lesezeichen im lokalen Speicher Ihres Browsers gespeichert. Dies ermöglicht es Ihnen, Ihre Lesezeichen
                bei späteren Besuchen wiederzufinden.
                <br><em>Gespeicherte Daten:</em> Ein JSON-Array von Objekten, wobei jedes Objekt die ID des Comics, die
                Seitenzahl, den Permalink und die URL des Vorschaubildes des Lesezeichens enthält.
            </li>
            <li><b>Archiv-Ansicht (<code class="mono">archiveExpansion</code>):</b> Speichert den Aufklappstatus der
                Kapitel im Archiv im lokalen
                Speicher Ihres Browsers. Dies verbessert die Benutzerfreundlichkeit, indem Ihre bevorzugte Ansicht
                beibehalten wird.
                <br><em>Gespeicherte Daten:</em> Ein JSON-Objekt, das die IDs der aufgeklappten Kapitel und einen
                Zeitstempel für die Gültigkeit der Speicherung enthält.
            </li>
            <li><b>Administrations-Sitzung (Session-ID, z.B. <code class="mono">PHPSESSID</code>):</b> Ein temporäres
                Cookie, das für den Login
                und die Aufrechterhaltung Ihrer Sitzung im Admin-Bereich verwendet wird. Es ist nur für Administratoren
                relevant und wird beim Schließen des Browsers oder nach kurzer Inaktivität gelöscht.
                <br><em>Gespeicherte Daten:</em> Eine zufällige, eindeutige Zeichenfolge (Session-ID), die auf dem
                Server mit den Anmeldeinformationen des Administrators verknüpft ist (z.B. <code
                    class="mono">admin_logged_in</code>, <code class="mono">admin_username</code>). Es werden keine
                direkten personenbezogenen Daten im Cookie selbst gespeichert.
            </li>
        </ul>

        <h3>Analyse-Cookies (Google Analytics)</h3>
        <p>Diese Website nutzt Funktionen des Webanalysedienstes Google Analytics. Anbieter ist die Google Ireland
            Limited („Google“), Gordon House, Barrow Street, Dublin 4, Irland.</p>
        <p>Google Analytics verwendet „Cookies“. Die durch den Cookie erzeugten Informationen über Ihre Benutzung dieser
            Website werden in der Regel an einen Server von Google in den USA übertragen und dort gespeichert.</p>
        <p>Die Speicherung von Google Analytics-Cookies und die Nutzung dieses Analyse-Tools erfolgen ausschließlich
            auf Grundlage Ihrer <b>expliziten Einwilligung</b> (Art. 6 Abs. 1 lit. a DSGVO), die Sie über unseren
            Cookie-Banner erteilen oder widerrufen können. Die Datenübertragung in die USA basiert auf den
            Standardvertragsklauseln der EU-Kommission. Ihre IP-Adresse wird dabei anonymisiert.</p>
        <p>
            <b>Verarbeitete Daten:</b>
        <ul>
            <li><b>Nutzungsdaten:</b> Informationen über Ihre Interaktionen mit der Webseite (z.B. besuchte Seiten,
                Verweildauer, Klicks, Navigationspfade).</li>
            <li><b>Geräte- und Browserdaten:</b> Typ des Geräts, Betriebssystem, Browsertyp, Bildschirmauflösung.</li>
            <li><b>Anonymisierte IP-Adresse:</b> Ihre IP-Adresse wird vor der Speicherung gekürzt, um eine direkte
                Zuordnung zu Ihrer Person zu verhindern.</li>
            <li><b>Referrer-URL:</b> Die Webseite, von der Sie auf unsere Seite gelangt sind.</li>
        </ul>
        </p>
        <p>Weitere Informationen zum Umgang mit Nutzerdaten bei Google Analytics finden Sie in der
            Datenschutzerklärung von Google: <a href="https://support.google.com/analytics/answer/6004245?hl=de"
                target="_blank">https://support.google.com/analytics/answer/6004245?hl=de</a>.</p>

        <div style="text-align: center; margin-top: 20px;">
            <button class="button" onclick="window.showCookieBanner()">Cookie-Einstellungen ändern</button>
        </div>
    </section>

    <section>
        <h2>5. Ihre Rechte</h2>
        <p>Sie haben jederzeit das Recht, unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten
            personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht, die Berichtigung, Sperrung oder Löschung
            dieser Daten zu verlangen. Hierzu sowie zu weiteren Fragen zum Thema Datenschutz können Sie sich jederzeit
            unter der im Impressum angegebenen Adresse an uns wenden.</p>
        <p>Des Weiteren steht Ihnen ein Beschwerderecht bei der zuständigen Aufsichtsbehörde zu.</p>
    </section>

    <section>
        <h2>6. Widerspruch gegen Datenerfassung</h2>
        <p>Sie können die Erfassung Ihrer Daten durch Google Analytics verhindern, indem Sie Ihre Cookie-Einstellungen
            entsprechend anpassen. Die Einwilligung kann jederzeit für die Zukunft widerrufen werden.</p>
    </section>

</article>

<?php
// Binde den gemeinsamen Footer ein.
include __DIR__ . "/src/layout/footer.php";
if ($debugMode)
    error_log("DEBUG: Footer in datenschutzerklaerung.php eingebunden.");
?>