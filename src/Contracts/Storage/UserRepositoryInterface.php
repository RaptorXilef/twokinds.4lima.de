<?php

declare(strict_types=1);

namespace App\Contracts\Storage;

use App\Core\Entity\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    /**
     * @return array<int, User>
     */
    public function findAll(): array;

    public function save(User $user): void;

    public function delete(string $id): void;

    // Löscht unbestätigte Accounts, die älter als X Minuten sind
    public function deleteUnverifiedAccounts(int $olderThanMinutes): int;

    /**
     * @return array<int, User>
     */
    public function findNewsletterSubscribers(bool $transcriptOnly = false): array;
}
