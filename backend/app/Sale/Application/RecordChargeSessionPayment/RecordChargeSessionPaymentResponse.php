<?php

declare(strict_types=1);

namespace App\Sale\Application\RecordChargeSessionPayment;

use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Entity\ChargeSessionPayment;

final class RecordChargeSessionPaymentResponse
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $chargeSessionId,
        public readonly int $dinerNumber,
        public readonly int $amountCents,
        public readonly string $paymentMethod,
        public readonly string $status,
        public readonly int $sessionPaidDinersCount,
        public readonly string $sessionStatus,
        public readonly int $sessionRemainingAmount,
        public readonly bool $isSessionComplete,
    ) {}

    public static function fromEntities(
        ChargeSession $chargeSession,
        ChargeSessionPayment $payment
    ): self {
        return new self(
            paymentId: $payment->id()->value(),
            chargeSessionId: $chargeSession->id()->value(),
            dinerNumber: $payment->dinerNumber(),
            amountCents: $payment->amount(),
            paymentMethod: $payment->paymentMethod(),
            status: $payment->status(),
            sessionPaidDinersCount: $chargeSession->paidDinersCount(),
            sessionStatus: $chargeSession->status()->value(),
            sessionRemainingAmount: $chargeSession->remainingAmount(),
            isSessionComplete: $chargeSession->status()->isCompleted(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'charge_session_id' => $this->chargeSessionId,
            'diner_number' => $this->dinerNumber,
            'amount_cents' => $this->amountCents,
            'payment_method' => $this->paymentMethod,
            'status' => $this->status,
            'session_paid_diners_count' => $this->sessionPaidDinersCount,
            'session_status' => $this->sessionStatus,
            'session_remaining_amount' => $this->sessionRemainingAmount,
            'is_session_complete' => $this->isSessionComplete,
        ];
    }
}
