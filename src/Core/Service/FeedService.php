<?php

declare(strict_types=1);

namespace App\Core\Service;

use App\Contracts\Config\ConfigInterface;
use App\Contracts\Storage\ComicRepositoryInterface;
use App\Contracts\Utils\ClockInterface;

final readonly class FeedService
{
    public function __construct(
        private ComicRepositoryInterface $comicRepo,
        private ConfigInterface $config,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Generiert den RSS-Feed als reinen XML-String.
     */
    public function generateRssXml(int $limit = 50): string
    {
        $comics    = $this->comicRepo->findAll(); // Sortierung DESC passiert im Repo
        $baseUrl   = \rtrim($this->config->getBaseUrl(), '/');
        $lowResUrl = $baseUrl . '/assets/images/comics/lowres';

        $xml     = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"></rss>');
        $channel = $xml->addChild('channel');

        $channel->addChild('title', 'Twokinds auf Deutsch');
        $channel->addChild('link', $baseUrl);
        $channel->addChild('description', 'Die deutsche Übersetzung des Webcomics Twokinds.');
        $channel->addChild('language', 'de-de');
        $channel->addChild('lastBuildDate', $this->clock->now()->format(\DATE_RSS));

        $atomLink = $channel->addChild('atom:link', '', 'http://www.w3.org/2005/Atom');
        $atomLink->addAttribute('href', $baseUrl . '/rss.xml');
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');

        $count = 0;
        foreach ($comics as $comic) {
            if ($count >= $limit) {
                break;
            }

            // Lückenfüller ignorieren wir im RSS Feed optional
            if ($comic->type !== 'Comicseite') {
                continue;
            }

            $item      = $channel->addChild('item');
            $titleText = $comic->name !== '' ? $comic->name : "Seite {$comic->id->value}";
            $item->addChild('title', \htmlspecialchars($titleText));

            $link = $baseUrl . '/comic/' . $comic->id->value; // Dynamisches Routing, keine .php mehr!
            $item->addChild('link', $link);
            $item->addChild('guid', $link);

            // Bild-Timestamp für Cache-Busting anhängen
            $cb     = $comic->imageUpdatedAt !== null ? '?c=' . $comic->imageUpdatedAt : '';
            $imgSrc = "{$lowResUrl}/{$comic->id->value}.webp{$cb}";

            $descContent = "<p><img src=\"{$imgSrc}\" alt=\"{$titleText}\" style=\"max-width: 100%; height: auto;\" /></p>";
            if ($comic->transcript !== null && $comic->transcript !== '') {
                $descContent .= $comic->transcript;
            }

            $node = \dom_import_simplexml($item);
            $no   = $node->ownerDocument;
            $node->appendChild($no->createElement('description', \htmlspecialchars($descContent)));

            $timestamp = $comic->imageUpdatedAt ?? $this->clock->now()->getTimestamp();
            $dt        = (new \DateTimeImmutable())->setTimestamp($timestamp);
            $item->addChild('pubDate', $dt->format('r'));

            ++$count;
        }

        $dom                     = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }
}
