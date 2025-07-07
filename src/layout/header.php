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

// Setzt das maximale Ausführungszeitlimit für das Skript, um Timeouts bei größeren Operationen zu vermeiden.
set_time_limit(300);

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
    // Pfad zur Sitemap-Datei.
    $sitemapURL = 'https://twokinds.4lima.de/sitemap.xml';
    ?>
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo htmlspecialchars($sitemapURL); ?>">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />
    
    <!-- Robots Meta Tag für Indexierungssteuerung -->
    <meta name="robots" content="<?php echo htmlspecialchars($robotsContent); ?>">

    <!-- Standard-Stylesheets für das Hauptdesign. -->
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20250524">
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20250524">

    <!-- Favicons für verschiedene Browser und Geräte. -->
    <link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">

    <!-- Standard-JavaScript-Dateien. -->
    <script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/common.js?c=20201116'></script>
    <?php
    // Hier können zusätzliche Skripte eingefügt werden, die spezifisch für die aufrufende Seite sind.
    echo $additionalScripts;
    // Hier können zusätzliche Meta-Tags, Links etc. eingefügt werden, die spezifisch für die aufrufende Seite sind.
    echo $additionalHeadContent;
    ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <div id="mainContainer" class="main-container">
        <!-- Hinweis auf das Fanprojekt und Link zum Original. -->
        <center>Dieses Fanprojekt ist die deutsche Übersetzung von <a href="https://twokinds.keenspot.com/" target="_blank">twokinds.keenspot.com</a></center>
        <div id="banner-lights-off" class="banner-lights-off"></div>
        <!-- Hauptbanner der Webseite. -->
        <div id="banner" class="banner">Twokinds</div>
        <div id="content-area" class="content-area">
            <div id="sidebar" class="sidebar">
                <div class="sidebar-content">
                    <!-- Menü-Navigation -->
                    <nav id="menu" class="menu">
                        <br>
                        <?php
                        // Debugging paths - UNCOMMENT THESE LINES TO DEBUG PATHS IF YOU ENCOUNTER AN ERROR
                        // $adminMenuPath = __DIR__ . '/../components/admin_menue_config.php'; // Korrigierter Pfad
                        // $publicMenuPath = __DIR__ . '/../components/menue_config.php';
                        // echo "<!-- Debug: Is Admin Page? " . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'Yes' : 'No') . " -->\n";
                        // echo "<!-- Debug: Admin Menu Path: " . htmlspecialchars($adminMenuPath) . " -->\n";
                        // echo "<!-- Debug: Public Menu Path: " . htmlspecialchars($publicMenuPath) . " -->\n";
                        // echo "<!-- Debug: Current PHP_SELF: " . htmlspecialchars($_SERVER['PHP_SELF']) . " -->\n";
                        // echo "<!-- Debug: Admin menu file_exists: " . (file_exists($adminMenuPath) ? 'Yes' : 'No') . " -->\n";
                        // echo "<!-- Debug: Public menu file_exists: " . (file_exists($publicMenuPath) ? 'Yes' : 'No') . " -->\n";

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
                        <br>
                        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT AUS</span></a>
                        <br><br>
                        <a href="https://twokinds.keenspot.com/" target="_blank">Zum Original <br>auf Englisch</a>
                    </nav>
                    <!-- Menü Ende -->
                </div>
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
