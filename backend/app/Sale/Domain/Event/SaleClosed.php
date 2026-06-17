<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class SaleClosed implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $saleId,
        private string $orderId,
        private string $restaurantUuid,
        private ?string $closedByUserIdBefore,
        private ?int $ticketNumberBefore,
        private ?int $totalCentsBefore,
        private ?string $closedByUserIdAfter,
        private ?int $ticketNumberAfter,
        private ?int $totalCentsAfter,
        private string $totalFormatted,
        private int $linesCount,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.closed';
    }

    public function auditEntityType(): string
    {
        return 'sale';
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function restaurantUuid(): string
    {
        return $this->restaurantUuid;
    }

    public function auditEntityId(): string
    {
        return $this->saleId;
    }

    public function auditMetadata(): array
    {
        return [
            'total_formatted' => $this->totalFormatted,
            'lines_count'     => $this->linesCount,
        ];
    }

    public function auditBefore(): ?array
    {
        return [
            'closed_by_user_id' => $this->closedByUserIdBefore,
            'ticket_number'     => $this->ticketNumberBefore,
            'total_cents'       => $this->totalCentsBefore,
        ];
    }

    public function auditAfter(): ?array
    {
        return [
            'closed_by_user_id' => $this->closedByUserIdAfter,
            'ticket_number'     => $this->ticketNumberAfter,
            'total_cents'       => $this->totalCentsAfter,
        ];
    }
}
