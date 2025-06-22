<?php
/**
 * Gemeinsamer Header für alle Seiten.
 * Enthält die grundlegende HTML-Struktur, Meta-Tags, Stylesheets und Skripte.
 *
 * @param string $pageTitle Der spezifische Titel für die aktuelle Seite.
 * @param string $pageHeader Der sichtbare H1-Header für die aktuelle Seite.
 * @param string $bodyClass Eine optionale Klasse für das Body-Tag (z.B. 'preload').
 * @param string $additionalScripts Optionaler HTML-Code für zusätzliche Skripte im Head.
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
$pageHeader = isset($pageHeader) ? $pageHeader : ''; // Standardmäßig leer, da viele Seiten einen eigenen Header haben
$bodyClass = isset($bodyClass) ? $bodyClass : 'preload';
$additionalScripts = isset($additionalScripts) ? $additionalScripts : '';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <title>
        <?php echo $pageTitle . ' - ' . ucfirst($filenameWithoutExtension); ?>
    </title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">

    <meta name="description" content="TwoKinds in deutsch! Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf.">
    <meta name="keywords" content="TwoKinds, in, auf, deutsch, übersetzt, uebersetzt, Web, Comic, Tom, Fischbach, RaptorXilef, Felix, Maywald, Reni, Nora, Trace, Flora, Keith, Natani, Zen, Sythe, Nibbly, Raine, Laura, Saria, Eric, Kathrin, Mike, Evals, Madelyn, Maren, Karen, Red, Templer, Keidran, Basitin, Mensch">
    <meta name="author" content="Felix Maywald, Design und Rechte by Thomas J. Fischbach & Brandon J. Dusseau">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="last-modified" content="<?php echo date('Y-m-d H:i:s', filemtime(__FILE__)); ?>">

    <?php
    // Pfad zur Sitemap-Datei (kann bei Bedarf angepasst werden)
    $sitemapURL = 'https://twokinds.4lima.de/sitemap.xml';
    ?>
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo $sitemapURL; ?>">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />

    <!-- Standard-Stylesheets -->
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20201116">
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20201116">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">

    <!-- Standard-Skripte -->
    <script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/common.js?c=20201116'></script>
    <?php echo $additionalScripts; // Hier können zusätzliche Skripte eingefügt werden ?>
</head>
<body class="<?php echo $bodyClass; ?>">
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
                        // Der Pfad wurde an die neue Struktur angepasst.
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
                        echo '    <h1 class="page-header">' . $pageHeader . '</h1>';
                        echo '</header>';
                    }
                    ?>