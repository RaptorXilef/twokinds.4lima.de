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
 * @version   5.0.0
 * @since     2.6.0 Diese Datei wurde verschlankt und konzentriert sich auf die HTML-Struktur und das Asset-Management.
 * Die sicherheitsrelevanten Initialisierungen wurden in separate `init.php`-Dateien ausgelagert.
 * @since     2.7.0 Entfernung redundanter URL-Berechnung und Ersetzung von Pfaden durch globale Konstanten.
 * @since     2.7.1 Korrektur der JS-Asset-Pfade auf Basis der finalen config_folder_path.php.
 * @since     2.7.2 Finale Validierung gegen die neueste Pfad-Konfiguration.
 * @since     3.0.0 Umstellung auf das finale, granulare Konstanten-System (DIRECTORY_..., ..._URL, DIRECTORY_PUBLIC_URL).
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     4.0.1 Aktualisiere den Pfad zur Sitemap-XML im Header
 * @since     4.0.2 Füge Font aus main.css direkt hinzu fonts.googleapis.com/css?family=Open+Sans:400,400i,700
 * @since     4.1.0 (CSS-Refactoring) Konsolidierung aller separaten Stylesheets (main, main_dark, cookie_banner, etc.) in eine einzige 'main.min.css'.
 * @since     5.0.0-alpha.1 refactor(HTML): IDs auf Kebab-Case umgestellt für SCSS-Kompatibilität.
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
 *
 * @note Die Variablen ($pageTitle, $nonce, $debugMode etc.) werden erwartet von
 * einer übergeordneten `init.php`-Datei (z.B. `init_public.php` oder `init_admin.php`) bereitgestellt zu werden.
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
$pageTitlePrefix = 'Twokinds – Deutsch | '; // $pageTitlePrefix = 'Twokinds – Das Webcomic auf Deutsch | ';
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
// Nur noch main.min.css laden
$mainCssUrl = getVersionedUrl(Url::getCssUrl('main.min.css'), DIRECTORY_PUBLIC_CSS . DIRECTORY_SEPARATOR . 'main.min.css');

$commonJsUrl = getVersionedUrl(Url::getJsUrl('common.min.js'), DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'common.min.js');
$cookieConsentJsUrl = getVersionedUrl(Url::getJsUrl('cookie_consent.min.js'), DIRECTORY_PUBLIC_JS . DIRECTORY_SEPARATOR . 'cookie_consent.min.js');

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
    <?php
    // --- 1. Bild-Logik vorbereiten ---
    // Wenn kein spezifisches Bild da ist, nimm den globalen Standard
    $finalSocialImage = !empty($ogImage) ? $ogImage : (defined('DEFAULT_SOCIAL_IMAGE') ? DEFAULT_SOCIAL_IMAGE : '');

    // --- 2. Debug-Check (nur im Quelltext sichtbar) ---
    if ($debugMode && !empty($finalSocialImage)) {
        // Sicherstellen, dass wir den lokalen Pfad korrekt berechnen
        $relativePart = str_replace(DIRECTORY_PUBLIC_URL, '', $finalSocialImage);
        $localPath = DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($relativePart, '/\\');

        echo "\n    ";
        if (!file_exists($localPath)) {
            echo "\n    ";
        } else {
            echo "\n    ";
        }
    }
    ?>

    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($siteDescription); ?>" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($siteDescription); ?>">

    <?php if (!empty($finalSocialImage)) : ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($finalSocialImage); ?>" />
        <meta name="twitter:image" content="<?php echo htmlspecialchars($finalSocialImage); ?>">
    <?php endif; ?>


    <!-- Weitere Meta-Informationen -->
    <link rel="sitemap" type="application/xml" title="Sitemap"
        href="<?php echo htmlspecialchars(DIRECTORY_PUBLIC_URL . 'sitemap.xml'); ?>">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />

    <!-- Google Fonts (Open Sans) -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400&display=swap"
        rel="stylesheet">

    <!-- Stylesheets -->
    <!--
        *** START ÄNDERUNG (CSS Refactoring) ***
        Alle Stile (main, main_dark, cookie_banner, character_display etc.)
        sind jetzt in main.min.css gebündelt (7-1 SCSS Refactoring).
    -->
    <link nonce="<?php echo htmlspecialchars($nonce); ?>" rel="stylesheet" type="text/css"
        href="<?php echo htmlspecialchars($mainCssUrl); ?>" fetchpriority="high">
    <!-- *** ENDE ÄNDERUNG *** -->

    <?php if ($isAdminPage) : ?>
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
        type='text/javascript'>
        window.isAdminPage = <?php echo ($isAdminPage ? 'true' : 'false'); ?>;
    </script>

    <?php
    echo $additionalScripts;
    echo $additionalHeadContent;
    ?>
</head>

<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <!-- Cookie-Consent-Banner -->
    <div id="cookie-consent-banner">
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
            <button id="accept-all-cookies">Alle akzeptieren</button>
            <button id="reject-all-cookies">Alle ablehnen</button>
            <button id="save-cookie-preferences">Auswahl speichern</button>
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
                        require_once Path::getConfigPath('config_menue_admin.php');
                        require_once Path::getAdminPartialTemplatePath('session_timeout_modal.php');
                    } else {
                        require_once Path::getConfigPath('config_menue_admin_login.php');
                    }
                } else {
                    require_once Path::getConfigPath('config_menue_public.php');
                }
                ?>
            </div>
            <main id="content" class="content">
                <?php
                $dynamicClass = '';
                if (isset($isComicPage) && $isComicPage) {
                    $dynamicClass = ' class="comic"'; // Fügt das Attribut komplett hinzu
                } elseif (isset($isCharakterPage) && $isCharakterPage) {
                    $dynamicClass = ' class="charaktere-overview"'; // Fügt das Attribut komplett hinzu
                }
                ?>

                <article<?php echo $dynamicClass; ?>>

                    <?php
                    // Der Seiten-Header wird hier dynamisch eingefügt, wenn er übergeben wurde.
                    if (!empty($pageHeader) && $isAdminPage) {
                        echo '<header><h1 class="page-header">' . htmlspecialchars($pageHeader) . '</h1></header>';
                    }
                    ?>

<?php
// --- UNIVERSALER SOCIAL MEDIA DEBUGGER (v6.4.0) ---
if ($debugMode) {
    echo "<div class='debug-box' style='background: #1a1a1a; color: #00ff00; padding: 15px; border: 2px dashed #00ff00; margin: 20px 0; font-family: monospace; font-size: 13px; line-height: 1.6; border-radius: 8px;'>";
    echo "<strong style='color: #fff; border-bottom: 1px solid #555; display: block; margin-bottom: 10px;'>🔍 Social Media Image Debug</strong>";

    $displayImage = null;
    $statusType = 'none'; // 'specific', 'fallback', 'none'

    // 1. Prüfung: Spezifisches Bild ($ogImage)
    if (!empty($ogImage)) {
        $cleanOg = explode('?', $ogImage)[0];
        $relOg = str_replace(DIRECTORY_PUBLIC_URL, '', $cleanOg);
        $pathOg = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($relOg, '/\\'));

        echo "<strong>Public URL (Spezifisch):</strong> " . htmlspecialchars($ogImage) . "<br>";

        if (file_exists($pathOg)) {
            echo "<span style='color: #00ff00;'>✔ SPEZIFISCHES BILD GEFUNDEN.</span><br>";
            $displayImage = $ogImage;
            $statusType = 'specific';
        } else {
            echo "<span style='color: #ff3333;'>✘ SPEZIFISCHES BILD FEHLT AUF SERVER.</span><br>";
        }
    } else {
        echo "<strong>Public URL:</strong> <span style='color: #aaa;'>--- LEER ---</span><br>";
    }

    // 2. Prüfung: Fallback (nur wenn spezifisches Bild fehlt oder leer ist)
    if ($statusType === 'none') {
        echo "<hr style='border: 0; border-top: 1px solid #333; margin: 10px 0;'>";
        if (defined('DEFAULT_SOCIAL_IMAGE') && !empty(DEFAULT_SOCIAL_IMAGE)) {
            $cleanFb = explode('?', DEFAULT_SOCIAL_IMAGE)[0];
            $relFb = str_replace(DIRECTORY_PUBLIC_URL, '', $cleanFb);
            $pathFb = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, DIRECTORY_PUBLIC . DIRECTORY_SEPARATOR . ltrim($relFb, '/\\'));

            echo "<strong>Nutze Fallback aus DEFAULT_SOCIAL_IMAGE:</strong><br>";
            echo htmlspecialchars(DEFAULT_SOCIAL_IMAGE) . "<br>";

            if (file_exists($pathFb)) {
                echo "<span style='color: #00ff00;'>✔ FALLBACK-BILD GEFUNDEN.</span><br>";
                $displayImage = DEFAULT_SOCIAL_IMAGE;
                $statusType = 'fallback';
            } else {
                echo "<span style='color: #ff3333;'>✘ AUCH FALLBACK-BILD FEHLT AUF SERVER.</span><br>";
            }
        } else {
            echo "<span style='color: #ff3333;'>✘ KEIN FALLBACK DEFINIERT (DEFAULT_SOCIAL_IMAGE).</span><br>";
        }
    }

    // 3. Visuelle Einbindung & Abschluss-Meldung
    echo "<div style='margin-top: 15px; padding-top: 10px; border-top: 1px solid #333;'>";
    if ($displayImage) {
        echo "<strong style='color: #fff;'>Vorschau (Crawler-Ansicht):</strong><br>";
        echo "<img src='" . htmlspecialchars($displayImage) . "' style='max-width: 100%; border: 1px solid #555; margin-top: 10px; background: #000;'>";
    } else {
        echo "<span style='color: #ff3333; font-weight: bold; font-size: 14px;'>CRITICAL: KEIN BILD VERFÜGBAR!</span><br>";
        echo "<small style='color: #aaa;'>Crawler werden kein Vorschaubild anzeigen.</small>";
    }
    echo "</div>";

    echo "</div>";
}
?>
