<?php

declare(strict_types=1);

namespace App\Core\Entity;

use App\Core\ValueObject\EmailAddress;
use App\Core\ValueObject\Username;
use DateTimeImmutable;

final readonly class User
{
    /**
     * @param array<int, string> $socialLinks
     */
    public function __construct(
        public string $id,
        public Username $username, // VO statt string
        public EmailAddress $email, // VO statt string
        public string $passwordHash,
        public string $roleId,
        public DateTimeImmutable $createdAt,
        public bool $wantsNewsletter = false,
        public bool $wantsNewsletterTranscript = false,
        public bool $wantsNotificationReport = false,
        public ?string $avatarUrl = null,
        public ?string $bio = null,
        public array $socialLinks = [],
        public bool $publicBookmarks = false,
    ) {
    }
}
