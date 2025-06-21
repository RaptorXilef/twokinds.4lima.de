<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    // Benutzer ist nicht eingeloggt, Weiterleitung zur login.php
    header('Location: login.php');
    exit();
}

require_once('includes/design/header.php');

// Hier kommt der Inhalt

function generateSitemap() {
    $baseURLs = array(
        'https://twokinds.4lima.de',
//        'https://www.twokinds.4lima.de',
    );

    // Verzeichnis oder Dateien aus dem Dateisystem auslesen
    $directory = '../'; // Geben Sie den Pfad zum Verzeichnis an, in dem sich Ihre Dateien befinden
    $files = scandir($directory);

    // Sitemap-Header erstellen
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    // URLs zur Sitemap hinzufügen
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !is_dir($directory . $file)) {
            foreach ($baseURLs as $baseURL) {
                $url = $baseURL . '/' . $file;
                $lastmod = date('Y-m-d\TH:i:sP', filemtime($directory . $file)); // Letzter Bearbeitungszeitpunkt
                $changefreq = 'yearly'; // Standardwert für changefreq
                $priority = '0.8'; // Standardwert für priority

            // Spezielle Zuweisungen für bestimmte URLs/Dateien
            if ($file == 'about.php') {
                $changefreq = 'yearly';
                $priority = '0.3';
            } elseif ($file == 'archiv.php') {
                $changefreq = 'weekly';
                $priority = '0.9';
            } elseif ($file == 'charaktere.php') {
                $changefreq = 'yearly';
                $priority = '0.3';
            } elseif ($file == 'faq.php') {
                $changefreq = 'yearly';
                $priority = '0.1';
            } elseif ($file == 'google5c760f191f071105.html') {
                $changefreq = 'never';
                $priority = '0.0';
            } elseif ($file == 'impressum.php') {
                $changefreq = 'yearly';
                $priority = '0.1';
            } elseif ($file == 'index.php') {
                $changefreq = 'daily';
                $priority = '1.0';
            } elseif ($file == 'lizenz.php') {
                $changefreq = 'yearly';
                $priority = '0.1';
            } elseif ($file == 'sitemap.xml') {
                $changefreq = 'weekly';
                $priority = '0.3';
            } elseif ($file == '20031022.php') {
                $changefreq = 'yearly';
                $priority = '0.8';
            } elseif ($file == 'comic.php') {
                $changefreq = 'never';
                $priority = '0.0';
            }

                $sitemap .= "\t<url>" . PHP_EOL;
                $sitemap .= "\t\t<loc>" . $url . "</loc>" . PHP_EOL;
                $sitemap .= "\t\t<lastmod>" . $lastmod . "</lastmod>" . PHP_EOL; // Lastmod einfügen
                $sitemap .= "\t\t<changefreq>" . $changefreq . "</changefreq>" . PHP_EOL; // Changefreq hinzufügen
                $sitemap .= "\t\t<priority>" . $priority . "</priority>" . PHP_EOL; // Priority hinzufügen
                $sitemap .= "\t</url>" . PHP_EOL;
            }
        }
    }

    // Sitemap abschließen
    $sitemap .= '</urlset>';

    // Sitemap in eine XML-Datei speichern
    $sitemapPath = $directory . 'sitemap.xml';
    file_put_contents($sitemapPath, $sitemap);

    // Pfad zur Sitemap zurückgeben
    return $sitemapPath;
}

// Sitemap generieren
$sitemapContent = generateSitemap();

// Das Erstellungsdatum/Änderungsdatum der Sitemap ermitteln
$sitemapLastMod = date('Y-m-d\TH:i:sP', filemtime('../sitemap.xml'));

// Den Link zur Sitemap generieren
$sitemapLink = '../sitemap.xml';

// Den Link zur Sitemap in einem neuen Tab öffnen
echo '<a href="' . $sitemapLink . '" target="_blank">Sitemap anzeigen (letzte Änderung: ' . $sitemapLastMod . ')</a>';

require_once('includes/design/footer.php');
?>
