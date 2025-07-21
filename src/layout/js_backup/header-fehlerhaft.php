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

// Starte die PHP-Sitzung, falls noch keine aktiv ist.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Dynamische Basis-URL Bestimmung für die gesamte Anwendung ===
// Diese Logik ist nun zentral in header.php und wird für alle Seiten
// vor dem Laden anderer Komponenten ausgeführt, die $baseUrl benötigen.
if (!isset($baseUrl)) {
    // Bestimme das Protokoll (http oder https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    // Bestimme den Hostnamen
    $host = $_SERVER['HTTP_HOST'];
    // Bestimme den Pfad zum aktuellen Skript
    $scriptPath = $_SERVER['PHP_SELF'];
    // Ermittle das Verzeichnis des aktuellen Skripts relativ zum Document Root
    $path = dirname($scriptPath);
    // Wenn das Skript im Root-Verzeichnis liegt, ist der Pfad '/'
    if ($path === '/' || $path === '\\') { // '\\' für Windows-Systeme
        $baseUrl = $protocol . '://' . $host . '/';
    } else {
        // Sicherstellen, dass der Pfad mit einem Slash endet
        $baseUrl = $protocol . '://' . $host . rtrim($path, '/\\') . '/';
    }
}

// === Lade globale Einstellungen ===
// Diese Datei enthält den Schalter $useLocalAssets
require_once __DIR__ . '/../../config/settings.php';

// === Lade Asset-Pfade ===
// Diese Datei gibt ein Array zurück, das die Pfade zu CSS- und JS-Dateien enthält.
$assetPaths = require_once __DIR__ . '/../../config/asset_paths.php';

// Basis-Dateiname der aktuellen PHP-Datei ohne Erweiterung, wird für den Standard-Titel verwendet.
$filenameWithoutExtension = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);

// Standardpräfix für den Seitentitel. Dieser kann in einzelnen Seiten vor dem Include überschrieben werden,
// falls ein spezifischerer Titel gewünscht ist.
$pageTitlePrefix = 'TwoKinds auf Deutsch - ';

// Setze Standardwerte, falls nicht übergeben
if (!isset($pageTitle)) {
    $pageTitle = ucfirst($filenameWithoutExtension);
}
if (!isset($pageHeader)) {
    $pageHeader = ucfirst($filenameWithoutExtension);
}
if (!isset($bodyClass)) {
    $bodyClass = 'preload'; // Standardklasse für Body
}
if (!isset($additionalScripts)) {
    $additionalScripts = '';
}
if (!isset($additionalHeadContent)) {
    $additionalHeadContent = '';
}
if (!isset($viewportContent)) {
    $viewportContent = 'width=1099'; // Standard-Viewport für Desktop-optimierte Seiten
}
if (!isset($siteDescription)) {
    $siteDescription = 'TwoKinds ist ein Fantasy-Webcomic von Tom Fischbach, fanübersetzt auf Deutsch.';
}
if (!isset($robotsContent)) {
    $robotsContent = 'index, follow';
}

// Ermittle die Pfade für die Assets basierend auf $useLocalAssets
$cssMainPath = $useLocalAssets ? $assetPaths['css']['main']['local'] : $assetPaths['css']['main']['original'];
$cssMainDarkPath = $useLocalAssets ? $assetPaths['css']['main_dark']['local'] : $assetPaths['css']['main_dark']['original'];

// jQuery-Pfad und Attribute
$jsJqueryPath = $useLocalAssets ? $assetPaths['js']['jquery']['local'] : $assetPaths['js']['jquery']['original'];
$jqueryIntegrity = $useLocalAssets ? '' : ' integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="';
$jqueryCrossorigin = $useLocalAssets ? '' : ' crossorigin="anonymous"';

$jsCommonPath = $useLocalAssets ? $assetPaths['js']['common']['local'] : $assetPaths['js']['common']['original'];
$jsArchivePath = $useLocalAssets ? $assetPaths['js']['archive']['local'] : $assetPaths['js']['archive']['original'];
$jsComicPath = $useLocalAssets ? $assetPaths['js']['comic']['local'] : $assetPaths['js']['comic']['original'];

// Aktualisiere den $additionalScripts String, um die neuen dynamischen Pfade zu verwenden
// und stelle sicher, dass jQuery zuerst geladen wird.
$additionalScripts = '
    <script src="' . htmlspecialchars($jsJqueryPath) . '"' . $jqueryIntegrity . $jqueryCrossorigin . '></script>
    <script type="text/javascript" src="' . htmlspecialchars($jsCommonPath) . '"></script>
    <script type="text/javascript" src="' . htmlspecialchars($jsArchivePath) . '"></script>
    <script type="text/javascript" src="' . htmlspecialchars($jsComicPath) . '"></script>
' . $additionalScripts; // Füge eventuell vorhandene zusätzliche Skripte hinzu

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <title>
        <?php echo htmlspecialchars($pageTitlePrefix . $pageTitle); ?>
    </title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">

    <meta name="description" content="<?php echo htmlspecialchars($siteDescription); ?>">
    <meta name="keywords"
        content="TwoKinds, in, auf, deutsch, übersetzt, uebersetzt, Web, Comic, Tom, Fischbach, RaptorXilef, Felix, Maywald, Reni, Nora, Trace, Flora, Keith, Natani, Zen, Sythe, Nibbly, Raine, Laura, Saria, Eric, Kathrin, Mike, Evals, Madelyn, Maren, Karen, Red, Templer, Keidran, Basitin, Mensch">
    <meta name="author" content="Felix Maywald, Design und Rechte by Thomas J. Fischbach & Brandon J. Dusseau">
    <meta name="viewport" content="<?php echo htmlspecialchars($viewportContent); ?>">
    <meta name="last-modified" content="<?php echo date('Y-m-d H:i:s'); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robotsContent); ?>">

    <link rel="sitemap" type="application/xml" title="Sitemap"
        href="<?php echo htmlspecialchars($baseUrl); ?>sitemap.xml">
    <meta name="google-site-verification" content="61orCNrFH-sm-pPvwWMM8uEH8OAnJDeKtI9yzVL3ico" />

    <!-- Dynamisch geladene CSS-Dateien -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssMainPath); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssMainDarkPath); ?>">

    <!-- Favicons für verschiedene Browser und Geräte. -->
    <link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="https://cdn.twokinds.keenspot.com/appleicon.png">

    <?php echo $additionalScripts; // Zusätzliche Skripte ?>
    <?php echo $additionalHeadContent; // Zusätzlicher Head-Inhalt ?>
</head>

<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <div id="mainContainer" class="main-container">
        <!-- Hinweis auf das Fanprojekt und Link zum Original. -->
        <center>Dieses Fanprojekt ist die deutsche Übersetzung von <a href="https://twokinds.keenspot.com/"
                target="_blank">twokinds.keenspot.com</a></center>
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