<?php

declare(strict_types=1);

namespace App\Cash\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class CashMovementRegistered implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $cashMovementId,
        private string $cashSessionId,
        private string $movementType,
        private string $amountFormatted,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'caja.cash_movement';
    }

    public function auditEntityType(): string
    {
        return 'cash_movement';
    }

    public function auditEntityId(): string
    {
        return $this->cashMovementId;
    }

    public function auditMetadata(): array
    {
        return [
            'movement_type' => $this->movementType,
            'amount_formatted' => $this->amountFormatted,
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
