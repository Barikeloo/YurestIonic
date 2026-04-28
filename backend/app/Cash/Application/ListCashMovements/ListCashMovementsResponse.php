<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashMovements;

use App\Cash\Domain\Entity\CashMovement;

final class ListCashMovementsResponse
{
    /** @param CashMovement[] $movements */
    public function __construct(private array $movements) {}

    public function toArray(): array
    {
        return [
            'movements' => array_map(fn (CashMovement $m) => [
                'uuid' => $m->uuid()->value(),
                'type' => $m->type()->value(),
                'reason_code' => $m->reasonCode()->value(),
                'amount_cents' => $m->amount()->toCents(),
                'description' => $m->description(),
                'user_id' => $m->userId()->value(),
                'created_at' => $m->createdAt()->format('Y-m-d H:i:s'),
            ], $this->movements),
        ];
    }
}
