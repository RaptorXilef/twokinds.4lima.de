<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ChapterRepositoryInterface;
use App\Contracts\Storage\ComicRepositoryInterface;

final readonly class SiteGeneratorService
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private ChapterRepositoryInterface $chapterRepo,
        private ConfigInterface $config,
    ) {
    }

    /**
     * Führt alle Background-Generatoren aus.
     */
    public function generateAll(): void
    {
        $this->generateSitemap();
        $this->generateRss();
    }

    private function generateSitemap(): void
    {
        $baseUrl   = $this->config->get('base_url');
        $publicDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public';

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        // 1. Startseite (Höchste Priorität)
        $this->addSitemapUrl($xml, $baseUrl . '/', '1.0', 'daily');

        // 2. Archiv / Kapitel (Hohe Priorität)
        $this->addSitemapUrl($xml, $baseUrl . '/archive', '0.9', 'weekly');
        $chapters = $this->chapterRepo->findAll();
        foreach ($chapters as $chapter) {
            $this->addSitemapUrl($xml, $baseUrl . '/archive/' . $chapter->id, '0.8', 'weekly');
        }

        // 3. Comic Seiten (Standard Priorität)
        $comics = $this->comicRepo->findAll();
        foreach ($comics as $comic) {
            // Datum für <lastmod> aus der ID generieren (YYYYMMDD)
            $dateStr = \substr($comic->id->value, 0, 8);
            $lastMod = \DateTimeImmutable::createFromFormat('Ymd', $dateStr) ?: new \DateTimeImmutable();

            // Wenn ein Bild hochgeladen wurde, nutzen wir dieses Datum als letzes Update
            if ($comic->imageUpdatedAt !== null) {
                $lastMod = (new \DateTimeImmutable())->setTimestamp($comic->imageUpdatedAt);
            }

            $this->addSitemapUrl($xml, $baseUrl . '/comic/' . $comic->id->value, '0.6', 'monthly', $lastMod->format('Y-m-d'));
        }

        $xml->endElement(); // urlset
        $xml->endDocument();

        \file_put_contents($publicDir . '/sitemap.xml', $xml->outputMemory());
    }

    private function generateRss(): void
    {
        $baseUrl   = $this->config->get('base_url');
        $publicDir = \rtrim((string) $this->config->get('root_path'), '/\\') . '/public';

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('rss');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('version', '2.0');

        $xml->startElement('channel');
        $xml->writeElement('title', $this->config->get('site_title', 'Twokinds auf Deutsch'));
        $xml->writeElement('link', $baseUrl);
        $xml->writeElement('description', $this->config->get('site_description', 'Die deutsche Übersetzung des Webcomics Twokinds.'));
        $xml->writeElement('language', 'de-de');

        // Die beiden fehlenden Header für Feed-Reader
        $xml->writeElement('lastBuildDate', (new \DateTimeImmutable())->format(\DATE_RFC2822));
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
                if (\file_exists($publicDir . '/assets/images/comic/lowres/' . $c->id->value . '.webp')) {
                    $feedComics[] = $c;
                }
            }
        }

        // Die neuesten zuerst (anhand der ID sortieren, da YYYYMMDD)
        \usort($feedComics, fn ($a, $b) => \strcmp($b->id->value, $a->id->value));

        // Nur die letzten 30 Einträge in den RSS Feed packen
        $feedComics = \array_slice($feedComics, 0, 30);

        foreach ($feedComics as $comic) {
            $xml->startElement('item');

            $title = $comic->name !== '' ? $comic->name : "Comic Seite {$comic->id->value}";
            $xml->writeElement('title', $title);

            // Ich belasse die URLs im neuen Format (ohne .php), damit das neue Routing greift
            $url = $baseUrl . '/comic/' . $comic->id->value;
            $xml->writeElement('link', $url);
            $xml->writeElement('guid', $url);

            // Exakter Nachbau deines alten HTML-Formats für die Feed-Reader
            $imgSrc = $baseUrl . '/assets/images/comic/lowres/' . $comic->id->value . '.webp';
            $desc   = '<p><img src="' . $imgSrc . '" alt="' . \htmlspecialchars($title, \ENT_QUOTES) . '" style="max-width: 100%; height: auto;" /></p>';

            if ($comic->transcript !== '') {
                // Das Transkript wird vom Editor bereits in <p> Tags geliefert
                $desc .= $comic->transcript;
            }

            $xml->startElement('description');
            $xml->writeCdata($desc);
            $xml->endElement(); // description

            // Datum aus ID (erste 8 Zeichen) extrahieren
            $dateStr = \substr($comic->id->value, 0, 8);
            $pubDate = \DateTimeImmutable::createFromFormat('Ymd', $dateStr) ?: new \DateTimeImmutable();
            $xml->writeElement('pubDate', $pubDate->format(\DATE_RFC2822));

            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();

        \file_put_contents($publicDir . '/rss.xml', $xml->outputMemory());
    }

    private function addSitemapUrl(\XMLWriter $xml, string $loc, string $priority, string $changefreq, ?string $lastmod = null): void
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
