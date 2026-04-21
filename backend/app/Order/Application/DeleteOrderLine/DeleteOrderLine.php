<?php

namespace App\Order\Application\DeleteOrderLine;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteOrderLine
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function __invoke(string $lineId): bool
    {
        $line = $this->orderLineRepository->findByUuid(Uuid::create($lineId));

        if ($line === null) {
            return false;
        }

        $this->orderLineRepository->delete($line->id());

        return true;
    }
}
