<?php
/**
 * Konfigurationsdatei für das Navigationsmenü.
 * Enthält Links zu den verschiedenen Bereichen der Webseite.
 * CSP-konform gemacht durch Entfernen von Inline-Styles und Hinzufügen von Nonces.
 * 
 * @file      /src/components/menu_config.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.0.1
 * @since     1.0.0 Grundlegende Menüstruktur und Links.
 * @since     1.0.1 Link zu Charakter-Übersicht hinzugefügt.
 */

// Die Variable $baseUrl wird nun IMMER von header.php gesetzt.

?>

<div class="sidebar-content">
    <div class="social">
        <!-- Soziale Medien Icons -->
        <div class="social-icons-container patreon-icon-wrapper">
            <!-- Patreon Icon -->
            <a href="https://www.patreon.com/RaptorXilef" target="_blank" rel="noopener noreferrer">
                <!-- Bild für den hellen Modus -->
                <img class="patreon-light-icon social-icon"
                    src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/patreon.png" alt="Patreon" width="32"
                    height="32">
                <!-- Bild für den dunklen Modus -->
                <img class="patreon-dark-icon social-icon"
                    src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/patreon_dark.png" alt="Patreon Dark"
                    width="32" height="32">
            </a>
            <!-- InkBunny Icon -->
            <a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" title="Mein InkBunny">
                <img class="social-icon" src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/inkbunny.png"
                    alt="InkBunny" width="32" height="32">
            </a>
            <!-- PayPal Icon -->
            <a href="https://paypal.me/RaptorXilef?country.x=DE&locale.x=de_DE" target="_blank" title="Mein Paypal">
                <img class="social-icon" src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/paypal.png"
                    alt="PayPal" width="32" height="32">
            </a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>rss.xml" target="_blank" title="Mein RSS-Feed"
                id="rssFeedLink">
                <img class="social-icon" src="<?php echo htmlspecialchars($baseUrl); ?>assets/icons/rss-feed.png"
                    alt="RSS" width="32" height="32">
            </a>
        </div>
    </div>

    <!-- Menü-Navigation -->
    <nav id="menu" class="menu">
        <a href="<?php echo htmlspecialchars($baseUrl); ?>comic/">Comic</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>archiv.php">Archiv</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>lesezeichen.php">Lesezeichen</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>ueber_den_comic.php">Über den Comic</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>charaktere.php">Charaktere</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>charaktere">Charakter-übersicht</a>
        <br>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>faq.php">FAQ</a>
        <br>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>rss_anleitung.php">RSS-Feed Info</a>
        <br>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>lizenz.php">Lizenz</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>datenschutzerklaerung.php">Datenschutz</a>
        <a href="<?php echo htmlspecialchars($baseUrl); ?>impressum.php">Impressum</a>
        <br>
        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span
                class="themename">LICHT AUS</span></a>
        <br>
        <a href='https://twokinds.keenspot.com'>Zum Original<p>auf Englisch</p><img
                src='<?php echo htmlspecialchars($baseUrl); ?>assets/img/tkbutton3.webp' alt='Twokinds'></a>
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

<script nonce="<?php echo $nonce; ?>">
    // Warte, bis das DOM vollständig geladen ist
    document.addEventListener('DOMContentLoaded', function () {
        const rssFeedLink = document.getElementById('rssFeedLink');

        if (rssFeedLink) {
            rssFeedLink.addEventListener('click', function (event) {
                // Verhindere das Standardverhalten des Links (das Öffnen der URL)
                event.preventDefault();

                // Die URL, die kopiert werden soll, ist der href-Wert des umgebenden <a>-Tags
                const rssUrl = this.href; // 'this' bezieht sich hier auf das <a>-Element

                // Versuche, in die Zwischenablage zu kopieren
                navigator.clipboard.writeText(rssUrl)
                    .then(() => {
                        // Da alert() blockiert wird, nutzen wir eine Alternative
                        console.log('RSS-Feed URL erfolgreich kopiert: ' + rssUrl);
                    })
                    .catch(err => {
                        console.error('Fehler beim Kopieren der RSS-Feed URL: ', err);
                        // Fallback für ältere Browser oder Fehler
                        try {
                            const tempInput = document.createElement('textarea');
                            tempInput.value = rssUrl;
                            document.body.appendChild(tempInput);
                            tempInput.select();
                            document.execCommand('copy');
                            document.body.removeChild(tempInput);
                            console.log('RSS-Feed URL kopiert (Fallback): ' + rssUrl);
                        } catch (copyErr) {
                            console.error('Fallback-Kopieren fehlgeschlagen: ', copyErr);
                        }
                    });
            });
        }
    });
</script>

<style nonce="<?php echo $nonce; ?>">
    /* Neu hinzugefügte Klassen für CSP-Konformität */
    .social-icons-container {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .social-icon {
        border-radius: 5px;
    }

    #rssFeedLink .social-icon {
        cursor: pointer;
    }

    /* Standard-Stile für das Light-Theme */
    .patreon-icon-wrapper .patreon-light-icon {
        display: inline-block;
        /* Zeige das helle Icon standardmäßig an */
    }

    .patreon-icon-wrapper .patreon-dark-icon {
        display: none;
        /* Verstecke das dunkle Icon standardmäßig */
    }

    /* Stile für das Dark-Theme, wenn die Body-Klasse 'theme-night' aktiv ist */
    body.theme-night .patreon-icon-wrapper .patreon-light-icon {
        display: none;
        /* Verstecke das helle Icon im Dark-Theme */
    }

    body.theme-night .patreon-icon-wrapper .patreon-dark-icon {
        display: inline-block;
        /* Zeige das dunkle Icon im Dark-Theme an */
    }
</style>