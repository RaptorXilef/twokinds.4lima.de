<?php
/**
 * Gemeinsamer, modularer Header für alle Seiten (SEO-optimierte Version).
 * 
 * @file      ROOT/templates/partials/header.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     2.6.0 Diese Datei wurde verschlankt und konzentriert sich auf die HTML-Struktur und das Asset-Management.
 * Die sicherheitsrelevanten Initialisierungen wurden in separate `init.php`-Dateien ausgelagert.
 * @since     2.7.0 Entfernung redundanter URL-Berechnung und Ersetzung von Pfaden durch globale Konstanten.
 * @since     2.7.1 Korrektur der JS-Asset-Pfade auf Basis der finalen config_folder_path.php.
 * @since     2.7.2 Finale Validierung gegen die neueste Pfad-Konfiguration.
 * @since     3.0.0 Umstellung auf das finale, granulare Konstanten-System (DIRECTORY_..., ..._URL, DIRECTORY_PUBLIC_URL).
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
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
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

// --- 1. PRÜFUNG, OB INITIALISIERUNG ERFOLGTE ---
// Die init-Dateien stellen BASE_URL und $nonce bereit. Hier wird ein Fallback sichergestellt.
/*if (!defined('BASE_URL') || !isset($nonce)) {
    $nonce = bin2hex(random_bytes(16));
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $host);
    error_log("WARNUNG: Keine init.php-Datei vor dem Header geladen. Fallback-Werte werden verwendet.");
}*/

// --- 2. SEITEN-URL UND CANONICAL-URL BESTIMMEN ---
$currentPageUrl = rtrim(DIRECTORY_PUBLIC_URL, '/') . $_SERVER['REQUEST_URI'];
$finalCanonicalUrl = $canonicalUrl ?? $currentPageUrl;

if (isset($debugMode) && $debugMode) {
    error_log("DEBUG (header.php): Basis-URL: " . DIRECTORY_PUBLIC_URL . ", Seiten-URL: " . $currentPageUrl . ", Canonical-URL: " . $finalCanonicalUrl);
}

// --- 3. SETUP DER SEITEN-VARIABLEN MIT STANDARDWERTEN ---
$isAdminPage = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$filenameWithoutExtension = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);
$pageTitlePrefix = 'Twokinds – Das Webcomic auf Deutsch | ';
$pageTitle = $pageTitlePrefix . ($pageTitle ?? ucfirst($filenameWithoutExtension));
$siteDescription = $siteDescription ?? 'Tauche ein in die Welt von Twokinds – dem beliebten Fantasy-Webcomic von Tom Fischbach, jetzt komplett auf Deutsch verfügbar. Erlebe die spannende Geschichte von Trace und Flora und entdecke die Rassenkonflikte zwischen Menschen und Keidran.';
$ogImage = $ogImage ?? '';
$robotsContent = $robotsContent ?? 'index, follow';
$bodyClass = $bodyClass ?? 'preload';
$additionalScripts = $additionalScripts ?? '';
$additionalHeadContent = $additionalHeadContent ?? '';
$viewportContent = $viewportContent ?? 'width=device-width, initial-scale=1.0';

// --- 4. WEB-PFADE MIT CACHE-BUSTER GENERIEREN (NEUE METHODE) ---
// Helferfunktion, um die URL-Generierung und das Cache-Busting zu kapseln.
function getVersionedUrl(string $url, string $serverPath): string
{
    if (file_exists($serverPath)) {
        return $url . '?c=' . filemtime($serverPath);
    }
    return $url;
}

$faviconUrl = DIRECTORY_PUBLIC_URL . '/favicon.ico?v=' . filemtime(DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'favicon.ico');
$appleIconUrl = DIRECTORY_PUBLIC_URL . '/appleicon.png?v=' . filemtime(DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . 'appleicon.png');

// Generiere versionierte URLs für CSS und JS mit der Path-Klasse
$mainCssUrl = getVersionedUrl(Url::getCss('main.min.css'), DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'main.min.css');
$mainDarkCssUrl = getVersionedUrl(Url::getCss('main_dark.min.css'), DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'main_dark.min.css');
$cookieBannerCssUrl = getVersionedUrl(Url::getCss('cookie_banner.min.css'), DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'cookie_banner.min.css');
$cookieBannerDarkCssUrl = getVersionedUrl(Url::getCss('cookie_banner_dark.min.css'), DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'cookie_banner_dark.min.css');
$characterDisplayCssUrl = getVersionedUrl(Url::getCss('character_display.min.css'), DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'character_display.min.css');

$commonJsUrl = getVersionedUrl(Url::getJs('common.min.js'), DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'common.min.js');
$cookieConsentJsUrl = getVersionedUrl(Url::getJs('cookie_consent.min.js'), DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'cookie_consent.min.js');

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
        href="<?php echo htmlspecialchars($mainCssUrl); ?>" fetchpriority="high">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($mainDarkCssUrl); ?>" fetchpriority="high">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($cookieBannerCssUrl); ?>">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($cookieBannerDarkCssUrl); ?>">
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($characterDisplayCssUrl); ?>">

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
        src='<?php echo htmlspecialchars($commonJsUrl); ?>'></script>
    <script nonce="<?php echo htmlspecialchars($nonce); ?>" type='text/javascript'
        src='<?php echo htmlspecialchars($cookieConsentJsUrl); ?>'></script>
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
                // Dynamisches Laden der Menükonfiguration mit der Path-Klasse
                if ($isAdminPage) {
                    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
                        require_once Path::getConfig('config_menue_admin.php');
                        require_once Path::getTemplatePartialAdmin('session_timeout_modal.php');
                    } else {
                        require_once Path::getConfig('config_menue_admin_login.php');
                    }
                } else {
                    require_once Path::getConfig('config_menue_public.php');
                }
                ?>
            </div>
            <main id="content" class="content">
                <article>
                    <?php
                    // Der Seiten-Header wird hier dynamisch eingefügt, wenn er übergeben wurde.
                    if (!empty($pageHeader) && $isAdminPage) {
                        echo '<header><h1 class="page-header">' . htmlspecialchars($pageHeader) . '</h1></header>';
                    }
                    ?>