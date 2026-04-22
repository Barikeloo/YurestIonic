<?php

declare(strict_types=1);

namespace App\Cash\Application\OpenCashSession;

use App\Cash\Domain\Entity\CashSession;

final class OpenCashSessionResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurantId,
        public readonly string $deviceId,
        public readonly string $openedByUserId,
        public readonly string $openedAt,
        public readonly int $initialAmountCents,
        public readonly string $status,
        public readonly ?string $notes,
    ) {}

    public static function create(CashSession $cashSession): self
    {
        return new self(
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            restaurantId: $cashSession->restaurantId()->value(),
            deviceId: $cashSession->deviceId(),
            openedByUserId: $cashSession->openedByUserId()->value(),
            openedAt: $cashSession->openedAt()->format('Y-m-d H:i:s'),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
            status: $cashSession->status()->value(),
            notes: $cashSession->notes(),
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
