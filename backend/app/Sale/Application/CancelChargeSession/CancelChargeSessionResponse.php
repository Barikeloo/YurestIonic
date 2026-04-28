<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final class CancelChargeSessionResponse
{
    /**
     * @param  array<int>  $paidDiners
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly int $paidDinersCount,
        public readonly int $totalPaidCents,
        public readonly ?string $warningMessage,
        public readonly array $paidDiners,
    ) {}

    public static function fromEntity(
        ChargeSession $chargeSession,
        ?string $warningMessage
    ): self {
        // Obtener comensales que pagaron
        $paidDiners = [];
        $totalPaid = 0;
        foreach ($chargeSession->payments() as $payment) {
            if ($payment->isCompleted()) {
                $paidDiners[] = $payment->dinerNumber();
                $totalPaid += $payment->amount();
            }
        }

        return new self(
            id: $chargeSession->id()->value(),
            status: $chargeSession->status()->value(),
            paidDinersCount: $chargeSession->paidDinersCount(),
            totalPaidCents: $totalPaid,
            warningMessage: $warningMessage,
            paidDiners: $paidDiners,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'paid_diners_count' => $this->paidDinersCount,
            'total_paid_cents' => $this->totalPaidCents,
            'warning_message' => $this->warningMessage,
            'paid_diners' => $this->paidDiners,
        ];
    }
}
