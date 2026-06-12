<?php

namespace App\Tax\Domain\Entity;

use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Event\TaxCreated;
use App\Tax\Domain\Event\TaxDeleted;
use App\Tax\Domain\Event\TaxUpdated;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class Tax
{
    use RecordsEvents;

    private function __construct(
        private Uuid $id,
        private TaxName $name,
        private TaxPercentage $percentage,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(TaxName $name, TaxPercentage $percentage): self
    {
        $now = DomainDateTime::now();

        $tax = new self(
            id: Uuid::generate(),
            name: $name,
            percentage: $percentage,
            createdAt: $now,
            updatedAt: $now,
        );

        $tax->recordEvent(new TaxCreated(
            taxId: $tax->id->value(),
            name: $tax->name->value(),
            percentage: $tax->percentage->value(),
        ));

        return $tax;
    }

    public static function fromPersistence(
        string $id,
        string $name,
        int $percentage,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            name: TaxName::create($name),
            percentage: TaxPercentage::create($percentage),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function update(?TaxName $name = null, ?TaxPercentage $percentage = null): void
    {
        $before = [
            'name' => $this->name->value(),
            'percentage' => $this->percentage->value(),
        ];

        if ($name !== null) {
            $this->name = $name;
        }
        if ($percentage !== null) {
            $this->percentage = $percentage;
        }
        $this->touch();

        $after = [
            'name' => $this->name->value(),
            'percentage' => $this->percentage->value(),
        ];

        $changedFields = [];
        if ($before['name'] !== $after['name']) {
            $changedFields[] = 'nombre';
        }
        if ($before['percentage'] !== $after['percentage']) {
            $changedFields[] = 'porcentaje';
        }

        if ($changedFields !== []) {
            $this->recordEvent(new TaxUpdated(
                taxId: $this->id->value(),
                before: $before,
                after: $after,
                changedFields: $changedFields,
            ));
        }
    }

    public function delete(): void
    {
        $this->recordEvent(new TaxDeleted(
            taxId: $this->id->value(),
            name: $this->name->value(),
            percentage: $this->percentage->value(),
        ));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): TaxName
    {
        return $this->name;
    }

    public function percentage(): TaxPercentage
    {
        return $this->percentage;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
