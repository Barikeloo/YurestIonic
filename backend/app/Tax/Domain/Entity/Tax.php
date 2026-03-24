<?php

namespace App\Tax\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class Tax
{
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

        return new self(
            id: Uuid::generate(),
            name: $name,
            percentage: $percentage,
            createdAt: $now,
            updatedAt: $now,
        );
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

    public function update(TaxName $name, TaxPercentage $percentage): void
    {
        $this->name = $name;
        $this->percentage = $percentage;
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name->value();
    }

    public function percentage(): int
    {
        return $this->percentage->value();
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
