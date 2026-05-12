<?php

declare(strict_types=1);

namespace App\Cash\Application\OpenCashSession;

final readonly class OpenCashSessionResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $restaurantId,
        public string $deviceId,
        public string $openedByUserId,
        public string $openedAt,
        public int $initialAmountCents,
        public string $status,
        public ?string $notes,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $restaurantId,
        string $deviceId,
        string $openedByUserId,
        string $openedAt,
        int $initialAmountCents,
        string $status,
        ?string $notes,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            restaurantId: $restaurantId,
            deviceId: $deviceId,
            openedByUserId: $openedByUserId,
            openedAt: $openedAt,
            initialAmountCents: $initialAmountCents,
            status: $status,
            notes: $notes,
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
            'opened_at' => $this->openedAt,
            'initial_amount_cents' => $this->initialAmountCents,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
