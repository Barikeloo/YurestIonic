<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final class CreateChargeSessionResponse
{
    /**
     * @param  array<int>  $paidDiners
     */
    private function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly int $dinersCount,
        public readonly int $totalCents,
        public readonly int $amountPerDiner,
        public readonly int $paidDinersCount,
        public readonly int $remainingAmount,
        public readonly string $status,
        public readonly array $paidDiners,
    ) {}

    public static function fromEntity(ChargeSession $chargeSession): self
    {
        // Obtener números de comensales que ya pagaron
        $paidDiners = [];
        foreach ($chargeSession->payments() as $payment) {
            if ($payment->isCompleted()) {
                $paidDiners[] = $payment->dinerNumber();
            }
        }

        return new self(
            id: $chargeSession->id()->value(),
            orderId: $chargeSession->orderId()->value(),
            dinersCount: $chargeSession->dinersCount(),
            totalCents: $chargeSession->totalCents(),
            amountPerDiner: $chargeSession->amountPerDiner()->value(),
            paidDinersCount: $chargeSession->paidDinersCount(),
            remainingAmount: $chargeSession->remainingAmount(),
            status: $chargeSession->status()->value(),
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
            'order_id' => $this->orderId,
            'diners_count' => $this->dinersCount,
            'total_cents' => $this->totalCents,
            'amount_per_diner' => $this->amountPerDiner,
            'paid_diners_count' => $this->paidDinersCount,
            'remaining_amount' => $this->remainingAmount,
            'status' => $this->status,
            'paid_diners' => $this->paidDiners,
        ];
    }
}
