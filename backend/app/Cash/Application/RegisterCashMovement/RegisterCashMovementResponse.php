<?php

declare(strict_types=1);

namespace App\Cash\Application\RegisterCashMovement;

use App\Cash\Domain\Entity\CashMovement;

final class RegisterCashMovementResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurantId,
        public readonly string $cashSessionId,
        public readonly string $type,
        public readonly string $reasonCode,
        public readonly int $amountCents,
        public readonly ?string $description,
        public readonly string $userId,
    ) {}

    public static function create(CashMovement $cashMovement): self
    {
        return new self(
            id: $cashMovement->id()->value(),
            uuid: $cashMovement->uuid()->value(),
            restaurantId: $cashMovement->restaurantId()->value(),
            cashSessionId: $cashMovement->cashSessionId()->value(),
            type: $cashMovement->type()->value(),
            reasonCode: $cashMovement->reasonCode()->value(),
            amountCents: $cashMovement->amount()->toCents(),
            description: $cashMovement->description(),
            userId: $cashMovement->userId()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurantId,
            'cash_session_id' => $this->cashSessionId,
            'type' => $this->type,
            'reason_code' => $this->reasonCode,
            'amount_cents' => $this->amountCents,
            'description' => $this->description,
            'user_id' => $this->userId,
        ];
    }
}
