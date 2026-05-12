<?php

declare(strict_types=1);

namespace App\Cash\Application\ForceCloseCashSession;

final readonly class ForceCloseCashSessionResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $restaurantId,
        public string $deviceId,
        public string $openedByUserId,
        public ?string $closedByUserId,
        public string $openedAt,
        public ?string $closedAt,
        public int $initialAmountCents,
        public string $status,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $restaurantId,
        string $deviceId,
        string $openedByUserId,
        ?string $closedByUserId,
        string $openedAt,
        ?string $closedAt,
        int $initialAmountCents,
        string $status,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            restaurantId: $restaurantId,
            deviceId: $deviceId,
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            openedAt: $openedAt,
            closedAt: $closedAt,
            initialAmountCents: $initialAmountCents,
            status: $status,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurantId,
            'device_id' => $this->deviceId,
            'opened_by_user_id' => $this->openedByUserId,
            'closed_by_user_id' => $this->closedByUserId,
            'opened_at' => $this->openedAt,
            'closed_at' => $this->closedAt,
            'initial_amount_cents' => $this->initialAmountCents,
            'status' => $this->status,
        ];
    }
}
