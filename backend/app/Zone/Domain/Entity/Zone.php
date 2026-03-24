<?php

namespace App\Zone\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\ValueObject\ZoneName;

class Zone
{
    private function __construct(
        private Uuid $id,
        private ZoneName $name,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(ZoneName $name): self
    {
        $now = DomainDateTime::now();

        return new self(
            id: Uuid::generate(),
            name: $name,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $name,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            name: ZoneName::create($name),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function rename(ZoneName $name): void
    {
        $this->name = $name;
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
