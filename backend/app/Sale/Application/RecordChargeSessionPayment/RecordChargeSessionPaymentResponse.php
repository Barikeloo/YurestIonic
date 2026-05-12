<?php

declare(strict_types=1);

namespace App\Sale\Application\RecordChargeSessionPayment;

final readonly class RecordChargeSessionPaymentResponse
{
    private function __construct(
        public string $paymentId,
        public string $chargeSessionId,
        public ?int $dinerNumber,
        public int $amountCents,
        public string $paymentMethod,
        public string $status,
        public int $sessionPaidDinersCount,
        public string $sessionStatus,
        public int $sessionRemainingCents,
        public bool $isSessionComplete,
    ) {}

    public static function create(
        string $paymentId,
        string $chargeSessionId,
        ?int $dinerNumber,
        int $amountCents,
        string $paymentMethod,
        string $status,
        int $sessionPaidDinersCount,
        string $sessionStatus,
        int $sessionRemainingCents,
        bool $isSessionComplete,
    ): self {
        return new self(
            paymentId: $paymentId,
            chargeSessionId: $chargeSessionId,
            dinerNumber: $dinerNumber,
            amountCents: $amountCents,
            paymentMethod: $paymentMethod,
            status: $status,
            sessionPaidDinersCount: $sessionPaidDinersCount,
            sessionStatus: $sessionStatus,
            sessionRemainingCents: $sessionRemainingCents,
            isSessionComplete: $isSessionComplete,
        );
    }

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
            'session_remaining_cents' => $this->sessionRemainingCents,
            'is_session_complete' => $this->isSessionComplete,
        ];
    }
}
