<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

interface LoyaltyReadRepositoryInterface
{
    public function listCustomers(
        string $restaurantId,
        ?string $search,
        int $perPage,
        int $page,
    ): array;

    public function countCustomers(string $restaurantId, ?string $search): int;

    public function getStats(string $restaurantId): array;

    public function getCustomerDetail(string $customerUuid, string $restaurantId): ?array;
}
