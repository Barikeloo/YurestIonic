<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class CreditNoteIssued implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $creditNoteId,
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
        return 'sale.credit_note_issued';
    }

    public function auditEntityType(): string
    {
        return 'credit_note';
    }

    public function auditEntityId(): string
    {
        return $this->creditNoteId;
    }

    public function auditMetadata(): array
    {
        return [
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
