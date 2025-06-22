<?php
/**
 * Gemeinsamer Header für alle Seiten.
 * Enthält die grundlegende HTML-Struktur, Meta-Tags, Stylesheets und Skripte.
 *
 * @param string $pageTitle Der spezifische Titel für die aktuelle Seite.
 * @param string $pageHeader Der sichtbare H1-Header für die aktuelle Seite.
 * @param string $bodyClass Eine optionale Klasse für das Body-Tag (z.B. 'preload').
 * @param string $additionalScripts Optionaler HTML-Code für zusätzliche Skripte im Head.
 * @param string $additionalHeadContent Optionaler HTML-Code für zusätzliche Meta-Tags oder Links im <head>.
 * @param string $viewportContent Der Inhalt des Viewport-Meta-Tags (Standard: "width=device-width, initial-scale=1.0").
 * @param string $siteDescription Die allgemeine Beschreibung der Webseite.
 * @param string $robotsContent Inhalt des robots-Meta-Tags (Standard: "index, follow").
 */

// Debug-Einstellungen (kann für den Produktivbetrieb auskommentiert werden)
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
*/

// Setzt das maximale Ausführungszeitlimit für das Skript
set_time_limit(300);

// Basis-Dateiname der aktuellen PHP-Datei ohne Erweiterung
$filenameWithoutExtension = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);

// Standardwerte für Parameter
$pageTitle = isset($pageTitle) ? $pageTitle : 'Twokinds - In deutsch';
$pageHeader = isset($pageHeader) ? $pageHeader : '';
$bodyClass = isset($bodyClass) ? $bodyClass : 'preload';
$additionalScripts = isset($additionalScripts) ? $additionalScripts : '';
$additionalHeadContent = isset($additionalHeadContent) ? $additionalHeadContent : '';
$viewportContent = isset($viewportContent) ? $viewportContent : 'width=device-width, initial-scale=1.0';
// Neue Variable für die Seitenbeschreibung, Standardwert auf Deutsch
// Dieser Wert kann in jeder aufrufenden Datei überschrieben werden, z.B. in 20250312.php
$siteDescription = isset($siteDescription) ? $siteDescription : 'Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf. Dies ist eine Fan-Übersetzung von TwoKinds auf Deutsch.';
// Neuer Parameter für den robots-Meta-Tag, standardmäßig auf "index, follow"
$robotsContent = isset($robotsContent) ? $robotsContent : 'index, follow';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <title>
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">

    <meta name="description" content="<?php echo htmlspecialchars($siteDescription); ?>">
    <meta name="keywords" content="TwoKinds, in, auf, deutsch, übersetzt, uebersetzt, Web, Comic, Tom, Fischbach, RaptorXilef, Felix, Maywald, Reni, Nora, Trace, Flora, Keith, Natani, Zen, Sythe, Nibbly, Raine, Laura, Saria, Eric, Kathrin, Mike, Evals, Madelyn, Maren, Karen, Red, Templer, Keidran, Basitin, Mensch">
    <meta name="author" content="Felix Maywald, Design und Rechte by Thomas J. Fischbach & Brandon J. Dusseau">
    <meta name="viewport" content="<?php echo htmlspecialchars($viewportContent); ?>">
    <meta name="last-modified" content="<?php echo date('Y-m-d H:i:s', filemtime(__FILE__)); ?>">

    <?php
    // Pfad zur Sitemap-Datei (kann bei Bedarf angepasst werden)
    $sitemapURL = 'https://twokinds.4lima.de/sitemap.xml';
    ?>
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo htmlspecialchars($sitemapURL); ?>">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />
    
    <!-- Robots Meta Tag für Indexierungssteuerung -->
    <meta name="robots" content="<?php echo htmlspecialchars($robotsContent); ?>">

    <!-- Standard-Stylesheets (Versionen an Original angepasst) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20250524">
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20250524">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">

    <!-- Standard-Skripte -->
    <script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/common.js?c=20201116'></script>
    <?php echo $additionalScripts; // Hier können zusätzliche Skripte eingefügt werden ?>
    <?php echo $additionalHeadContent; // Hier können zusätzliche Meta-Tags, Links etc. eingefügt werden ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <div id="mainContainer" class="main-container">
        <center>Dieses Fanprojekt ist die deutsche Übersetzung von <a href="https://twokinds.keenspot.com/" target="_blank">twokinds.keenspot.com</a></center>
        <div id="banner-lights-off" class="banner-lights-off"></div>
        <div id="banner" class="banner">Twokinds</div>
        <div id="content-area" class="content-area">
            <div id="sidebar" class="sidebar">
                <div class="sidebar-content">
                    <!-- Soziale Links (aktuell auskommentiert, falls nicht benötigt) -->
                    <!--
                    <div class="social">
                        <a href="https://www.patreon.com/twokinds" class="social-link patreon" target="_blank">Patreon</a>
                        <a href="https://www.twitter.com/twokinds" class="social-link twitter" target="_blank">Twitter</a>
                        <a href="https://www.facebook.com/twokinds" class="social-link facebook" target="_blank">Facebook</a>
                    </div>
                    -->
                    <!-- Menü-Navigation -->
                    <nav id="menu" class="menu">
                        </br>
                        <?php
                        // Das Menü-Konfigurationsskript einbinden
                        require(__DIR__ . '/../components/menue_config.php');
                        ?>
                        </br>
                        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT AUS</span></a>
                        </br></br>
                        <a href="https://twokinds.keenspot.com/" target="_blank">Zum Original </br>auf Englisch</a>
                    </nav>
                    <!-- Menü Ende -->
                </div>
            </div>
            <main id="content" class="content">
                <article>
                    <?php
                    // Der Seiten-Header wird hier dynamisch eingefügt, wenn er übergeben wurde
                    if (!empty($pageHeader)) {
                        echo '<header>';
                        echo '    <h1 class="page-header">' . htmlspecialchars($pageHeader) . '</h1>';
                        echo '</header>';
                    }
                    ?>
