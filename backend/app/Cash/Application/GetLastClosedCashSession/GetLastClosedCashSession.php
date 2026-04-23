<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class GetLastClosedCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
    ): GetLastClosedCashSessionResponse {
        $restaurantUuid = Uuid::create($restaurantId);

        $lastClosed = $this->cashSessionRepository->findLastClosedByRestaurant($restaurantUuid);
        $orphanSession = $this->cashSessionRepository->findOrphanByRestaurant($restaurantUuid);

        $operatorName = null;
        $tickets = 0;
        $diners = 0;

        if ($lastClosed !== null) {
            $operator = $this->userRepository->findById($lastClosed->openedByUserId()->value());
            $operatorName = $operator?->name()->value() ?? null;

            $sales = $this->saleRepository->findByCashSessionId($lastClosed->uuid());
            $tickets = count($sales);

            foreach ($sales as $sale) {
                $order = $sale->orderId();
            }
        }

        return GetLastClosedCashSessionResponse::create(
            $lastClosed,
            $orphanSession,
            $operatorName,
            $tickets,
            $diners,
        );
    }
}
