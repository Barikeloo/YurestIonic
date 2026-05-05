<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final class CancelChargeSessionResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly int $paidDinersCount,
        public readonly int $sessionPaidCents,
        public readonly ?string $warningMessage,
        public readonly array $paidDiners,
    ) {}

    public static function fromEntity(
        ChargeSession $chargeSession,
        int $paidCents,
        array $paidDinerNumbers,
        ?string $warningMessage,
    ): self {
        return new self(
            id: $chargeSession->id()->value(),
            status: $chargeSession->status()->value(),
            paidDinersCount: count($paidDinerNumbers),
            sessionPaidCents: $paidCents,
            warningMessage: $warningMessage,
            paidDiners: $paidDinerNumbers,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'paid_diners_count' => $this->paidDinersCount,
            'session_paid_cents' => $this->sessionPaidCents,
            'warning_message' => $this->warningMessage,
            'paid_diners' => $this->paidDiners,
        ];
    }
}
