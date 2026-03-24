<?php

namespace App\Product\Domain\Entity;

use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Product
{
    private function __construct(
        private Uuid $id,
        private Uuid $familyId,
        private Uuid $taxId,
        private ProductImageSrc $imageSrc,
        private ProductName $name,
        private ProductPrice $price,
        private ProductStock $stock,
        private bool $active,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $familyId,
        Uuid $taxId,
        ProductImageSrc $imageSrc,
        ProductName $name,
        ProductPrice $price,
        ProductStock $stock,
        bool $active = true,
    ): self {
        $now = DomainDateTime::now();

        return new self(
            id: Uuid::generate(),
            familyId: $familyId,
            taxId: $taxId,
            imageSrc: $imageSrc,
            name: $name,
            price: $price,
            stock: $stock,
            active: $active,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $familyId,
        string $taxId,
        ?string $imageSrc,
        string $name,
        int $price,
        int $stock,
        bool $active,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            familyId: Uuid::create($familyId),
            taxId: Uuid::create($taxId),
            imageSrc: ProductImageSrc::create($imageSrc),
            name: ProductName::create($name),
            price: ProductPrice::create($price),
            stock: ProductStock::create($stock),
            active: $active,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function update(
        Uuid $familyId,
        Uuid $taxId,
        ProductImageSrc $imageSrc,
        ProductName $name,
        ProductPrice $price,
        ProductStock $stock,
        bool $active,
    ): void {
        $this->familyId = $familyId;
        $this->taxId = $taxId;
        $this->imageSrc = $imageSrc;
        $this->name = $name;
        $this->price = $price;
        $this->stock = $stock;
        $this->active = $active;
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

    public function familyId(): string
    {
        return $this->familyId->value();
    }

    public function taxId(): string
    {
        return $this->taxId->value();
    }

    public function imageSrc(): ?string
    {
        return $this->imageSrc->value();
    }

    public function name(): string
    {
        return $this->name->value();
    }

    public function price(): int
    {
        return $this->price->value();
    }

    public function stock(): int
    {
        return $this->stock->value();
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
