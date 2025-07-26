<?php
/**
 * Dies ist die Administrationsseite für den Sitemap-Generator.
 * Sie generiert oder aktualisiert die sitemap.xml-Datei im öffentlichen Hauptverzeichnis
 * basierend auf einer Konfigurationsdatei (sitemap.json).
 */

// Starte den Output Buffer als ALLERERSTE Zeile, um wirklich jede Ausgabe abzufangen.
ob_start();

// Starte die PHP-Sitzung. Notwendig, um den Anmeldestatus zu überprüfen.
session_start();

// Logout-Logik: Muss vor dem Sicherheitscheck erfolgen.
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Zerstöre alle Session-Variablen.
    $_SESSION = array();

    // Lösche das Session-Cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httplly"]
        );
    }

    // Zerstöre die Session.
    session_destroy();

    // Weiterleitung zur Login-Seite (index.php im Admin-Bereich).
    // ob_end_clean() leert den Output Buffer, bevor die Weiterleitung gesendet wird.
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// SICHERHEITSCHECK: Nur für angemeldete Administratoren zugänglich.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Wenn nicht angemeldet, zur Login-Seite weiterleiten.
    ob_end_clean(); // Output Buffer leeren, da wir umleiten
    header('Location: index.php');
    exit;
}

// Pfade zu den benötigten Ressourcen
$headerPath = __DIR__ . '/../src/layout/header.php';
$footerPath = __DIR__ . '/../src/layout/footer.php';
$sitemapConfigPath = __DIR__ . '/../src/config/sitemap.json';
$sitemapOutputPath = __DIR__ . '/../sitemap.xml'; // Sitemap im öffentlichen Hauptverzeichnis

// Setze Parameter für den Header.
$pageTitle = 'Sitemap Generator';
$pageHeader = 'Sitemap Generator';
$robotsContent = 'noindex, nofollow'; // Admin-Seiten nicht crawlen

// Basis-URL der Webseite dynamisch bestimmen
$isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
if ($isLocal) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Beispiel: /twokinds/default-website/twokinds/admin/sitemap_generator.php
    // Wir wollen die Basis-URL des Projekts: /twokinds/default-website/twokinds/
    $pathParts = explode('/', $_SERVER['SCRIPT_NAME']);
    array_pop($pathParts); // Entfernt 'sitemap_generator.php'
    array_pop($pathParts); // Entfernt 'admin'
    $basePath = implode('/', $pathParts);
    $baseUrl = $protocol . $host . $basePath . '/';
} else {
    $baseUrl = 'https://twokinds.4lima.de/';
}

// Funktion zum Generieren der Sitemap
function generateSitemap(string $sitemapConfigPath, string $sitemapOutputPath, string $baseUrl): string
{
    $message = '';
    $status = 'error';

    if (!file_exists($sitemapConfigPath)) {
        return 'Fehler: Konfigurationsdatei sitemap.json nicht gefunden unter ' . htmlspecialchars($sitemapConfigPath);
    }

    $configContent = file_get_contents($sitemapConfigPath);
    $sitemapConfig = json_decode($configContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Fehler beim Dekodieren von sitemap.json: ' . json_last_error_msg();
    }

    if (!isset($sitemapConfig['pages']) || !is_array($sitemapConfig['pages'])) {
        return 'Fehler: Ungültiges Format in sitemap.json. "pages"-Array fehlt oder ist ungültig.';
    }

    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;

    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->appendChild($urlset);

    foreach ($sitemapConfig['pages'] as $page) {
        if (!isset($page['name']) || !isset($page['path'])) {
            error_log("Warnung: Ungültiger Seiteneintrag in sitemap.json übersprungen.");
            continue;
        }

        $filePath = realpath(__DIR__ . '/../' . $page['path'] . $page['name']); // Pfad relativ zum Projekt-Root

        if (!file_exists($filePath)) {
            error_log("Warnung: Datei nicht gefunden für Sitemap-Eintrag: " . htmlspecialchars($filePath));
            continue;
        }

        $url = $xml->createElement('url');

        $loc = $xml->createElement('loc', htmlspecialchars($baseUrl . ltrim($page['path'], './') . $page['name']));
        $url->appendChild($loc);

        $lastmodTimestamp = filemtime($filePath);
        $lastmod = $xml->createElement('lastmod', date('Y-m-d\TH:i:sP', $lastmodTimestamp));
        $url->appendChild($lastmod);

        if (isset($page['changefreq'])) {
            $changefreq = $xml->createElement('changefreq', htmlspecialchars($page['changefreq']));
            $url->appendChild($changefreq);
        }

        if (isset($page['priority'])) {
            $priority = $xml->createElement('priority', htmlspecialchars(sprintf('%.1f', $page['priority'])));
            $url->appendChild($priority);
        }

        $urlset->appendChild($url);
    }

    $xml->save($sitemapOutputPath);
    $message = 'Sitemap.xml erfolgreich generiert und gespeichert unter: ' . htmlspecialchars($sitemapOutputPath);
    $status = 'success';
    return $message;
}

$generationMessage = '';
if (isset($_POST['generate_sitemap'])) {
    $generationMessage = generateSitemap($sitemapConfigPath, $sitemapOutputPath, $baseUrl);
}

// Binde den gemeinsamen Header ein.
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    die('Fehler: Header-Datei nicht gefunden. Pfad: ' . htmlspecialchars($headerPath));
}
?>

<section>
    <h2 class="page-header">Sitemap generieren</h2>
    <p>Hier können Sie die <code>sitemap.xml</code> für Ihre Webseite generieren oder aktualisieren.</p>
    <p>Die Sitemap wird im Hauptverzeichnis Ihrer Webseite abgelegt und enthält Links zu den wichtigsten Seiten, wie in
        <code>src/config/sitemap.json</code> konfiguriert.</p>

    <form method="POST" action="">
        <button type="submit" name="generate_sitemap" class="button">Sitemap generieren / aktualisieren</button>
    </form>

    <?php if (!empty($generationMessage)): ?>
        <div
            style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; background-color: #e0ffe0; border-radius: 5px; color: #155724;">
            <p><?php echo $generationMessage; ?></p>
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 30px;">Informationen zur Sitemap.xml</h3>
    <p>Eine Sitemap ist eine XML-Datei, die Suchmaschinen wie Google und Bing über die Struktur Ihrer Webseite
        informiert und ihnen hilft, Ihre Inhalte effizienter zu crawlen und zu indexieren. Sie enthält eine Liste aller
        URLs, die Sie den Suchmaschinen mitteilen möchten.</p>

    <h4>Welche Informationen müssen und sollten in der XML enthalten sein und warum?</h4>
    <ul>
        <li>
            <strong><code>&lt;loc&gt;</code> (Location / URL der Seite)</strong>:
            <p>Dies ist das einzige **Pflichtfeld**. Es gibt die vollständige URL der Webseite an.
                **Warum**: Suchmaschinen benötigen die genaue Adresse, um Ihre Seite zu finden und zu besuchen.</p>
        </li>
        <li>
            <strong><code>&lt;lastmod&gt;</code> (Letzte Änderung)</strong>:
            <p>Gibt das Datum der letzten Änderung der Datei an. Das Format muss dem W3C Datetime Format entsprechen
                (YYYY-MM-DDThh:mm:ssTZD).
                **Warum**: Hilft Suchmaschinen zu erkennen, ob sich der Inhalt einer Seite seit dem letzten Besuch
                geändert hat. Dies kann das Crawling effizienter machen, da Seiten, die sich nicht geändert haben,
                seltener neu gecrawlt werden müssen.</p>
        </li>
        <li>
            <strong><code>&lt;changefreq&gt;</code> (Änderungsfrequenz)</strong>:
            <p>Ein Hinweis darauf, wie oft sich der Inhalt der Seite voraussichtlich ändert (z.B. <code>always</code>,
                <code>hourly</code>, <code>daily</code>, <code>weekly</code>, <code>monthly</code>, <code>yearly</code>,
                <code>never</code>).
                **Warum**: Dies ist ein **Hinweis** für Crawler. Wenn Sie angeben, dass sich eine Seite täglich ändert,
                kann dies Suchmaschinen dazu ermutigen, diese Seite häufiger zu besuchen. Es ist jedoch keine Garantie,
                dass dies auch tatsächlich geschieht.</p>
        </li>
        <li>
            <strong><code>&lt;priority&gt;</code> (Priorität)</strong>:
            <p>Die Priorität der URL relativ zu anderen URLs auf Ihrer Webseite. Die Werte reichen von 0.0 (niedrigste
                Priorität) bis 1.0 (höchste Priorität). Der Standardwert ist 0.5.
                **Warum**: Zeigt Suchmaschinen an, welche Seiten Sie für wichtiger halten als andere. Seiten mit höherer
                Priorität werden möglicherweise häufiger gecrawlt. Auch dies ist nur ein **Hinweis** und sollte
                realistisch gesetzt werden.</p>
        </li>
    </ul>
    <p>Durch die Bereitstellung dieser Informationen in Ihrer <code>sitemap.xml</code> können Sie Suchmaschinen dabei
        unterstützen, Ihre Webseite besser zu verstehen und Ihre Inhalte effektiver zu indexieren, was letztendlich zu
        einer besseren Sichtbarkeit in den Suchergebnissen führen kann.</p>

</section>

<?php
// Binde den gemeinsamen Footer ein.
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    echo "</body></html>"; // HTML schließen, falls Footer fehlt.
}
?>