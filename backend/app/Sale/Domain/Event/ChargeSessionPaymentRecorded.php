<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ChargeSessionPaymentRecorded implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $chargeSessionId,
        private string $paymentMethod,
        private string $amountFormatted,
        private ?int $dinerNumber,
        private bool $isSessionComplete,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.payment_recorded';
    }

    public function auditEntityType(): string
    {
        return 'charge_session';
    }

    public function auditEntityId(): string
    {
        return $this->chargeSessionId;
    }

    public function auditMetadata(): array
    {
        return [
            'payment_method'    => $this->paymentMethod,
            'amount_formatted'  => $this->amountFormatted,
            'diner_number'      => $this->dinerNumber,
            'is_session_complete' => $this->isSessionComplete,
        ];
    }

    public function auditBefore(): ?array
    {
        return null;
    }

    public function auditAfter(): ?array
    {
        return null;
    }
}
