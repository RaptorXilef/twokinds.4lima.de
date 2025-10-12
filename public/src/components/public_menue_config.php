<?php
/**
 * Konfigurationsdatei für das Navigationsmenü.
 * Enthält Links zu den verschiedenen Bereichen der Webseite.
 * CSP-konform gemacht durch Entfernen von Inline-Styles und Hinzufügen von Nonces.
 * 
 * @file      ROOT/public/src/components/menu_config.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     1.0.0 Grundlegende Menüstruktur und Links.
 * @since     1.0.1 Link zu Charakter-Übersicht hinzugefügt.
 * @since     1.0.2 Visuelles Feedback beim Kopieren der RSS-URL hinzugefügt.
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse und DIRECTORY_PUBLIC_URL.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

?>

<div class="sidebar-content">
    <div class="social">
        <!-- Soziale Medien Icons -->
        <div class="social-icons-container patreon-icon-wrapper">
            <!-- Patreon Icon -->
            <a href="https://www.patreon.com/RaptorXilef" target="_blank" rel="noopener noreferrer">
                <!-- Bild für den hellen Modus -->
                <img class="patreon-light-icon social-icon" src="<?php echo Path::getIcon('patreon.png'); ?>"
                    alt="Patreon" width="32" height="32">
                <!-- Bild für den dunklen Modus -->
                <img class="patreon-dark-icon social-icon" src="<?php echo Path::getIcon('patreon_dark.png'); ?>"
                    alt="Patreon Dark" width="32" height="32">
            </a>
            <!-- InkBunny Icon -->
            <a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" title="Mein InkBunny">
                <img class="social-icon" src="<?php echo Path::getIcon('inkbunny.png'); ?>" alt="InkBunny" width="32"
                    height="32">
            </a>
            <!-- PayPal Icon -->
            <a href="https://paypal.me/RaptorXilef?country.x=DE&locale.x=de_DE" target="_blank" title="Mein Paypal">
                <img class="social-icon" src="<?php echo Path::getIcon('paypal.png'); ?>" alt="PayPal" width="32"
                    height="32">
            </a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/rss.xml" target="_blank" title="Mein RSS-Feed"
                id="rssFeedLink">
                <img class="social-icon" src="<?php echo Path::getIcon('rss-feed.png'); ?>" alt="RSS" width="32"
                    height="32">
                <span class="copy-feedback">URL Kopiert!</span>
            </a>
        </div>
    </div>

    <!-- Menü-Navigation -->
    <nav id="menu" class="menu">
        <a href="<?php echo DIRECTORY_PUBLIC_COMIC_URL; ?>">Comic</a>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/archiv<?php echo $dateiendungPHP; ?>">Archiv</a>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/lesezeichen<?php echo $dateiendungPHP; ?>">Lesezeichen</a>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/ueber_den_comic<?php echo $dateiendungPHP; ?>">Über den
            Comic</a>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/charaktere.php">Charaktere</a>
        <a href="<?php echo DIRECTORY_PUBLIC_CHARAKTERE_URL; ?>">Charakter-Übersicht</a>
        <br>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/faq<?php echo $dateiendungPHP; ?>">FAQ</a>
        <br>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/rss_anleitung<?php echo $dateiendungPHP; ?>">RSS-Feed Info</a>
        <br>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/lizenz<?php echo $dateiendungPHP; ?>">Lizenz</a>
        <a
            href="<?php echo DIRECTORY_PUBLIC_URL; ?>/datenschutzerklaerung<?php echo $dateiendungPHP; ?>">Datenschutz</a>
        <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/impressum<?php echo $dateiendungPHP; ?>">Impressum</a>
        <br>
        <a id="toggle_lights" class="theme jsdep" href=""><span class="themelabel">Theme</span><span
                class="themename">LICHT AUS</span></a>
        <br>
        <a href='https://twokinds.keenspot.com'>Zum Original<p>auf Englisch</p><img
                src='<?php echo Path::getImg('tkbutton3.webp'); ?>' alt='Twokinds'></a>
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

                const rssUrl = this.href;
                const feedbackElement = this.querySelector('.copy-feedback');

                navigator.clipboard.writeText(rssUrl)
                    .then(() => {
                        if (feedbackElement) {
                            feedbackElement.classList.add('show');
                            setTimeout(() => {
                                feedbackElement.classList.remove('show');
                            }, 2000);
                        }
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
                            if (feedbackElement) {
                                feedbackElement.textContent = 'Kopiert! (Fallback)';
                                feedbackElement.classList.add('show');
                                setTimeout(() => {
                                    feedbackElement.classList.remove('show');
                                    feedbackElement.textContent = 'Kopiert!';
                                }, 2000);
                            }
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

    #rssFeedLink {
        position: relative;
        /* Wichtig für die Positionierung der Feedback-Nachricht */
    }

    #rssFeedLink .social-icon {
        cursor: pointer;
    }

    /* Stile für die "Kopiert!"-Nachricht */
    .copy-feedback {
        position: absolute;
        bottom: 110%;
        left: 50%;
        transform: translateX(-50%);
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
        z-index: 10;
    }

    .copy-feedback.show {
        opacity: 1;
        visibility: visible;
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

    body.theme-night .copy-feedback {
        background-color: #3c763d;
    }
</style>