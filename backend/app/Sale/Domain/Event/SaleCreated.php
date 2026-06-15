<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class SaleCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $saleId,
        private string $totalFormatted,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.created';
    }

    public function auditEntityType(): string
    {
        return 'sale';
    }

    public function auditEntityId(): string
    {
        return $this->saleId;
    }

    public function auditMetadata(): array
    {
        return [
            'total_formatted' => $this->totalFormatted,
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
