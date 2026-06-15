<?php

namespace App\ProductVariant\Domain\Entity;

use App\Product\Domain\Exception\InsufficientStockException;
use App\ProductVariant\Domain\Event\ProductVariantCreated;
use App\ProductVariant\Domain\Event\ProductVariantDeleted;
use App\ProductVariant\Domain\Event\ProductVariantUpdated;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class ProductVariant
{
    use RecordsEvents;
    private function __construct(
        private Uuid $id,
        private Uuid $productId,
        private VariantName $name,
        private VariantPrice $price,
        private VariantStock $stock,
        private bool $active,
        private int $sortOrder,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $productId,
        VariantName $name,
        VariantPrice $price,
        VariantStock $stock,
        bool $active = true,
        int $sortOrder = 0,
    ): self {
        $now = DomainDateTime::now();

        $variant = new self(
            id: Uuid::generate(),
            productId: $productId,
            name: $name,
            price: $price,
            stock: $stock,
            active: $active,
            sortOrder: $sortOrder,
            createdAt: $now,
            updatedAt: $now,
        );

        $variant->recordEvent(new ProductVariantCreated(
            variantId: $variant->id()->value(),
            productId: $productId->value(),
            variantName: $name->value(),
            priceCents: $price->value(),
            stock: $stock->value(),
        ));

        return $variant;
    }

    public static function fromPersistence(
        string $id,
        string $productId,
        string $name,
        int $price,
        int $stock,
        bool $active,
        int $sortOrder,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            productId: Uuid::create($productId),
            name: VariantName::create($name),
            price: VariantPrice::create($price),
            stock: VariantStock::create($stock),
            active: $active,
            sortOrder: $sortOrder,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function update(
        VariantName $name,
        VariantPrice $price,
        VariantStock $stock,
        bool $active,
        int $sortOrder,
    ): void {
        $before = $this->snapshot();

        $this->name = $name;
        $this->price = $price;
        $this->stock = $stock;
        $this->active = $active;
        $this->sortOrder = $sortOrder;
        $this->touch();

        $this->recordEvent(new ProductVariantUpdated(
            variantId: $this->id()->value(),
            before: $before,
            after: $this->snapshot(),
        ));
    }

    public function delete(): void
    {
        $this->recordEvent(new ProductVariantDeleted(
            variantId: $this->id()->value(),
            productId: $this->productId()->value(),
            variantName: $this->name()->value(),
            priceCents: $this->price()->value(),
            stock: $this->stock()->value(),
            active: $this->isActive(),
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

    public function decreaseStock(int $amount): void
    {
        if (! $this->stock->isSufficientFor($amount)) {
            throw InsufficientStockException::forProduct(
                $this->id->value(),
                $this->stock->value(),
                $amount,
            );
        }

        $this->stock = $this->stock->decrease($amount);
        $this->touch();
    }

    public function increaseStock(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be greater than or equal to 0.');
        }

        $this->stock = $this->stock->increase($amount);
        $this->touch();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function name(): VariantName
    {
        return $this->name;
    }

    public function price(): VariantPrice
    {
        return $this->price;
    }

    public function stock(): VariantStock
    {
        return $this->stock;
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

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }

    private function snapshot(): array
    {
        return [
            'name'       => $this->name->value(),
            'price'      => $this->price->value(),
            'stock'      => $this->stock->value(),
            'active'     => $this->active,
            'sort_order' => $this->sortOrder,
        ];
    }
}
