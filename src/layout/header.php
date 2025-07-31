<?php
/**
 * Gemeinsamer Header für alle Seiten.
 * Enthält die grundlegende HTML-Struktur, Meta-Tags, Stylesheets und Skripte.
 *
 * @param string $pageTitle Der spezifische Titel für die aktuelle Seite, der im Browser-Tab angezeigt wird.
 * @param string $pageHeader Der sichtbare H1-Header für die aktuelle Seite im Hauptinhaltsbereich.
 * @param string $bodyClass Eine optionale Klasse für das Body-Tag (z.B. 'preload' für Ladezustände).
 * @param string $additionalScripts Optionaler HTML-Code für zusätzliche Skripte, die im <head> Bereich eingefügt werden.
 * @param string $additionalHeadContent Optionaler HTML-Code für zusätzliche Meta-Tags oder Links im <head> Bereich.
 * @param string $viewportContent Der Inhalt des Viewport-Meta-Tags, steuert das Responsive Design.
 * @param string $siteDescription Die allgemeine Beschreibung der Webseite für SEO und Social Media.
 * @param string $robotsContent Inhalt des robots-Meta-Tags (Standard: "index, follow").
 */

// === DEBUG-MODUS STEUERUNG ===
// Setze auf true, um DEBUG-Meldungen zu aktivieren, auf false, um sie zu deaktivieren.
/* $debugMode = false; */

// Setzt das maximale Ausführungszeitlimit für das Skript, um Timeouts bei größeren Operationen zu vermeiden.
set_time_limit(300);

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Dynamische Basis-URL Bestimmung für die gesamte Anwendung ===
// Diese Logik ermittelt die Basis-URL dynamisch, unabhängig davon, ob die Seite lokal,
// im Intranet oder auf einem externen Server läuft und ob sie in einem Unterordner installiert ist.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Ermittle den Pfad des aktuellen Skripts relativ zum Webserver-Dokumenten-Root.
// Beispiel: /deinprojekt/src/layout/header.php
$scriptPath = $_SERVER['PHP_SELF'];

// Ermittle das Verzeichnis der aktuellen Datei (z.B. /deinprojekt/src/layout)
$currentDir = dirname($scriptPath);

// Ermittle den absoluten Dateisystempfad des Anwendungs-Roots.
// __FILE__ ist der absolute Pfad zu header.php (z.B. /var/www/html/deinprojekt/src/layout/header.php)
// dirname(__FILE__) ist /var/www/html/deinprojekt/src/layout
// dirname(dirname(__FILE__)) ist /var/www/html/deinprojekt/src
// dirname(dirname(dirname(__FILE__))) ist /var/www/html/deinprojekt (dies ist der Anwendungs-Root)
$appRootAbsPath = str_replace('\\', '/', dirname(dirname(dirname(__FILE__))));

// Ermittle den absoluten Dateisystempfad des Webserver-Dokumenten-Roots.
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));

// Berechne den Unterordner-Pfad relativ zum Dokumenten-Root des Webservers.
// Wenn documentRoot /var/www/html ist und appRootAbsPath /var/www/html/deinprojekt,
// dann wird subfolderPath /deinprojekt.
$subfolderPath = str_replace($documentRoot, '', $appRootAbsPath);

// Stelle sicher, dass der Unterordner-Pfad mit einem Schrägstrich beginnt und endet.
if (!empty($subfolderPath) && $subfolderPath !== '/') {
    $subfolderPath = '/' . trim($subfolderPath, '/') . '/';
} elseif (empty($subfolderPath)) {
    $subfolderPath = '/'; // Wenn der Anwendungs-Root GLEICH dem Dokumenten-Root ist.
}

$baseUrl = $protocol . $host . $subfolderPath;

// Debug-Ausgabe, falls $debugMode aktiviert ist
if (isset($debugMode) && $debugMode) {
    error_log("DEBUG: Dynamische Basis-URL: " . $baseUrl);
}
// === Ende Dynamische Basis-URL Bestimmung ===

// Basis-Dateiname der aktuellen PHP-Datei ohne Erweiterung, wird für den Standard-Titel verwendet.
$filenameWithoutExtension = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);

// Standardpräfix für den Seitentitel. Dieser kann in einzelnen Seiten vor dem Include überschrieben werden,
// falls ein spezifischerer Präfix gewünscht ist.
$pageTitlePrefix = 'TwoKinds auf Deutsch - ';

// Standardwerte für Parameter, falls sie in der aufrufenden Datei nicht gesetzt werden.
// Der Seiten-Titel wird aus dem Präfix und dem übergebenen $pageTitle zusammengesetzt.
$pageTitle = $pageTitlePrefix . (isset($pageTitle) ? $pageTitle : ucfirst($filenameWithoutExtension));
$pageHeader = isset($pageHeader) ? $pageHeader : ''; // Standardmäßig leer, da viele Seiten einen eigenen Header haben.
$bodyClass = isset($bodyClass) ? $bodyClass : 'preload';
$additionalScripts = isset($additionalScripts) ? $additionalScripts : '';
$additionalHeadContent = isset($additionalHeadContent) ? $additionalHeadContent : '';
$viewportContent = isset($viewportContent) ? $viewportContent : 'width=device-width, initial-scale=1.0';
// Standardbeschreibung der Webseite, kann von einzelnen Seiten überschrieben werden.
$siteDescription = isset($siteDescription) ? $siteDescription : 'Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.';
// Standardwert für den robots-Meta-Tag, der bei Comicseiten oder Adminseiten überschrieben wird.
$robotsContent = isset($robotsContent) ? $robotsContent : 'index, follow';


// --- Dynamische Pfadbestimmung für common.js ---
// Der Pfad zur common.js ist relativ zum Anwendungs-Root: src/layout/js/common.js
$commonJsWebPath = $baseUrl . 'src/layout/js/common.js';
// Füge einen Cache-Buster hinzu, basierend auf der letzten Änderungszeit der common.js Datei
// Pfad zur Datei auf dem Dateisystem: dirname(__FILE__) ist src/layout/, also js/common.js
$commonJsWebPathWithCacheBuster = $commonJsWebPath . '?c=' . filemtime(__DIR__ . '/js/common.js');
// --- Ende Dynamische Pfadbestimmung ---

// --- Pfade für Cookie Banner CSS und JS ---
// Die Pfade sind relativ zum aktuellen Verzeichnis der header.php (src/layout/)
$cookieBannerCssPath = $baseUrl . 'src/layout/css/cookie_banner.css';
$cookieBannerDarkCssPath = $baseUrl . 'src/layout/css/cookie_banner_dark.css';
$cookieConsentJsPath = $baseUrl . 'src/layout/js/cookie_consent.js';

// Cache-Buster für Cookie Banner Dateien
$cookieBannerCssPathWithCacheBuster = $cookieBannerCssPath . '?c=' . filemtime(__DIR__ . '/css/cookie_banner.css');
$cookieBannerDarkCssPathWithCacheBuster = $cookieBannerDarkCssPath . '?c=' . filemtime(__DIR__ . '/css/cookie_banner_dark.css');
$cookieConsentJsPathWithCacheBuster = $cookieConsentJsPath . '?c=' . filemtime(__DIR__ . '/js/cookie_consent.js');
// --- Ende Pfade für Cookie Banner ---

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <title>
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="keywords"
        content="TwoKinds, in, auf, deutsch, übersetzt, uebersetzt, Web, Comic, Tom, Fischbach, RaptorXilef, Felix, Maywald, Reni, Nora, Trace, Flora, Keith, Natani, Zen, Sythe, Nibbly, Raine, Laura, Saria, Eric, Kathrin, Mike, Evals, Madelyn, Maren, Karen, Red, Templer, Keidran, Basitin, Mensch">
    <meta name="author" content="Felix Maywald, Design und Rechte by Thomas J. Fischbach & Brandon J. Dusseau">
    <meta name="viewport" content="<?php echo htmlspecialchars($viewportContent); ?>">
    <meta name="last-modified" content="<?php echo date('Y-m-d H:i:s', filemtime(__FILE__)); ?>">

    <?php
    // Pfad zur Sitemap-Datei.
    // Verwendet nun die dynamisch ermittelte baseUrl
    $sitemapURL = $baseUrl . 'sitemap.xml';
    ?>
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo htmlspecialchars($sitemapURL); ?>">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />

    <!-- Robots Meta Tag für Indexierungssteuerung -->
    <meta name="robots" content="<?php echo htmlspecialchars($robotsContent); ?>">

    <!-- Standard-Stylesheets für das Hauptdesign. -->
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20250524">
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20250524">

    <!-- NEU: Stylesheets für den Cookie-Banner -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($cookieBannerCssPathWithCacheBuster); ?>">
    <link rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($cookieBannerDarkCssPathWithCacheBuster); ?>">


    <!-- Favicons für verschiedene Browser und Geräte. -->
    <link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">

    <!-- Standard-JavaScript-Dateien. common.js wird nun vom lokalen Server geladen. -->
    <script type='text/javascript' src='<?php echo htmlspecialchars($commonJsWebPathWithCacheBuster); ?>'></script>
    <!-- NEU: Cookie-Consent-Skript -->
    <script type='text/javascript' src='<?php echo htmlspecialchars($cookieConsentJsPathWithCacheBuster); ?>'></script>

    <?php
    // Setze eine JavaScript-Variable, die angibt, ob es sich um eine Admin-Seite handelt.
    // Dies wird von common.js verwendet, um bestimmte Funktionen zu deaktivieren.
    $isAdminPage = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'true' : 'false';
    echo "<script type='text/javascript'>window.isAdminPage = " . $isAdminPage . ";</script>";
    ?>

    <?php
    // Hier können zusätzliche Skripte eingefügt werden, die spezifisch für die aufrufende Seite sind.
    echo $additionalScripts;
    // Hier können zusätzliche Meta-Tags, Links etc. eingefügt werden, die spezifisch für die aufrufende Seite sind.
    echo $additionalHeadContent;
    ?>
</head>

<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <!-- NEU: Cookie-Consent-Banner -->
    <div id="cookieConsentBanner">
        <h3>Datenschutz-Einstellungen</h3>
        <p>Wir verwenden Cookies und vergleichbare Technologien, um die Funktionalität unserer Webseite zu gewährleisten
            und die Nutzung zu analysieren. Bitte treffen Sie Ihre Auswahl:</p>

        <div class="cookie-category">
            <label for="cookieNecessary">
                <input type="checkbox" id="cookieNecessary" checked disabled>
                Notwendige Funktionen
            </label>
            <p class="description">Diese Speicherungen sind für den grundlegenden Betrieb der Webseite unerlässlich und
                können nicht deaktiviert werden. Sie speichern keine direkt personenbezogenen Daten, die eine
                Identifizierung erlauben, sondern dienen der technischen Funktionalität und Ihrer Nutzungserfahrung.</p>

            <!-- Äußerer einklappbarer Bereich für notwendige Cookies -->
            <div class="toggle-details-container">
                <span class="toggle-details" data-target="details-necessary-cookies">Details anzeigen <i
                        class="fas fa-chevron-right toggle-icon"></i></span>
                <div id="details-necessary-cookies" class="collapsible-content">
                    <ul>
                        <li>
                            <b>Ihre Einwilligung (<code class="mono">cookie_consent</code>):</b> Speichert Ihre
                            Entscheidung bezüglich der Cookie-Einstellungen (akzeptiert/abgelehnt für Analyse-Cookies)
                            im lokalen Speicher Ihres Browsers. Dies ist notwendig, damit der Banner nicht bei jedem
                            Besuch erneut erscheint.
                            <span class="toggle-details" data-target="details-consent">Erfahre mehr darüber <i
                                    class="fas fa-chevron-right toggle-icon"></i></span>
                            <div id="details-consent" class="collapsible-content inner-collapsible">
                                <em>Gespeicherte Daten:</em> Ein JSON-Objekt, das den Status Ihrer Einwilligung für jede
                                Cookie-Kategorie enthält (z.B. <code
                                    class="mono">{"necessary": true, "analytics": true/false}</code>).
                            </div>
                        </li>
                        <li>
                            <b>Theme-Präferenz (<code class="mono">themePref</code>):</b> Speichert Ihre gewählte
                            Theme-Einstellung (Hell/Dunkel/Systemstandard) im lokalen Speicher Ihres Browsers.
                            <span class="toggle-details" data-target="details-theme">Erfahre mehr darüber <i
                                    class="fas fa-chevron-right toggle-icon"></i></span>
                            <div id="details-theme" class="collapsible-content inner-collapsible">
                                <em>Gespeicherte Daten:</em> Eine numerische ID des gewählten Themes (0 für
                                Systemstandard, 1 für Hell, 2 für Dunkel) oder kein Eintrag, wenn der Systemstandard
                                gewählt ist.
                            </div>
                        </li>
                        <li>
                            <b>Lesezeichen (<code class="mono">comicBookmarks</code>):</b> Speichert die IDs der von
                            Ihnen gesetzten Lesezeichen im lokalen Speicher Ihres Browsers. Dies ermöglicht es Ihnen,
                            Ihre Lesezeichen bei späteren Besuchen wiederzufinden.
                            <span class="toggle-details" data-target="details-bookmarks">Erfahre mehr darüber <i
                                    class="fas fa-chevron-right toggle-icon"></i></span>
                            <div id="details-bookmarks" class="collapsible-content inner-collapsible">
                                <em>Gespeicherte Daten:</em> Ein JSON-Array von Objekten, wobei jedes Objekt die ID des
                                Comics, die Seitenzahl, den Permalink und die URL des Vorschaubildes des Lesezeichens
                                enthält.
                            </div>
                        </li>
                        <li>
                            <b>Archiv-Ansicht (<code class="mono">archiveExpansion</code>):</b> Speichert den
                            Aufklappstatus der Kapitel im Archiv im lokalen Speicher Ihres Browsers. Dies verbessert die
                            Benutzerfreundlichkeit, indem Ihre bevorzugte Ansicht beibehalten wird.
                            <span class="toggle-details" data-target="details-archive">Erfahre mehr darüber <i
                                    class="fas fa-chevron-right toggle-icon"></i></span>
                            <div id="details-archive" class="collapsible-content inner-collapsible">
                                <em>Gespeicherte Daten:</em> Ein JSON-Objekt, das die IDs der aufgeklappten Kapitel und
                                einen Zeitstempel für die Gültigkeit der Speicherung enthält.
                            </div>
                        </li>
                        <li>
                            <b>Administrations-Sitzung (Session-ID, z.B. <code class="mono">PHPSESSID</code>):</b> Ein
                            temporäres Cookie, das für den Login und die Aufrechterhaltung Ihrer Sitzung im
                            Admin-Bereich verwendet wird. Es ist nur für Administratoren relevant und wird beim
                            Schließen des Browsers oder nach Inaktivität gelöscht.
                            <span class="toggle-details" data-target="details-admin">Erfahre mehr darüber <i
                                    class="fas fa-chevron-right toggle-icon"></i></span>
                            <div id="details-admin" class="collapsible-content inner-collapsible">
                                <em>Gespeicherte Daten:</em> Eine zufällige, eindeutige Zeichenfolge (Session-ID), die
                                auf dem Server mit den Anmeldeinformationen des Administrators verknüpft ist (z.B. <code
                                    class="mono">admin_logged_in</code>, <code class="mono">admin_username</code>). Es
                                werden keine direkten personenbezogenen Daten im Cookie selbst gespeichert.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="cookie-category">
            <label for="cookieAnalytics">
                <input type="checkbox" id="cookieAnalytics" checked>
                Analyse-Cookies (Google Analytics) <br> Wichtig für die Webseiten-Weiterentwicklung
            </label>
            <p class="description">Diese Cookies helfen mir zu verstehen, wie Besucher mit der Webseite interagieren,
                indem Informationen über Ihre Nutzung (z.B. besuchte Seiten, Verweildauer, verwendetes Gerät,
                anonymisierte IP-Adresse) gesammelt und an Google Analytics übertragen werden. Diese Daten werden
                ausschließlich zur Verbesserung der Webseite verwendet und ermöglichen keine direkte Identifizierung
                Ihrer Person. <br><br> Die Daten werden <b>nicht</b> für das schalten personalisierter Werbung
                verwendet!</p>
        </div>

        <div class="cookie-buttons">
            <button id="acceptAllCookies">Alle akzeptieren</button>
            <button id="rejectAllCookies">Alle ablehnen</button>
            <button id="saveCookiePreferences">Auswahl speichern</button>
        </div>
    </div>
    <!-- Ende Cookie-Consent-Banner -->

    <div id="mainContainer" class="main-container">
        <!-- Hinweis auf das Fanprojekt und Link zum Original. -->
        <center>Dieses Fanprojekt ist die deutsche Übersetzung von <a href="https://twokinds.keenspot.com/"
                target="_blank">twokinds.keenspot.com</a><br><br>Die Homepage wird derzeit überarbeitet und erhält
            etliche neue Elemente und Funktionen.<br>Bis alle Transcripte auf deutsch verfügbar sind, wird es noch etwas
            dauern. Bitte hab noch etwas geduld. Danke :-)<br><br>Den aktuellen Stand der Updates erfährst du hier: <a
                href="https://github.com/RaptorXilef/twokinds.4lima.de/" target="_blank">GitHub</a></center>
        <div id="banner-lights-off" class="banner-lights-off"></div>
        <!-- Hauptbanner der Webseite. -->
        <div id="banner" class="banner">Twokinds</div>
        <div id="content-area" class="content-area">
            <div id="sidebar" class="sidebar">

                <?php
                // Dynamisches Laden der Menükonfiguration basierend auf dem aktuellen Pfad
                // __DIR__ ist das Verzeichnis der aktuellen Datei (src/layout)
                // $_SERVER['PHP_SELF'] ist der Pfad zum aktuell aufgerufenen Skript (z.B. /admin/index.php oder /index.php)
                if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
                    // Wenn sich die aufgerufene Seite im /admin/-Verzeichnis befindet, lade das Admin-Menü
                    // Der Pfad ist von src/layout/ nach ../components/
                    require(__DIR__ . '/../components/admin_menue_config.php');
                } else {
                    // Andernfalls lade das normale Seitenmenü
                    // Der Pfad ist von src/layout/ nach ../components/
                    require(__DIR__ . '/../components/menue_config.php');
                }
                ?>

            </div>
            <main id="content" class="content">
                <article>
                    <?php
                    // Der Seiten-Header wird hier dynamisch eingefügt, wenn er übergeben wurde.
                    // Er wird nur angezeigt, wenn die Seite im Admin-Verzeichnis liegt.
                    if (!empty($pageHeader) && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
                        echo '<header>';
                        echo '    <h1 class="page-header">' . htmlspecialchars($pageHeader) . '</h1>';
                        echo '</header>';
                    }
                    ?>