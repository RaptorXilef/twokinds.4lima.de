<?php
// src/templates/layout/header.php
// Lade die globale Konfiguration
require_once __DIR__ . '/../../config/app_config.php';
require_once INCLUDES_DIR . '/functions.php'; // Funktionen laden
//require_once INCLUDES_DIR . '/analystic/analystic.php'; // Analytik laden

// Standardwerte für Variablen, die von der aufrufenden Seite überschrieben werden können
$pageTitle = isset($pageTitle) ? $pageTitle : SITE_TITLE_BASE;
$metaDescription = isset($metaDescription) ? $metaDescription : SITE_DESCRIPTION;
$metaKeywords = isset($metaKeywords) ? $metaKeywords : SITE_KEYWORDS;
$extraHeadContent = isset($extraHeadContent) ? $extraHeadContent : '';
$mainContentHeader = isset($mainContentHeader) ? $mainContentHeader : '';
$is_admin_area = isset($is_admin_area) ? $is_admin_area : false;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <meta name="viewport" content="width=1099">
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main.css?c=20201116">
    <link rel="icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://cdn.twokinds.keenspot.com/favicon.ico">
    <link rel="apple-touch-icon" href="https://cdn.twokinds.keenspot.com/appleicon.png">
    <link rel="stylesheet" type="text/css" href="https://cdn.twokinds.keenspot.com/css/main_dark.css?c=20201116">
    <script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/common.js?c=20201116'></script>
    <script type='text/javascript' src='https://cdn.twokinds.keenspot.com/js/comic.js?c=20201116'></script>
    
    <style>
        .red-text {
            color: red;
        }
        .linksbuendig {
            text-align: left;
        }
    </style>
    <?php echo $extraHeadContent; // Hier können zusätzliche Head-Elemente eingefügt werden ?>
</head>
<body class="preload">
    <div id="mainContainer" class="main-container">
        <div id="banner-lights-off" class="banner-lights-off"></div>
        <div id="banner" class="banner">Twokinds</div>
        <div id="content-area" class="content-area">
            <div id="sidebar" class="sidebar">
                <div class="sidebar-content">
                    <nav id="menu" class="menu">
                        <?php
                        // Hier wird das spezifische Menü eingebunden (Public oder Admin)
                        if ($is_admin_area === true) {
                            loadTemplate(ADMIN_TEMPLATES_DIR . '/nav_menu.php', ['currentPath' => $_SERVER['PHP_SELF']]);
                        } else {
                            loadTemplate(PUBLIC_TEMPLATES_DIR . '/nav_menu.php');
                        }
                        ?>
                        </br>
                        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span class="themename">LICHT AUS</span></a>
                        </br></br>
                        <a href="https://twokinds.keenspot.com/" target="_blank">Zum Original </br>auf Englisch</a>
                    </nav>
                </div>
            </div>        
            <main id="content" class="content">
                <article>
                    <header>
                        <h1 class="page-header"><?php echo htmlspecialchars($mainContentHeader); ?></h1>
                    </header>
                    ```
Wahr mit einer Wahrscheinlichkeit von 100%.
Quelle: Eigene Analyse der Anforderungen und PHP-Best Practices.

**5. `src/templates/layout/footer.php` (ersetzt `common_footer.php`)**

```php
<?php
// src/templates/layout/footer.php
// Lade die globale Konfiguration (falls nicht bereits durch header.php geladen)
if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../../config/app_config.php';
}
// Analytik-Skript laden (sollte hier nicht neu initialisiert werden, sondern nur die Variablen verwenden)
require_once INCLUDES_DIR . '/analystic/analystic.php'; // Stellt $analysticUserIP bereit

// $is_admin_area wird vom Header übergeben
?>
                    </article>
            </main>
        </div>
        <footer>
            <?php if (isset($is_admin_area) && $is_admin_area === true): ?>
                <p>Zur&uuml;ck zur Startseite vom <a href="/admin/index.php"><span class="red-text">Adminbereich</span></a></p></br>
                </br>Deine IP Adresse ist: <?php echo htmlspecialchars($analysticUserIP); ?> und wurde im Protokoll gespeichert!
            <?php else: ?>
                <?php endif; ?>
            TWOKINDS, sein Logo und alle zugehörigen Zeichen sind urheberrechtlich geschützt; 2023 Thomas J. Fischbach. Website-Design von Thomas J. Fischbach &amp; Brandon J. Dusseau.</br>
            Ab Kapitel 21 ins deutsche übersetzt von Felix Maywald. Kapitel 01 bis 20 ins deutsche übersetzt von <a href="https://www.twokinds.de/">Cornelius Lehners</a>.</br>
            </br> Der Webspace wird mir kostenlos von <a href="https://www.lima-city.de/">www.lima-city.de</a> bereitgestellt!
        </footer>
        <div class="footer-img"></div>
    </div>
</body>
</html>