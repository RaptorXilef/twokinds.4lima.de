<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

set_time_limit(300);
/*require('includes/error.php');*/

$filenameWithExtension = basename($_SERVER['PHP_SELF']); // Den Dateinamen der aktuellen PHP-Datei auslesen
$filenameWithoutExtension = pathinfo($filenameWithExtension, PATHINFO_FILENAME); // Den Dateinamen ohne Erweiterung extrahieren
// echo $filenameWithoutExtension;

echo '
<!DOCTYPE html>
<html lang="de">
<head>
	<title>
		Twokinds - Adminbereich - ' . $filenameWithoutExtension . '
	</title>
'; ?>
    <meta name="robots" content="noindex">
	<meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="description" content="Adminbereich.">
	<meta name="viewport" content="width=1099">
	<link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20201116">
	<link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
	<link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
	<link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">
	<link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20201116">
	<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/common.js?c=20201116'></script>
	<script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20201116'></script>
    
    <style>
        .red-text {
            color: red;
        }
    </style>
</head>
<body class="preload">
	<div id="mainContainer" class="main-container">
    <center>Dieses Fanprojekt ist die deutsche &Uuml;bersetzung von <a href="https://twokinds.keenspot.com/" target="_blank">twokinds.keenspot.com</a></center>
		<div id="banner-lights-off" class="banner-lights-off"></div>
		<div id="banner" class="banner">Twokinds</div>
		<div id="content-area" class="content-area">
			<div id="sidebar" class="sidebar">
                <div class="sidebar-content">
                    <!--Menü-->
                    <nav id="menu" class="menu">
                        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT AUS</span></a>
                        <a href="../">Zum Comic</a>
                            <?php require('includes/design/config-menue.php') ?>
                    </nav>
                    <!--Menü Ende-->
                </div>
            </div>        
            <main id="content" class="content">
                <article class="comic">
                    <header>
                        <h1> 