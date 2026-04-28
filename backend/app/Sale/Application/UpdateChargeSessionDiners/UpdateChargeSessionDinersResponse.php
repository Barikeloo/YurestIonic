<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Domain\Entity\ChargeSession;

final class UpdateChargeSessionDinersResponse
{
    public function __construct(
        public readonly string $id,
        public readonly int $dinersCount,
        public readonly int $amountPerDiner,
        public readonly int $totalCents,
        public readonly string $status,
    ) {}

    public static function fromEntity(ChargeSession $chargeSession): self
    {
        return new self(
            id: $chargeSession->id()->value(),
            dinersCount: $chargeSession->dinersCount(),
            amountPerDiner: $chargeSession->amountPerDiner()->value(),
            totalCents: $chargeSession->totalCents(),
            status: $chargeSession->status()->value(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'diners_count' => $this->dinersCount,
            'amount_per_diner' => $this->amountPerDiner,
            'total_cents' => $this->totalCents,
            'status' => $this->status,
        ];
    }
}
