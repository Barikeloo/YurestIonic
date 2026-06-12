<?php

namespace App\Family\Domain\Entity;

use App\Family\Domain\Event\FamilyCreated;
use App\Family\Domain\Event\FamilyDeleted;
use App\Family\Domain\Event\FamilyUpdated;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Family
{
    use RecordsEvents;

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

        $family = new self(
            id: Uuid::generate(),
            name: $name,
            active: true,
            createdAt: $now,
            updatedAt: $now,
        );

        $family->recordEvent(new FamilyCreated(
            familyId: $family->id->value(),
            name: $family->name->value(),
        ));

        return $family;
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
        $before = ['name' => $this->name->value()];

        $this->name = $name;
        $this->touch();

        $this->recordEvent(new FamilyUpdated(
            familyId: $this->id->value(),
            before: $before,
            after: ['name' => $this->name->value()],
        ));
    }

    public function delete(): void
    {
        $this->recordEvent(new FamilyDeleted(
            familyId: $this->id->value(),
            name: $this->name->value(),
        ));
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

    public function name(): FamilyName
    {
        return $this->name;
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
