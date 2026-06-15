<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderFinalTicketCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $ticketId,
        private int $ticketNumber,
        private string $totalFormatted,
        private string $orderId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.final_ticket_created';
    }

    public function auditEntityType(): string
    {
        return 'order_final_ticket';
    }

    public function auditEntityId(): string
    {
        return $this->ticketId;
    }

    public function auditMetadata(): array
    {
        return [
            'ticket_number'   => $this->ticketNumber,
            'total_formatted' => $this->totalFormatted,
            'order_id'        => $this->orderId,
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
