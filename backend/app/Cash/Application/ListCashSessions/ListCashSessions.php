<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ListCashSessions
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(string $restaurantId): ListCashSessionsResponse
    {
        $restaurantUuid = Uuid::create($restaurantId);
        $sessions = $this->cashSessionRepository->findClosedByRestaurantId($restaurantUuid);

        return ListCashSessionsResponse::create($sessions);
    }
}
