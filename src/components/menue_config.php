<?php
/**
 * Konfigurationsdatei für das Navigationsmenü.
 * Enthält Links zu den verschiedenen Bereichen der Webseite.
 */

// Stellen Sie sicher, dass $baseUrl hier verfügbar ist.
// Diese Variable sollte in einer übergeordneten Datei (z.B. header.php) gesetzt werden,
// die auch die comic_page_renderer.php inkludiert, wo $baseUrl definiert wird.
if (!isset($baseUrl)) {
    // Fallback oder Fehlerbehandlung, falls $baseUrl nicht definiert ist.
    // In einer gut strukturierten Anwendung sollte dies nicht notwendig sein,
    // da $baseUrl im header.php (via comic_page_renderer.php) definiert wird.
    error_log("FEHLER: \$baseUrl ist in menue_config.php nicht definiert.");
    $baseUrl = '/'; // Setze einen Fallback auf den Server-Root, dies könnte aber Fehler verursachen.
}
?>
<a href="https://www.patreon.com/c/raptorxilef/posts?filters%5Btag%5D=TwoKinds&redirect=true" target="_blank"><img src="https://c5.patreon.com/external/favicon/rebrand/favicon.ico?v=af5597c2ef" alt="Patreon-Logo" width="15" style="float: left; margin-left: 15px; margin-right: -30px;"> Patreon <img src="https://c5.patreon.com/external/favicon/rebrand/favicon.ico?v=af5597c2ef" alt="Patreon-Logo" width="15" style="float: right; margin-left: -30px; margin-right: 15px;"></a>
<br>
<a href="<?php echo htmlspecialchars($baseUrl); ?>comic/">Comic</a>
<a href="<?php echo htmlspecialchars($baseUrl); ?>archiv.php">Archiv</a>
<br>
<a href="<?php echo htmlspecialchars($baseUrl); ?>about.php">Infos</a>
<a href="<?php echo htmlspecialchars($baseUrl); ?>charaktere.php">Charaktere</a>

<a href="<?php echo htmlspecialchars($baseUrl); ?>faq.php">FAQ</a>
<a href="<?php echo htmlspecialchars($baseUrl); ?>lizenz.php">Lizenz</a>
<br>
<a href="<?php echo htmlspecialchars($baseUrl); ?>impressum.php">Impressum</a>
<br>
<a href="<?php echo htmlspecialchars($baseUrl); ?>newsletter.php"><img src="https://fonts.gstatic.com/s/i/productlogos/gmail_2020q4/v8/web-64dp/logo_gmail_2020q4_color_1x_web_64dp.png" alt="E-Mail-Logo" width="15"> Newsletter <img src="https://ow2.res.office365.com/owalanding/2023.8.17.01/images/favicon.ico?v=4" alt="E-Mail-Logo" width="15"><p><img src="https://www.paypalobjects.com/marketing/web/logos/paypal-mark-color_new.svg" alt="PayPal-Logo" width="15">  Spenden  <img src="https://upload.wikimedia.org/wikipedia/commons/9/94/Patreon_logo.svg" alt="Patreon-Logo" width="15"></a></p>