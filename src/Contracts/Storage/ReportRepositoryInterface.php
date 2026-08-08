<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\Report;
use App\Core\ValueObject\ReportId;
use DateTimeImmutable;

interface ReportRepositoryInterface
{
    public function save(Report $report): void;

    public function findById(ReportId $id): ?Report;

    /**
     * @return Report[]
     */
    public function findAll(): array;

    /**
     * @return Report[]
     */
    public function findByStatus(string $status): array;

    public function countRecentByIpHash(string $ipHash, DateTimeImmutable $since): int;
}
