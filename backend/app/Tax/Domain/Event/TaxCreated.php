<?php

declare(strict_types=1);

namespace App\Tax\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class TaxCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $taxId,
        private string $name,
        private int $percentage,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'tax.created';
    }

    public function auditEntityType(): string
    {
        return 'tax';
    }

    public function auditEntityId(): string
    {
        return $this->taxId;
    }

    public function auditMetadata(): array
    {
        return [
            'tax_name' => $this->name,
            'percentage' => $this->percentage,
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
