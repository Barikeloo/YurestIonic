<?php

declare(strict_types=1);

namespace App\Tax\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class TaxUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $taxId,
        private array $before,
        private array $after,
        private array $changedFields,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'tax.updated';
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
            'tax_name' => $this->after['name'],
            'changed_fields' => implode(', ', $this->changedFields),
        ];
    }

    public function auditBefore(): ?array
    {
        return $this->before;
    }

    public function auditAfter(): ?array
    {
        return $this->after;
    }
}
