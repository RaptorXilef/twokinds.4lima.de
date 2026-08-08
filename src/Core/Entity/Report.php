<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\ComicId;
use App\Core\ValueObject\ReportId;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Report
{
    public function __construct(
        public ReportId $id,
        public ?ComicId $comicId, // Jetzt optional (?)
        public ?string $userId, // Optionale User ID für angemeldete Nutzer
        public DateTimeImmutable $date,
        public string $status,
        public string $ipHash, // Spamschutz aus alter JSON übernommen
        public string $submitterName,
        public bool $wantsCredit,
        public string $type,
        public ?string $screenshotUrl,
        public string $description,
        public string $transcriptSuggestion,
        public string $transcriptOriginal,
        public string $debugInfo,
        public ?string $submitterAvatarUrl = null,
    ) {
        if (!\in_array($status, ['open', 'closed', 'spam'], true)) {
            throw new InvalidArgumentException("Ungültiger Report-Status: {$status}");
        }
        if (!\in_array($type, ['transcript', 'image', 'other'], true)) {
            throw new InvalidArgumentException("Ungültiger Report-Typ: {$type}");
        }
    }
}
