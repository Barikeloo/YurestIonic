<?php

declare(strict_types=1);

namespace App\Cash\Application\RegisterCashMovement;

final readonly class RegisterCashMovementResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $restaurantId,
        public string $cashSessionId,
        public string $type,
        public string $reasonCode,
        public int $amountCents,
        public ?string $description,
        public string $userId,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $restaurantId,
        string $cashSessionId,
        string $type,
        string $reasonCode,
        int $amountCents,
        ?string $description,
        string $userId,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            restaurantId: $restaurantId,
            cashSessionId: $cashSessionId,
            type: $type,
            reasonCode: $reasonCode,
            amountCents: $amountCents,
            description: $description,
            userId: $userId,
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
