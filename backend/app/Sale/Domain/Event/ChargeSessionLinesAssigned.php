<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ChargeSessionLinesAssigned implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $chargeSessionId,
        private string $assignmentsSummary,
        private int $totalAssigned,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.lines_assigned';
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
            'assignments_summary' => $this->assignmentsSummary,
            'total_assigned'      => $this->totalAssigned,
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
