<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Sale\Domain\Entity\ChargeSession;

final class CreateChargeSessionResponse
{
    /**
     * @param  array<array{diner_number: int, amount_cents: int, payment_method: string, paid_at: string}>  $paidDiners
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
        $paidDiners = [];
        foreach ($chargeSession->payments() as $payment) {
            if ($payment->isCompleted()) {
                $paidDiners[] = [
                    'diner_number' => $payment->dinerNumber(),
                    'amount_cents' => $payment->amount(),
                    'payment_method' => $payment->paymentMethod(),
                    'paid_at' => $payment->createdAt()->format(\DateTimeInterface::ATOM),
                ];
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
