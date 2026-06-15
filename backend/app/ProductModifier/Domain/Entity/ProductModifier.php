<?php

namespace App\ProductModifier\Domain\Entity;

use App\ProductModifier\Domain\Event\ProductModifierCreated;
use App\ProductModifier\Domain\Event\ProductModifierDeleted;
use App\ProductModifier\Domain\Event\ProductModifierUpdated;
use App\ProductModifier\Domain\ValueObject\ModifierName;
use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class ProductModifier
{
    use RecordsEvents;
    private function __construct(
        private Uuid $id,
        private Uuid $productId,
        private ModifierName $name,
        private ModifierType $type,
        private bool $isRequired,
        private ModifierSelectionType $selectionType,
        private ModifierPrice $price,
        private bool $active,
        private int $sortOrder,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {
        self::assertConsistent($type, $isRequired);
    }

    private static function assertConsistent(ModifierType $type, bool $isRequired): void
    {
        if ($type->value() === ModifierType::extra()->value() && $isRequired) {
            throw new \InvalidArgumentException('An extra modifier cannot be marked as required.');
        }
    }

    public static function dddCreate(
        Uuid $productId,
        ModifierName $name,
        ModifierType $type,
        bool $isRequired,
        ModifierSelectionType $selectionType,
        ModifierPrice $price,
        bool $active = true,
        int $sortOrder = 0,
    ): self {
        $now = DomainDateTime::now();

        $modifier = new self(
            id: Uuid::generate(),
            productId: $productId,
            name: $name,
            type: $type,
            isRequired: $isRequired,
            selectionType: $selectionType,
            price: $price,
            active: $active,
            sortOrder: $sortOrder,
            createdAt: $now,
            updatedAt: $now,
        );

        $modifier->recordEvent(new ProductModifierCreated(
            modifierId: $modifier->id->value(),
            productId: $modifier->productId->value(),
            modifierName: $modifier->name->value(),
            modifierType: $modifier->type->value(),
            priceCents: $modifier->price->value(),
        ));

        return $modifier;
    }

    public static function fromPersistence(
        string $id,
        string $productId,
        string $name,
        string $type,
        bool $isRequired,
        string $selectionType,
        int $price,
        bool $active,
        int $sortOrder,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            productId: Uuid::create($productId),
            name: ModifierName::create($name),
            type: ModifierType::create($type),
            isRequired: $isRequired,
            selectionType: ModifierSelectionType::create($selectionType),
            price: ModifierPrice::create($price),
            active: $active,
            sortOrder: $sortOrder,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function update(
        ModifierName $name,
        ModifierType $type,
        bool $isRequired,
        ModifierSelectionType $selectionType,
        ModifierPrice $price,
        bool $active,
        int $sortOrder,
    ): void {
        self::assertConsistent($type, $isRequired);

        $before = $this->snapshot();

        $this->name = $name;
        $this->type = $type;
        $this->isRequired = $isRequired;
        $this->selectionType = $selectionType;
        $this->price = $price;
        $this->active = $active;
        $this->sortOrder = $sortOrder;
        $this->touch();

        $this->recordEvent(new ProductModifierUpdated(
            modifierId: $this->id->value(),
            before: $before,
            after: $this->snapshot(),
        ));
    }

    public function delete(): void
    {
        $this->recordEvent(new ProductModifierDeleted(
            modifierId: $this->id->value(),
            productId: $this->productId->value(),
            modifierName: $this->name->value(),
            modifierType: $this->type->value(),
            priceCents: $this->price->value(),
            active: $this->active,
        ));
    }

    public function reorder(int $sortOrder): void
    {
        if ($this->sortOrder === $sortOrder) {
            return;
        }

        $this->sortOrder = $sortOrder;
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

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function name(): ModifierName
    {
        return $this->name;
    }

    public function type(): ModifierType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function selectionType(): ModifierSelectionType
    {
        return $this->selectionType;
    }

    public function price(): ModifierPrice
    {
        return $this->price;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return array{name: string, type: string, is_required: bool, selection_type: string, price: int, active: bool, sort_order: int}
     */
    private function snapshot(): array
    {
        return [
            'name'           => $this->name->value(),
            'type'           => $this->type->value(),
            'is_required'    => $this->isRequired,
            'selection_type' => $this->selectionType->value(),
            'price'          => $this->price->value(),
            'active'         => $this->active,
            'sort_order'     => $this->sortOrder,
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
