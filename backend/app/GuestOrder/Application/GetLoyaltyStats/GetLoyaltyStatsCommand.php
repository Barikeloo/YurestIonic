<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetLoyaltyStats;

final readonly class GetLoyaltyStatsCommand
{
    public function __construct(
        public string $restaurantId,
    ) {}
}
