<?php

/**
 * Konfigurationsdatei für das Navigationsmenü.
 * Enthält Links zu den verschiedenen Bereichen der Webseite.
 * CSP-konform gemacht durch Entfernen von Inline-Styles und Hinzufügen von Nonces.
 *
 * @file      ROOT/config/config_menue_public.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since   5.0.0 Refactor
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;
$nonce = $nonce ?? '';
$dateiendungPHP = $dateiendungPHP ?? '.php';
?>

<div class="sidebar-content">
    <div class="social">
        <div class="social-icons-container">
            <a href="https://www.patreon.com/RaptorXilef" target="_blank" rel="noopener noreferrer" class="patreon-icon-wrapper" title="Patreon">
                <img class="patreon-light-icon social-icon" src="<?= Url::getImgUiUrl('patreon.png'); ?>" alt="Patreon" width="32" height="32">
                <img class="patreon-dark-icon social-icon" src="<?= Url::getImgUiUrl('patreon_dark.png'); ?>" alt="Patreon Dark" width="32" height="32">
            </a>
            <a href="https://inkbunny.net/RaptorXilefSFW" target="_blank" title="Mein InkBunny">
                <img class="social-icon" src="<?= Url::getImgUiUrl('inkbunny.png'); ?>" alt="InkBunny" width="32" height="32">
            </a>
            <a href="https://paypal.me/RaptorXilef" target="_blank" title="Mein Paypal">
                <img class="social-icon" src="<?= Url::getImgUiUrl('paypal.png'); ?>" alt="PayPal" width="32" height="32">
            </a>
            <a href="<?= DIRECTORY_PUBLIC_URL; ?>/rss.xml" target="_blank" title="Mein RSS-Feed" id="rssFeedLink">
                <img class="social-icon" src="<?= Url::getImgUiUrl('rss-feed.png'); ?>" alt="RSS" width="32" height="32">
                <span class="copy-feedback">URL Kopiert!</span>
            </a>
        </div>
    </div>

    <nav id="menu" class="menu">
        <span class="menu-label">Comic & Archiv</span>
        <a href="<?= DIRECTORY_PUBLIC_COMIC_URL; ?>">Comic</a>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/archiv<?= $dateiendungPHP; ?>">Archiv</a>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/lesezeichen<?= $dateiendungPHP; ?>">Lesezeichen</a>

        <span class="menu-label">Welt & Charaktere</span>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/ueber_den_comic<?= $dateiendungPHP; ?>">Über den Comic</a>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/charakter-vorstellung<?= $dateiendungPHP; ?>">Charaktere</a>
        <a href="<?= DIRECTORY_PUBLIC_CHARAKTERE_URL . '/'; ?>" class="menu-new">Übersicht</a>

        <span class="menu-label">Informationen</span>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/faq<?= $dateiendungPHP; ?>">FAQ</a>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/rss_anleitung<?= $dateiendungPHP; ?>">RSS-Feed Info</a>

        <span class="menu-label">Rechtliches</span>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/lizenz<?= $dateiendungPHP; ?>">Lizenz</a>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/datenschutzerklaerung<?= $dateiendungPHP; ?>">Datenschutz</a>
        <a href="<?= DIRECTORY_PUBLIC_URL; ?>/impressum<?= $dateiendungPHP; ?>">Impressum</a>

        <a id="toggle_lights" class="theme jsdep" href="#">
            <span class="themelabel">Theme</span>
            <span class="themename">LICHT AUS</span>
        </a>

        <div class="incentive">
            <div class="tinytext">Zum Original auf Englisch</div>
            <a href="https://twokinds.keenspot.com" target="_blank" rel="noopener noreferrer">
                <img src="<?= Url::getImgUiUrl('tkbutton3.webp'); ?>" alt="Twokinds">
            </a>
        </div>
    </nav>
</div>

<script nonce="<?= $nonce; ?>">
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
