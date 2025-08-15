<?php
/**
 * Dieses Skript enthält die Konfiguration für das Navigationsmenü im Admin-Bereich.
 * Es wird dynamisch in src/layout/header.php geladen, wenn sich der Benutzer im Admin-Bereich befindet.
 */
?>


<div class="sidebar-content">
    <?php /*
      *    <div class="social">
      *        <!-- Soziale Medien Icons -->
      *        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 10px;">
      *            <!-- Patreon Icon -->
      *            <a href="https://www.patreon.com/raptorxilef" target="_blank" title="Mein Patreon">
      *                <img src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/patreon.png" alt="Patreon" width="32"
      *                    height="32" style="border-radius: 5px;">
      *            </a>
      *            <!-- InkBunny Icon -->
      *            <a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" title="Mein InkBunny">
      *                <img src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/inkbunny.png" alt="InkBunny" width="32"
      *                    height="32" style="border-radius: 5px;">
      *            </a>
      *            <!-- FurAffinity Icon -->
      *            <a href="https://paypal.me/RaptorXilef?country.x=DE&locale.x=de_DE" target="_blank" title="Mein Paypal">
      *                <img src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/paypal.png" alt="PayPal" width="32"
      *                    height="32" style="border-radius: 5px;">
      *            </a>
      *        </div>
      *    </div>
      */ ?>

    <!-- Menü-Navigation -->
    <nav id="menu" class="menu">
        <a href="./index.php">Dashboard</a>
        <a href="./index.php#manage-users">Benutzer verwalten</a>
        </br>
        <a href="./initial_setup.php">Webseiten-Ersteinrichtung</a>
        </br>
        <a href="./upload_image.php">Bild-Upload</a>
        </br>
        <a href="./generator_thumbnail.php">Thumbnail-Generator</a>
        <a href="./generator_image_socialmedia.php">Social Media Vorschau-Generator</a>
        <!--<a href="./generator_image_socialmedia.php">Social Media Bild-Generator</a>-->
        </br>
        <a href="./data_editor_comic.php">Comic Daten Editor</a>
        <a href="./generator_comic.php">Comic-Seiten-Generator</a>
        </br>
        <a href="./generator_rss.php">RSS-Generator</a>
        </br>
        <a href="./data_editor_archiv.php">Archiv Daten Editor</a>
        </br>
        <a href="./data_editor_sitemap.php">Sitemap Editor</a>
        <a href="./generator_sitemap.php">Sitemap Generator</a>
        </br>
        <a href="?action=logout">Logout</a>
        <br>
        <br>
        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span
                class="themename">LICHT
                AUS</span></a>
    </nav>
    <!-- Menü Ende -->
</div>