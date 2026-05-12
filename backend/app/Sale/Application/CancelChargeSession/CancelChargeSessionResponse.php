<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final readonly class CancelChargeSessionResponse
{
    private function __construct(
        public string $id,
        public string $status,
        public int $paidDinersCount,
        public int $sessionPaidCents,
        public ?string $warningMessage,
        public array $paidDiners,
    ) {}

    public static function create(
        string $id,
        string $status,
        int $paidDinersCount,
        int $sessionPaidCents,
        ?string $warningMessage,
        array $paidDiners,
    ): self {
        return new self(
            id: $id,
            status: $status,
            paidDinersCount: $paidDinersCount,
            sessionPaidCents: $sessionPaidCents,
            warningMessage: $warningMessage,
            paidDiners: $paidDiners,
        );
    }

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
