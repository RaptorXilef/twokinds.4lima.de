<?php
/**
 * Dies ist eine Informationsseite über die Nutzung von RSS-Feeds.
 * Sie erklärt, was RSS-Feeds sind, ihre Vorteile und wie man sie mit verschiedenen Readern nutzt.
 * 
 * @file      ROOT/public/rss_anleitung.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     1.1.0 Umstellung auf globale Pfad-Konstanten.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// === 1. ZENTRALE INITIALISIERUNG (Sicherheit & Basis-Konfiguration) ===
// Dieser Pfad MUSS relativ bleiben, da er die Konfigurationen und die Path-Klasse erst lädt.
require_once __DIR__ . '/../src/components/public_init.php';

// === 2. VARIABLEN FÜR DEN HEADER SETZEN ===
$pageTitle = 'So nutzen Sie RSS-Feeds';
$siteDescription = 'Erfahre mehr darüber, wie du RSS-Feeds nutzen kannst, um mit der deutschen TwoKinds-Übersetzung immer auf dem neuesten Stand zu bleiben.';
$robotsContent = 'index, follow';

// Die URL des RSS-Feeds, die dynamisch eingefügt wird.
$rssFeedUrl = htmlspecialchars(DIRECTORY_PUBLIC_URL) . '/rss.xml';

// === 3. HEADER EINBINDEN (Jetzt mit Path-Klasse) ===
require_once Path::getTemplatePartial('header.php');
?>

<style nonce="<?php echo htmlspecialchars($nonce); ?>">
    /* Ersetzt den Inline-Stil für das RSS-Icon (CSP-Konformität) */
    .rss-icon-inline {
        border-radius: 5px;
        cursor: pointer;
    }
</style>

<article>
    <header>
        <h1 class="page-header">So nutzt du RSS-Feeds: Immer auf dem neuesten Stand bleiben</h1>
    </header>

    <p>RSS-Feeds sind eine einfache und effiziente Methode, um über Aktualisierungen deiner Lieblingswebseiten, Blogs,
        Nachrichtenquellen oder TwoKinds-Übersetzungen :D auf dem Laufenden zu bleiben, ohne diese ständig manuell
        überprüfen zu müssen. Erfahre hier, wie du RSS-Feeds optimal nutzen und welche Software du dafür benötigst.</p>

    ---

    <h2>Was ist ein RSS-Feed und welche Vorteile bietet er?</h2>
    <p>Ein RSS-Feed (Really Simple Syndication) ist ein spezielles Dateiformat, das die neuesten Inhalte einer Webseite
        in einer maschinenlesbaren Form bereitstellt. Anstatt die Webseite regelmäßig zu besuchen, um nach neuen
        Artikeln oder Nachrichten zu suchen, sendet der RSS-Feed diese Informationen direkt an dich.</p>

    <h3>Die größten Vorteile von RSS-Feeds:</h3>
    <ul>
        <li><strong>Zeitersparnis:</strong> Du musst die Webseite nicht ständig besuchen, um zu sehen, ob es neue
            Inhalte gibt. Dein RSS-Reader informiert dich automatisch.</li>
        <li><strong>Zentrale Informationsquelle:</strong> Sammle Inhalte von verschiedenen Webseiten an einem einzigen
            Ort.</li>
        <li><strong>Keine Ablenkung:</strong> RSS-Reader zeigen oft nur den reinen Inhalt an, ohne Werbung oder andere
            störende Elemente der Webseite.</li>
        <li><strong>Offline-Verfügbarkeit:</strong> Viele RSS-Reader laden Inhalte herunter, sodass du diese auch ohne
            Internetverbindung lesen kannst.</li>
        <li><strong>Privatsphäre:</strong> Im Gegensatz zu Newslettern musst du für RSS-Feeds keine persönlichen Daten
            wie deine E-Mail-Adresse angeben.</li>
    </ul>

    ---

    <h2>Wie nutze ich einen RSS-Feed?</h2>
    <p>Um einen RSS-Feed zu nutzen, benötigst du einen sogenannten <strong>RSS-Reader</strong> (oder RSS-Aggregator).
        Dies ist eine Software oder ein Dienst, der die RSS-Feeds deiner gewählten Quellen abruft und dir die neuen
        Inhalte übersichtlich darstellt.</p>

    <p>Die URL meines RSS-Feeds findest du meist als kleines Icon
        (<a href="<?php echo htmlspecialchars(DIRECTORY_PUBLIC_URL); ?>/rss.xml" target="_blank" title="Mein RSS-Feed"
            id="rssFeedLink">
            <img src="<?php echo Path::getIcon('rss-feed.png'); ?>" alt="RSS" width="16" height="16"
                class="rss-icon-inline">
        </a>)
        auf meiner Webseite oder du kannst sie direkt hier abrufen: <code><?php echo $rssFeedUrl; ?></code></p>

    ---

    <h2>Empfohlene RSS-Reader für verschiedene Plattformen</h2>

    <h3>Android</h3>
    <p>Für Android-Geräte gibt es viele gute RSS-Reader. Eine empfehlenswerte App ist <strong>"Easy RSS - Simple RSS
            Reader"</strong>. Diese App ermöglicht es dir, RSS-Feeds über einen Button einfach hinzuzufügen und
        benachrichtigt dich regelmäßig über Änderungen. Ein großer Vorteil ist, dass die Feed-Informationen bei
        bestehender Internetverbindung heruntergeladen werden und somit auch offline lesbar sind.</p>
    <p><strong>So funktioniert's mit "Easy RSS":</strong></p>
    <ol>
        <li>Lade die App "Easy RSS - Simple RSS Reader" aus dem Google Play Store herunter: <a
                href="https://play.google.com/store/apps/details?id=com.vanniktech.rssreader" target="_blank">Easy RSS
                im Google Play Store</a></li>
        <li>Öffne die App.</li>
        <li>Suche nach einem "Hinzufügen"-Button (oft ein Plus-Symbol) oder einer Option wie "Feed hinzufügen".</li>
        <li>Gib die URL meines RSS-Feeds (<code><?php echo $rssFeedUrl; ?></code>) in das entsprechende Feld ein und
            bestätige.</li>
        <li>Die App beginnt nun, die Inhalte meines Feeds abzurufen und dich über Neuigkeiten zu informieren.</li>
        <li>Da ich den Feed genau mit dieser App teste, ist der Feed ganau auf diese App angepasst :D</li>
    </ol>

    <h3>iPhone (iOS)</h3>
    <p>Auch für iPhones gibt es zahlreiche RSS-Reader. Eine beliebte und funktionsreiche Option ist
        <strong>"Feedly"</strong>.
    </p>
    <p><strong>So funktioniert's mit Feedly:</strong></p>
    <ol>
        <li>Lade die Feedly-App aus dem Apple App Store herunter.</li>
        <li>Erstelle ein Konto oder melde dich an.</li>
        <li>Nutze die Suchfunktion oder den "Folgen"-Button, um meinen RSS-Feed über die URL
            (<code><?php echo $rssFeedUrl; ?></code>) hinzuzufügen.</li>
        <li>Feedly synchronisiert die Inhalte und hält dich auf dem Laufenden.</li>
    </ol>

    <h3>Windows PC</h3>
    <p>Für Windows-Computer gibt es sowohl eigenständige Anwendungen als auch webbasierte Dienste.</p>
    <ul>
        <li><strong>Desktop-App: "QuiteRSS"</strong> ist ein kostenloser und funktionaler Desktop-Reader.</li>
        <li><strong>Webbasiert: "Inoreader"</strong> oder <strong>"Feedly"</strong> (wie oben für iPhone beschrieben)
            sind plattformübergreifende Webdienste, die du einfach über deinen Browser nutzen kannst.</li>
    </ul>
    <p><strong>So funktioniert's (Beispiel QuiteRSS):</strong></p>
    <ol>
        <li>Lade QuiteRSS von der offiziellen Webseite herunter und installiere es.</li>
        <li>Öffne die Anwendung.</li>
        <li>Wähle "Datei" > "Feed hinzufügen" (oder ein ähnliches Menü).</li>
        <li>Füge die RSS-Feed URL (<code><?php echo $rssFeedUrl; ?></code>) ein.</li>
    </ol>

    <h3>Mac PC</h3>
    <p>macOS-Nutzer können ebenfalls auf eine Vielzahl von RSS-Readern zurückgreifen.</p>
    <ul>
        <li><strong>Desktop-App: "NetNewsWire"</strong> ist ein beliebter, kostenloser und quelloffener RSS-Reader für
            macOS.</li>
        <li><strong>Webbasiert: "Feedly"</strong> oder <strong>"Inoreader"</strong> bieten auch hier eine hervorragende
            Lösung im Browser.</li>
    </ul>
    <p><strong>So funktioniert's (Beispiel NetNewsWire):</strong></p>
    <ol>
        <li>Lade NetNewsWire aus dem Mac App Store herunter oder von der offiziellen Webseite.</li>
        <li>Öffne die Anwendung.</li>
        <li>Klicke auf das "+" Symbol oder wähle "Datei" > "Neuer Feed".</li>
        <li>Gib die RSS-Feed URL (<code><?php echo $rssFeedUrl; ?></code>) ein.</li>
    </ol>

    <h3>Linux PC</h3>
    <p>Linux-Distributionen bieten ebenfalls verschiedene Optionen für RSS-Reader.</p>
    <ul>
        <li><strong>Desktop-App: "Akregator"</strong> (KDE) oder <strong>"Thunderbird"</strong> (E-Mail-Client mit
            integriertem RSS-Reader) sind gute Optionen.</li>
        <li><strong>Webbasiert: "Inoreader"</strong> oder <strong>"Feedly"</strong> sind auch hier eine gute,
            plattformunabhängige Wahl.</li>
    </ul>
    <p><strong>So funktioniert's (Beispiel Thunderbird):</strong></p>
    <ol>
        <li>Installiere Thunderbird, falls noch nicht geschehen.</li>
        <li>Gehe zu "Datei" > "Neu" > "Feed-Konto".</li>
        <li>Folge den Anweisungen und füge die RSS-Feed URL (<code><?php echo $rssFeedUrl; ?></code>) hinzu.</li>
    </ol>

    <h3>Huawei (Geräte ohne Google Play Dienste)</h3>
    <p>Für Huawei-Geräte, die keinen Zugriff auf den Google Play Store haben, musst du Apps aus der Huawei AppGallery
        oder alternativen Quellen beziehen. Eine App, die möglicherweise in der AppGallery verfügbar ist oder über eine
        APK-Datei installiert werden kann, ist <strong>"Palabre"</strong> oder <strong>"Feedly"</strong> (falls als APK
        verfügbar oder über deren Webseite).</p>
    <p>Alternativ kannst du einen <strong>webbasierten RSS-Reader</strong> wie <strong>"Feedly"</strong> oder
        <strong>"Inoreader"</strong> direkt über den Browser auf deinem Huawei-Gerät nutzen, ohne eine App installieren
        zu müssen.
    </p>
    <p><strong>So funktioniert's (Beispiel webbasierter Reader):</strong></p>
    <ol>
        <li>Öffne den Browser auf deinem Huawei-Gerät.</li>
        <li>Besuche die Webseite eines webbasierten RSS-Readers (z.B. <a href="https://feedly.com"
                target="_blank">feedly.com</a> oder <a href="https://www.inoreader.com"
                target="_blank">inoreader.com</a>).</li>
        <li>Registriere dich oder melde dich an.</li>
        <li>Füge meinen RSS-Feed über die bereitgestellte URL (<code><?php echo $rssFeedUrl; ?></code>) hinzu.</li>
    </ol>

    ---

    <p>Ich hoffe, diese Anleitung hilft dir dabei, meine Inhalte noch einfacher und effizienter zu verfolgen. Bei
        Fragen stehe ich dir gerne zur Verfügung!</p>

</article>

<?php
// === FUSSZEILE EINBINDEN (Jetzt mit Path-Klasse) ===
require_once Path::getTemplatePartial('footer.php');
?>