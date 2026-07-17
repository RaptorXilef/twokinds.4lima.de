<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;

final readonly class Report
{
    public function __construct(
        public ReportId $id,
        public ComicId $comicId,
        public \DateTimeImmutable $date,
        public string $status,
        public string $ipHash, // Spamschutz aus alter JSON übernommen
        public string $submitterName,
        public string $type,
        public string $description,
        public string $transcriptSuggestion,
        public string $transcriptOriginal,
        public string $debugInfo,
    ) {
        if (! \in_array($status, ['open', 'closed', 'spam'], true)) {
            throw new \InvalidArgumentException("Ungültiger Report-Status: {$status}");
        }
        if (! \in_array($type, ['transcript', 'image', 'other'], true)) {
            throw new \InvalidArgumentException("Ungültiger Report-Typ: {$type}");
        }
    }
}
