<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetLoyaltyStats;

use App\GuestOrder\Domain\Interfaces\LoyaltyReadRepositoryInterface;

final class GetLoyaltyStats
{
    public function __construct(
        private readonly LoyaltyReadRepositoryInterface $loyaltyReadRepository,
    ) {}

    public function __invoke(GetLoyaltyStatsCommand $command): array
    {
        return $this->loyaltyReadRepository->getStats($command->restaurantId);
    }
}
