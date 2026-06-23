<?php

namespace App\Family\Domain\Entity;

use App\Family\Domain\Event\FamilyCreated;
use App\Family\Domain\Event\FamilyDeleted;
use App\Family\Domain\Event\FamilyUpdated;
use App\Family\Domain\ValueObject\FamilyColor;
use App\Family\Domain\ValueObject\FamilyIcon;
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
        private ?FamilyColor $color,
        private ?FamilyIcon $icon,
        private bool $active,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(FamilyName $name, ?FamilyColor $color = null, ?FamilyIcon $icon = null): self
    {
        $now = DomainDateTime::now();

        $family = new self(
            id: Uuid::generate(),
            name: $name,
            color: $color,
            icon: $icon,
            active: true,
            createdAt: $now,
            updatedAt: $now,
        );

        $family->recordEvent(new FamilyCreated(
            familyId: $family->id->value(),
            name: $family->name->value(),
            color: $family->color?->value(),
            icon: $family->icon?->value(),
        ));

        return $family;
    }

    public static function fromPersistence(
        string $id,
        string $name,
        ?string $color,
        ?string $icon,
        bool $active,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            name: FamilyName::create($name),
            color: $color !== null ? FamilyColor::create($color) : null,
            icon: $icon !== null ? FamilyIcon::create($icon) : null,
            active: $active,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function update(FamilyName $name, ?FamilyColor $color, ?FamilyIcon $icon): void
    {
        $before = $this->appearanceSnapshot();

        $this->name = $name;
        $this->color = $color;
        $this->icon = $icon;
        $this->touch();

        $this->recordEvent(new FamilyUpdated(
            familyId: $this->id->value(),
            before: $before,
            after: $this->appearanceSnapshot(),
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

    public function color(): ?FamilyColor
    {
        return $this->color;
    }

    public function icon(): ?FamilyIcon
    {
        return $this->icon;
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

    private function appearanceSnapshot(): array
    {
        return [
            'name' => $this->name->value(),
            'color' => $this->color?->value(),
            'icon' => $this->icon?->value(),
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
