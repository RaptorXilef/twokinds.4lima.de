<?php
/*ini_set('display_errors', 1);
error_reporting(E_ALL);*/

set_time_limit(300);
/*require('admin/includes/error.php');*/

$filenameWithExtension = basename($_SERVER['PHP_SELF']); // Den Dateinamen der aktuellen PHP-Datei auslesen
$filenameWithoutExtension = pathinfo($filenameWithExtension, PATHINFO_FILENAME); // Den Dateinamen ohne Erweiterung extrahieren
// echo $filenameWithoutExtension;


echo '
<!DOCTYPE html>
<html lang="de">
<head>
	<title>
		Twokinds - In deutsch - ' . $filenameWithoutExtension . '
	</title>
'; ?>
	<meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    
	<meta name="description" content="TwoKinds in deutsch! Ein Webcomic über einen ahnungslosen Helden, eine schelmische Tigerin, einen ängstlichen Krieger und einen geschlechtsverwirrten Wolf.">
	<meta name="keywords" content="TwoKinds, in, auf, deutsch, übersetzt, uebersetzt, Web, Comic, Tom, Fischbach, RaptorXilef, Felix, Maywald, Reni, Nora, Trace, Flora, Keith, Natani, Zen, Sythe, Nibbly, Raine, Laura, Saria, Eric, Kathrin, Mike, Evals, Madelyn, Maren, Karen, Red, Templer, Keidran, Basitin, Mensch">
    <meta name="author" content="Felix Maywald, Design und Rechte by Thomas J. Fischbach & Brandon J. Dusseau">
    <meta name="viewport" content="width=1099, initial-scale=1.0">
    <meta name="last-modified" content="<?php echo date('Y-m-d H:i:s', filemtime(__FILE__)); ?>">
    <?php $sitemapURL = 'https://twokinds.4lima.de/sitemap.xml'; // Geben Sie den vollständigen Pfad zur sitemap.xml auf Ihrer Webseite an  ############################### ?>
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo $sitemapURL; ?>">
    
    <!--<<meta name="viewport" content="width=1099">-->
	<link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20201116">
	<link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
	<link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
	<link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">
	<link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20201116">
	<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/common.js?c=20201116'></script>
	<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20201116'></script>
    
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7VE3ZEWZQ7"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-7VE3ZEWZQ7'); // Hier kommt deine Mess-ID hin
    </script>
</head>
<body class="preload">
	<div id="mainContainer" class="main-container">
        <center>Dieses Fanprojekt ist die deutsche Übersetzung von <a href="https://twokinds.keenspot.com/" target="_blank">twokinds.keenspot.com</a></br>Die Webseite ist fast fertig! Nur die Comicseiten-Namen fehlen noch teilweise.</br>Die aktuellen Comicseiten werden vorraussichtlich Ende kommender Woche übersetzt!</center>
		<div id="banner-lights-off" class="banner-lights-off"></div>
		<div id="banner" class="banner">Twokinds</div>
		<div id="content-area" class="content-area">
			<div id="sidebar" class="sidebar">
                <div class="sidebar-content">
                    <!--<div class="social">
                        <a href="https://www.patreon.com/twokinds" class="social-link patreon" target="_blank">Patreon</a>
                        <a href="https://www.twitter.com/twokinds" class="social-link twitter" target="_blank">Twitter</a>
                        <a href="https://www.facebook.com/twokinds" class="social-link facebook" target="_blank">Facebook</a>
                    </div>-->
                    <!--Menü-->
                    <nav id="menu" class="menu">
                    </br>
                        <?php require('includes/design/menue_config.php'); ?></br>
                        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT AUS</span></a>
                        </br></br>
                        <a href="https://twokinds.keenspot.com/" target="_blank">Zum Original </br>auf Englisch</a>
                        
                        
                        </br></br></br></br>
                        <b style="color: #1E90FF; font-size: 14px;"><u>Benachrichtigung</u></b>
                        <p style="color: #00BFFF; font-size: 11px;">Hier kannst du deine E-Mail-Adresse hinterlassen, um eine Benachrichtigung zu erhalten, sobald eine neue Seite hochgeladen wurde. **</p>

                        <form method="POST" action="e-mail/handler.php">
                          <label for="email" style="color: #1DD800; font-size: 12px;">Benachrichtigung:</label>
                          <input type="email" id="email" name="email" required placeholder="E-Mail-Adresse" style="width: calc(100% - 10px);">

                          <button type="submit" name="action" value="hinzufuegen">Abonnieren</button>
                          <button type="submit" name="action" value="loeschen">Abbestellen</button>
                        </form>


                        </br><p style="color: #1E90FF; font-size: 8px;">** Die E-Mail-Adresse wird ausschließlich zum Senden der Benachrichtigung beim hochgeladen neuer übersetzter Comicseiten genutzt. Sie wird weder an Dritte weitergegeben noch anderweitig verwendet oder verarbeitet.</p>


                    </nav>
                    <!--Menü Ende-->
                </div>
            </div>        
            <main id="content" class="content">
                <article class="comic">
                    <header>
                        <h1> 