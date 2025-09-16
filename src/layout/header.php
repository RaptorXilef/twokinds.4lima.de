<?php
/**
 * Gemeinsamer, modularer Header für alle Seiten (SEO-optimierte Version).
 *
 * Diese Datei wurde verschlankt und konzentriert sich auf die HTML-Struktur und das Asset-Management.
 * Die sicherheitsrelevanten Initialisierungen wurden in separate `init.php`-Dateien ausgelagert.
 *
 * @param string $pageTitle Der spezifische Titel für die aktuelle Seite.
 * @param string $pageHeader Der sichtbare H1-Header für die aktuelle Seite im Hauptinhaltsbereich.
 * @param string $bodyClass Eine optionale Klasse für das Body-Tag (z.B. 'preload' für Ladezustände).
 * @param string $additionalScripts Optionaler HTML-Code für zusätzliche Skripte, die im <head> Bereich eingefügt werden.
 * @param string $additionalHeadContent Optionaler HTML-Code für zusätzliche Meta-Tags oder Links im <head> Bereich.
 * @param string $viewportContent Der Inhalt des Viewport-Meta-Tags, steuert das Responsive Design.
 * @param string $siteDescription Die Beschreibung der Seite für SEO und Social Media.
 * @param string $ogImage Die URL zu einem Vorschaubild für Social Media (optional).
 * @param string $robotsContent Inhalt des robots-Meta-Tags (Standard: "index, follow").
 * @param string $canonicalUrl Eine explizite URL für den Canonical-Tag (optional, überschreibt die automatisch generierte URL).
 * ... weitere Parameter ...
 */

// --- 1. Dynamische Basis-URL und Seiten-URL Bestimmung ---
// Diese Logik ermittelt die Basis-URL dynamisch, unabhängig davon, ob die Seite lokal,
// im Intranet oder auf einem externen Server läuft und ob sie in einem Unterordner installiert ist.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
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
// Erstellt die vollständige, aktuelle URL für Canonical und OG-Tags.
$currentPageUrl = rtrim($baseUrl, '/') . $_SERVER['REQUEST_URI'];

// --- NEU: Flexible Canonical URL ---
// Nutze die explizit gesetzte $canonicalUrl, ansonsten die automatisch ermittelte $currentPageUrl.
$finalCanonicalUrl = $canonicalUrl ?? $currentPageUrl;

if (isset($debugMode) && $debugMode) {
    error_log("DEBUG: Basis-URL: " . $baseUrl . ", Seiten-URL: " . $currentPageUrl . ", Canonical-URL: " . $finalCanonicalUrl);
}

// --- 2. Prüfung, ob eine Initialisierungsdatei geladen wurde ---
if (!isset($nonce)) {
    $nonce = bin2hex(random_bytes(16));
    error_log("WARNUNG: Keine init.php-Datei vor dem Header geladen. Eine Fallback-Nonce wurde generiert.");
}

// --- 3. Setup der Seiten-Variablen mit Standardwerten ---
$isAdminPage = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$filenameWithoutExtension = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);
$pageTitlePrefix = 'Twokinds – Das Webcomic auf Deutsch | ';
$pageTitle = $pageTitlePrefix . ($pageTitle ?? ucfirst($filenameWithoutExtension));
$siteDescription = $siteDescription ?? 'Tauche ein in die Welt von Twokinds – dem beliebten Fantasy-Webcomic von Tom Fischbach, jetzt komplett auf Deutsch verfügbar. Erlebe die spannende Geschichte von Trace und Flora und entdecke die Rassenkonflikte zwischen Menschen und Keidran.';
$ogImage = $ogImage ?? ''; // Standardmäßig kein OG-Image
$robotsContent = $robotsContent ?? 'index, follow';
$bodyClass = $bodyClass ?? 'preload';
$additionalScripts = $additionalScripts ?? '';
$additionalHeadContent = $additionalHeadContent ?? '';
$viewportContent = $viewportContent ?? 'width=device-width, initial-scale=1.0';

// --- Web-Pfade mit Cache-Buster generieren ---
$faviconUrl = $baseUrl . 'favicon.ico?v=' . filemtime($appRootAbsPath . '/favicon.ico');
$appleIconUrl = $baseUrl . 'appleicon.png?v=' . filemtime($appRootAbsPath . '/appleicon.png');

// Pfade zu Assets mit Cache-Busting
$commonJsWebPathWithCacheBuster = $baseUrl . 'src/layout/js/common.min.js?c=' . filemtime(__DIR__ . '/js/common.min.js');
$mainCssPathWithCacheBuster = $baseUrl . 'src/layout/css/main.min.css?c=' . filemtime(__DIR__ . '/css/main.min.css');
$mainDarkCssPathWithCacheBuster = $baseUrl . 'src/layout/css/main_dark.min.css?c=' . filemtime(__DIR__ . '/css/main_dark.min.css');
$cookieBannerCssPathWithCacheBuster = $baseUrl . 'src/layout/css/cookie_banner.min.css?c=' . filemtime(__DIR__ . '/css/cookie_banner.min.css');
$cookieBannerDarkCssPathWithCacheBuster = $baseUrl . 'src/layout/css/cookie_banner_dark.min.css?c=' . filemtime(__DIR__ . '/css/cookie_banner_dark.min.css');
$cookieConsentJsPathWithCacheBuster = $baseUrl . 'src/layout/js/cookie_consent.min.js?c=' . filemtime(__DIR__ . '/js/cookie_consent.min.js');
// NEU: Pfad für die Charakter-Anzeige CSS, im Stil der bestehenden Pfade
$characterDisplayCssPathWithCacheBuster = $baseUrl . 'src/layout/css/character_display.min.css?c=' . filemtime(__DIR__ . '/css/character_display.css');
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="<?php echo htmlspecialchars($viewportContent); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robotsContent); ?>">
    <meta name="author" content="Felix Maywald, Design und Rechte by Thomas J. Fischbach & Brandon J. Dusseau">
    <meta name="keywords"
        content="Twokinds, 2kinds, Webcomic, Tom Fischbach, Deutsch, Übersetzung, Fantasy, Manga, Comic, Trace Legacy, Flora, Keidran, Basitin, Fanprojekt, Felix Maywald, RaptorXielf" />
    <meta name="description" content="<?php echo htmlspecialchars($siteDescription); ?>" />

    <!-- Dynamische Canonical & Open Graph URLs -->
    <link rel="canonical" href="<?php echo htmlspecialchars($finalCanonicalUrl); ?>" />
    <meta property="og:url" content="<?php echo htmlspecialchars($finalCanonicalUrl); ?>" />
    <meta name="last-modified" content="<?php echo date('Y-m-d H:i:s', filemtime(__FILE__)); ?>">

    <!-- Open Graph Meta Tags für Social Media -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($siteDescription); ?>" />
    <meta property="og:type" content="website" />
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>" />
    <?php endif; ?>

    <!-- Weitere Meta-Informationen -->
    <link rel="sitemap" type="application/xml" title="Sitemap"
        href="<?php echo htmlspecialchars($baseUrl . 'sitemap.xml'); ?>">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />

    <!-- Stylesheets -->
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($mainCssPathWithCacheBuster); ?>" fetchpriority="high">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($mainDarkCssPathWithCacheBuster); ?>" fetchpriority="high">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($cookieBannerCssPathWithCacheBuster); ?>">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($cookieBannerDarkCssPathWithCacheBuster); ?>">
    <!-- NEU: Stylesheet für Charakter-Anzeige -->
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($characterDisplayCssPathWithCacheBuster); ?>">

    <?php if ($isAdminPage): ?>
        <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <?php endif; ?>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo htmlspecialchars($faviconUrl); ?>">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="<?php echo htmlspecialchars($appleIconUrl); ?>">

    <!-- JavaScript -->
    <script nonce="<?php echo htmlspecialchars($nonce); ?>" type='text/javascript'
        src='<?php echo htmlspecialchars($commonJsWebPathWithCacheBuster); ?>'></script>
    <script nonce="<?php echo htmlspecialchars($nonce); ?>" type='text/javascript'
        src='<?php echo htmlspecialchars($cookieConsentJsPathWithCacheBuster); ?>'></script>
    <script nonce="<?php echo htmlspecialchars($nonce); ?>"
        type='text/javascript'>window.isAdminPage = <?php echo ($isAdminPage ? 'true' : 'false'); ?>;</script>

    <?php
    echo $additionalScripts;
    echo $additionalHeadContent;
    ?>
</head>

<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <!-- Cookie-Consent-Banner -->
    <div id="cookieConsentBanner">
        <h3>Datenschutz-Einstellungen</h3>
        <p>Ich verwende Cookies und vergleichbare Technologien, um die Funktionalität dieser Webseite zu gewährleisten
            und die Nutzung zu analysieren. Bitte treffe deine Auswahl:</p>

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
        <center>Dieses Fanprojekt ist die deutsche Übersetzung von <a href="https://twokinds.keenspot.com/"
                target="_blank">twokinds.keenspot.com</a></center>
        <div id="banner-lights-off" class="banner-lights-off"></div>
        <div id="banner" class="banner">Twokinds</div>
        <div id="content-area" class="content-area">
            <div id="sidebar" class="sidebar">
                <?php
                // Dynamisches Laden der Menükonfiguration basierend auf dem aktuellen Pfad
                if ($isAdminPage) {
                    // *** BESTIMMUNGS-LOGIK ***
                    // Lade das Admin-Menü und das Timeout-Modal NUR, wenn der Admin auch wirklich eingeloggt ist.
                    // Die Prüfung 'isset($_SESSION['admin_logged_in'])' stellt sicher, dass das Menü nicht auf der Login-Seite angezeigt wird.
                    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
                        require(__DIR__ . '/../../admin/src/components/admin_menue_config.php');
                        require(__DIR__ . '/../../admin/src/components/session_timeout_modal.php');
                    } else {
                        require(__DIR__ . '/../../admin/src/components/admin_menue_config_login.php');
                    }
                } else {
                    // Andernfalls lade das normale Seitenmenü für öffentliche Seiten
                    require(__DIR__ . '/../components/menue_config.php');
                }
                ?>
            </div>
            <main id="content" class="content">
                <article>
                    <?php
                    // Der Seiten-Header wird hier dynamisch eingefügt, wenn er übergeben wurde.
                    // Er wird nur angezeigt, wenn die Seite im Admin-Verzeichnis liegt.
                    if (!empty($pageHeader) && $isAdminPage) {
                        echo '<header><h1 class="page-header">' . htmlspecialchars($pageHeader) . '</h1></header>';
                    }
                    ?>