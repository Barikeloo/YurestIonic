<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashMovements;

use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ListCashMovements
{
    public function __construct(
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
    ) {}

    public function __invoke(ListCashMovementsCommand $command): ListCashMovementsResponse
    {
        $movements = $this->cashMovementRepository->findByCashSessionId(
            Uuid::create($command->cashSessionId),
        );

        $items = array_map(
            static fn (CashMovement $m): ListCashMovementsItemResponse => ListCashMovementsItemResponse::create(
                uuid: $m->uuid()->value(),
                type: $m->type()->value(),
                reasonCode: $m->reasonCode()->value(),
                amountCents: $m->amount()->toCents(),
                description: $m->description(),
                userId: $m->userId()->value(),
                createdAt: $m->createdAt()->format('Y-m-d H:i:s'),
            ),
            $movements,
        );

        return ListCashMovementsResponse::create($items);
    }
}
