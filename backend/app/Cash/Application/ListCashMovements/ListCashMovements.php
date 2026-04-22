<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashMovements;

use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ListCashMovements
{
    public function __construct(
        private CashMovementRepositoryInterface $cashMovementRepository,
    ) {}

    public function __invoke(string $cashSessionId): ListCashMovementsResponse
    {
        $movements = $this->cashMovementRepository->findByCashSessionId(Uuid::create($cashSessionId));

        return new ListCashMovementsResponse($movements);
    }
}
