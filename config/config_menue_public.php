<?php

/**
 * Konfigurationsdatei für das Navigationsmenü.
 * Enthält Links zu den verschiedenen Bereichen der Webseite.
 *
 * @file      ROOT/config/config_menue_public.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     4.0.0 Umstellung auf die dynamische Path-Helfer-Klasse.
 * @since     5.0.0 refactor: Inline-Styles entfernt, Struktur für modernes CSS optimiert.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
$nonce = $nonce ?? '';
?>

<div class="sidebar-content">
    <div class="social-area">
        <div class="social-icons-container patreon-icon-wrapper">
            <a href="https://www.patreon.com/RaptorXilef" target="_blank" rel="noopener noreferrer" title="Patreon">
                <img class="patreon-light-icon social-icon" id="rssFeedLink" src="<?php echo Url::getImgUiUrl('patreon.png'); ?>" alt="Patreon" width="32" height="32">
                <img class="patreon-dark-icon social-icon" id="rssFeedLink" src="<?php echo Url::getImgUiUrl('patreon_dark.png'); ?>" alt="Patreon Dark" width="32" height="32">
            </a>
            <a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" title="Mein InkBunny" rel="noopener noreferrer">
                <img class="social-icon" src="<?php echo Url::getImgUiUrl('inkbunny.png'); ?>" alt="InkBunny" width="32" height="32">
            </a>
            <a href="https://paypal.me/RaptorXilef" target="_blank" title="Unterstützung via PayPal" rel="noopener noreferrer">
                <img class="social-icon" src="<?php echo Url::getImgUiUrl('paypal.png'); ?>" alt="PayPal" width="32" height="32">
            </a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/rss.xml" target="_blank" title="RSS-Feed URL kopieren" id="rssFeedLink" rel="noopener noreferrer">
                <img class="social-icon" src="<?php echo Url::getImgUiUrl('rss-feed.png'); ?>" alt="RSS" width="32" height="32">
                <span class="copy-feedback">URL Kopiert!</span>
            </a>
        </div>
    </div>

    <nav id="menu" class="menu">
        <div class="menu-group">
            <span class="menu-label">Comic & Archiv</span>
            <a href="<?php echo DIRECTORY_PUBLIC_COMIC_URL; ?>">Comic lesen</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/archiv<?php echo $dateiendungPHP; ?>">Archiv</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/lesezeichen<?php echo $dateiendungPHP; ?>" >Lesezeichen</a>
        </div>

        <div class="menu-group">
            <span class="menu-label">Informationen</span>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/ueber_den_comic<?php echo $dateiendungPHP; ?>">Über den Comic</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/charakter-vorstellung<?php echo $dateiendungPHP; ?>">Charakter-Vorstellung</a>
            <a href="<?php echo DIRECTORY_PUBLIC_CHARAKTERE_URL . '/'; ?>" class="menu-new">Charakter-Übersicht</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/faq<?php echo $dateiendungPHP; ?>">Häufige Fragen (FAQ)</a>
        </div>

        <div class="menu-group">
            <span class="menu-label">Rechtliches & RSS</span>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/rss_anleitung<?php echo $dateiendungPHP; ?>">RSS-Feed Info</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/lizenz<?php echo $dateiendungPHP; ?>">Lizenz</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/datenschutzerklaerung<?php echo $dateiendungPHP; ?>">Datenschutz</a>
            <a href="<?php echo DIRECTORY_PUBLIC_URL; ?>/impressum<?php echo $dateiendungPHP; ?>">Impressum</a>
        </div>

        <div class="menu-footer">
            <a id="toggle_lights" class="theme-toggle jsdep" href="#">
                <span class="themelabel">Design:</span>
                <span class="themename">LICHT AUS</span>
            </a>



            <div class="incentive">
                <div class="tinytext">Zum Original auf Englisch</div>
                <a href='https://twokinds.keenspot.com' target="_blank" rel="noopener noreferrer">

                    <img src='<?php echo Url::getImgUiUrl('tkbutton3.webp'); ?>' alt='Twokinds Original'>
                </a>
            </div>
        </div>
    </nav>
</div>

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
