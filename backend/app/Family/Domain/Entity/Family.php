<?php

namespace App\Family\Domain\Entity;

use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Family
{
    private function __construct(
        private Uuid $id,
        private FamilyName $name,
        private bool $active,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(FamilyName $name): self
    {
        $now = DomainDateTime::now();

        return new self(
            id: Uuid::generate(),
            name: $name,
            active: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $name,
        bool $active,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            name: FamilyName::create($name),
            active: $active,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function rename(FamilyName $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function activate(): void
    {
        if (! $this->active) {
            $this->active = true;
            $this->touch();
        }
    }

    public function deactivate(): void
    {
        if ($this->active) {
            $this->active = false;
            $this->touch();
        }
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name->value();
    }

    public function isActive(): bool
    {
        return $this->active;
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
