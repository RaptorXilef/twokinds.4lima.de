<?php
/**
 * Konfigurationsdatei für das Navigationsmenü.
 * Enthält Links zu den verschiedenen Bereichen der Webseite.
 */

// Die Variable $baseUrl wird nun IMMER von header.php gesetzt.
// Der Fallback-Block ist nicht mehr notwendig.

?>
<div class="sidebar-content">
    <div class="social">
        <!-- Soziale Medien Icons -->
        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 10px;">
            <!-- Patreon Icon -->
            <a href="https://www.patreon.com/raptorxilef" target="_blank" title="Mein Patreon">
                <img src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/patreon.png" alt="Patreon" width="32"
                    height="32" style="border-radius: 5px;">
            </a>
            <!-- InkBunny Icon -->
            <a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" title="Mein InkBunny">
                <img src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/inkbunny.png" alt="InkBunny" width="32"
                    height="32" style="border-radius: 5px;">
            </a>
            <!-- FurAffinity Icon -->
            <a href="https://paypal.me/RaptorXilef?country.x=DE&locale.x=de_DE" target="_blank" title="Mein Paypal">
                <img src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/paypal.png" alt="PayPal" width="32"
                    height="32" style="border-radius: 5px;">
            </a>
        </div>
    </div>

    <!-- Menü-Navigation -->
    <nav id="menu" class="menu">
        <a href="<?php echo htmlspecialchars($baseUrl); ?>comic/">Comic</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>archiv.php">Archiv</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>lesezeichen.php">Lesezeichen</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>ueber_den_comic.php">Über den Comic</a>
        <a href="<?php htmlspecialchars($baseUrl); ?>charaktere.php">Charaktere</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>lizenz.php">Lizenz</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>impressum.php">Impressum</a>
        <br>
        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span
                class="themename">LICHT AUS</span></a>
        <br>
        <a href='https://twokinds.keenspot.com'>Zum Original<p>auf Englisch</p><img
                src='https://www.2kinds.com/images/tkbutton3.png' alt='Twokinds'></a>
    </nav>
    <!-- Menü Ende -->
</div>


<?php /* <a href="https://www.patreon.com/c/raptorxilex/posts?filters%5Btag%5D=TwoKinds&redirect=true" target="_blank"><img src="https://c5.patreon.com/external/favicon/rebrand/favicon.ico?v=af5597c2ef" alt="Patreon-Logo" width="15" style="float: left; margin-left: 15px; margin-right: -30px;"> Patreon <img src="https://c5.patreon.com/external/favicon/rebrand/favicon.ico?v=af5597c2ef" alt="Patreon-Logo" width="15" style="float: right; margin-left: -30px; margin-right: 15px;"></a>
<br>
<a href="<?php echo htmlspecialchars($baseUrl); ?>faq.php">Häufige Fragen</a>
<a href="<?php echo htmlspecialchars($baseUrl); ?>unterstuetzung.php"><img
src="https://www.paypalobjects.com/marketing/web/logos/paypal-mark-color_new.svg" alt="PayPal-Logo"
width="15"> Unterstützung <img src="https://upload.wikimedia.org/wikipedia/commons/9/94/Patreon_logo.svg"
alt="Patreon-Logo" width="15"></a></p> 
<a href="https://twokinds.keenspot.com/" target="_blank">Zum Original <br>auf Englisch</a>
*/ ?>