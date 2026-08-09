<?php

declare(strict_types=1);

namespace App\Infrastructure\System;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\CharacterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\System\SiteGeneratorInterface;
use DateTimeImmutable;
use XMLWriter;

final class StaticSiteGenerator implements SiteGeneratorInterface
{
    private bool $needsGeneration = false;

    public function __construct(
        private readonly ComicRepositoryInterface $comicRepo,
        /**
         * @phpstan-ignore-next-line
         */
        private readonly ChapterRepositoryInterface $chapterRepo, // Kann für zukünftige Feeds nützlich sein
        private readonly ConfigInterface $config,
        private readonly CharacterRepositoryInterface $characterRepo,
    ) {
    }

    /**
     * Führt alle Background-Generatoren aus.
     *
     * Wird vom ComicService aufgerufen. Wir merken uns nur, DASS generiert werden muss.
     */
    public function generateAll(): void
    {
        $this->needsGeneration = true;
    }

    // PERF: Dieser Destruktor feuert GANZ am Ende, NACHDEM der Browser längst seine Antwort hat!
    public function __destruct()
    {
        if (!$this->needsGeneration) {
            return;
        }

        $this->doGenerateSitemap();
        $this->doGenerateRss();
    }

    private function doGenerateSitemap(): void
    {
        $baseUrl = \rtrim($this->config->getBaseUrl(), '/');

        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $publicDir = \rtrim($rootStr, '/\\') . '/public';

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        // 1. Die statischen Hauptseiten
        $this->addSitemapUrl($xml, $baseUrl, '1.0', 'daily');
        $this->addSitemapUrl($xml, $baseUrl . '/archiv', '0.9', 'weekly');
        $this->addSitemapUrl($xml, $baseUrl . '/charaktere', '0.9', 'weekly');

        // 2. Wichtige Neben-Seiten
        $this->addSitemapUrl($xml, $baseUrl . '/projekt', '0.8', 'monthly');
        $this->addSitemapUrl($xml, $baseUrl . '/lesezeichen', '0.8', 'monthly');

        // 3. System- & Rechtliche Seiten (Niedrigere Priorität, aber wichtig für Indexierung)
        $this->addSitemapUrl($xml, $baseUrl . '/login', '0.5', 'monthly');
        $this->addSitemapUrl($xml, $baseUrl . '/registrieren', '0.5', 'monthly');
        $this->addSitemapUrl($xml, $baseUrl . '/impressum', '0.3', 'yearly');
        $this->addSitemapUrl($xml, $baseUrl . '/datenschutz', '0.3', 'yearly');

        // 4. Alle individuellen Charakter-Seiten
        $characters = $this->characterRepo->findAll();
        foreach ($characters as $char) {
            $charUrlName = \urlencode($char->id->value);
            $this->addSitemapUrl($xml, $baseUrl . '/charaktere/' . $charUrlName, '0.7', 'monthly');
        }

        // 5. Alle individuellen Comic-Seiten
        $comics = $this->comicRepo->findAll();
        foreach ($comics as $comic) {
            // Datum für <lastmod> aus der ID generieren (YYYYMMDD)
            $dateStr = \substr($comic->id->value, 0, 8);

            $parsedDate = DateTimeImmutable::createFromFormat('Ymd', $dateStr);
            $lastMod = $parsedDate !== false ? $parsedDate : new DateTimeImmutable();

            // Wenn ein Bild hochgeladen wurde, nutzen wir dieses Datum als letzes Update
            if ($comic->imageUpdatedAt !== null) {
                $lastMod = (new DateTimeImmutable())->setTimestamp($comic->imageUpdatedAt);
            }

            $this->addSitemapUrl(
                $xml,
                $baseUrl . '/comic/' . $comic->id->value,
                '0.6',
                'monthly',
                $lastMod->format('Y-m-d'),
            );
        }

        $xml->endElement(); // urlset schließen
        $xml->endDocument();

        \file_put_contents($publicDir . '/sitemap.xml', $xml->outputMemory());
    }

    private function doGenerateRss(): void
    {
        $baseUrl = $this->config->getBaseUrl();

        $rootRaw = $this->config->get('root_path', '');
        $rootStr = \is_string($rootRaw) ? $rootRaw : '';
        $publicDir = \rtrim($rootStr, '/\\') . '/public';

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('rss');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('version', '2.0');

        $xml->startElement('channel');

        $titleRaw = $this->config->get('site_title', 'Twokinds auf Deutsch');
        $xml->writeElement('title', \is_string($titleRaw) ? $titleRaw : 'Twokinds auf Deutsch');
        $xml->writeElement('link', $baseUrl);

        $descRaw = $this->config->get('site_description', 'Die deutsche Übersetzung des Webcomics Twokinds.');
        $xml->writeElement('description', \is_string($descRaw) ? $descRaw : 'Die deutsche Übersetzung des Webcomics Twokinds.');
        $xml->writeElement('language', 'de-de');

        $xml->writeElement('lastBuildDate', (new DateTimeImmutable())->format(\DATE_RFC2822));
        $xml->writeElement('generator', 'Twokinds Admin Panel Generator');

        // Atom Self-Link
        $xml->startElement('atom:link');
        $xml->writeAttribute('href', $baseUrl . '/rss.xml');
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        $comics = $this->comicRepo->findAll();

        // WICHTIG: Schlauer Filter mit Fallback auf die Festplatte
        $feedComics = [];

        foreach ($comics as $c) {
            if ($c->imageUpdatedAt !== null) {
                $feedComics[] = $c;
            } else {
                // Fallback: Wenn in der DB kein Zeitstempel steht, prüfe ob die Datei physisch existiert
                if (\file_exists($publicDir . '/assets/images/comics/lowres/' . $c->id->value . '.webp')) {
                    $feedComics[] = $c;
                }
            }
        }

        // Die neuesten zuerst (anhand der ID sortieren, da YYYYMMDD)
        \usort($feedComics, fn ($a, $b): int => \strcmp($b->id->value, $a->id->value));

        // Max Items aus der Config ziehen (Default 25, falls Eintrag fehlt)
        $maxItemsRaw = $this->config->get('rss_max_items', 25);
        $maxItems = \is_scalar($maxItemsRaw) ? (int) $maxItemsRaw : 25;

        $feedComics = \array_slice($feedComics, 0, $maxItems);

        foreach ($feedComics as $comic) {
            $xml->startElement('item');

            $title = $comic->name !== '' ? $comic->name : "Comic Seite {$comic->id->value}";
            $xml->writeElement('title', $title);

            // Ich belasse die URLs im neuen Format (ohne .php), damit das neue Routing greift
            $url = $baseUrl . '/comic/' . $comic->id->value;
            $xml->writeElement('link', $url);
            $xml->writeElement('guid', $url);

            // Exakter Nachbau deines alten HTML-Formats für die Feed-Reader
            $imgSrc = $baseUrl . '/assets/images/comics/lowres/' . $comic->id->value . '.webp';
            $desc = '<p><img src="' . $imgSrc . '" alt="' . \htmlspecialchars($title, \ENT_QUOTES) . '" style="max-width: 100%; height: auto;" /></p>';

            if ($comic->transcript !== '') {
                // Das Transkript wird vom Editor bereits in <p> Tags geliefert
                $desc .= $comic->transcript;
            }

            $xml->startElement('description');
            $xml->writeCdata($desc);
            $xml->endElement(); // description

            // Datum aus ID (erste 8 Zeichen) extrahieren
            $dateStr = \substr($comic->id->value, 0, 8);

            $parsedDate = DateTimeImmutable::createFromFormat('Ymd', $dateStr);
            $pubDate = $parsedDate !== false ? $parsedDate : new DateTimeImmutable();

            $xml->writeElement('pubDate', $pubDate->format(\DATE_RFC2822));

            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();

        \file_put_contents($publicDir . '/rss.xml', $xml->outputMemory());
    }

    private function addSitemapUrl(XMLWriter $xml, string $loc, string $priority, string $changefreq, ?string $lastmod = null): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $loc);
        if ($lastmod !== null) {
            $xml->writeElement('lastmod', $lastmod);
        }
        $xml->writeElement('changefreq', $changefreq);
        $xml->writeElement('priority', $priority);
        $xml->endElement();
    }
}
