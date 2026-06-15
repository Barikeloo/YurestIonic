<?php

namespace App\Product\Domain\Entity;

use App\Product\Domain\Event\ProductActivated;
use App\Product\Domain\Event\ProductCreated;
use App\Product\Domain\Event\ProductDeactivated;
use App\Product\Domain\Event\ProductDeleted;
use App\Product\Domain\Event\ProductPhotoUpdated;
use App\Product\Domain\Event\ProductPriceChanged;
use App\Product\Domain\Event\ProductUpdated;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\ValueObject\ProductAllergens;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Product
{
    use RecordsEvents;
    private function __construct(
        private Uuid $id,
        private Uuid $familyId,
        private Uuid $taxId,
        private ProductImageSrc $imageSrc,
        private ProductName $name,
        private ProductPrice $price,
        private ProductStock $stock,
        private bool $active,
        private ProductAllergens $allergens,
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
        ?ProductAllergens $allergens = null,
    ): self {
        $now = DomainDateTime::now();

        $product = new self(
            id: Uuid::generate(),
            familyId: $familyId,
            taxId: $taxId,
            imageSrc: $imageSrc,
            name: $name,
            price: $price,
            stock: $stock,
            active: $active,
            allergens: $allergens ?? ProductAllergens::empty(),
            createdAt: $now,
            updatedAt: $now,
        );

        $product->recordEvent(new ProductCreated(
            productId: $product->id->value(),
            productName: $product->name->value(),
            priceCents: $product->price->value(),
            familyId: $product->familyId->value(),
            active: $product->active,
        ));

        return $product;
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
        array $allergens,
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
            allergens: ProductAllergens::create($allergens),
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
        ?ProductAllergens $allergens = null,
    ): void {
        $before = $this->snapshot();
        $oldPriceCents = $this->price->value();

        $this->familyId = $familyId;
        $this->taxId = $taxId;
        $this->imageSrc = $imageSrc;
        $this->name = $name;
        $this->price = $price;
        $this->stock = $stock;
        $this->active = $active;

        if ($allergens !== null) {
            $this->allergens = $allergens;
        }

        $after = $this->snapshot();

        if ($before === $after) {
            return;
        }

        $this->touch();

        $this->recordEvent(new ProductUpdated(
            productId: $this->id->value(),
            before: $before,
            after: $after,
        ));

        if ($this->price->value() !== $oldPriceCents) {
            $this->recordEvent(new ProductPriceChanged(
                productId: $this->id->value(),
                productName: $this->name->value(),
                oldPriceCents: $oldPriceCents,
                newPriceCents: $this->price->value(),
            ));
        }
    }

    public function delete(): void
    {
        $this->recordEvent(new ProductDeleted(
            productId: $this->id->value(),
            productName: $this->name->value(),
            priceCents: $this->price->value(),
        ));
    }

    public function changeImage(ProductImageSrc $imageSrc): void
    {
        $this->imageSrc = $imageSrc;
        $this->touch();
        $this->recordEvent(new ProductPhotoUpdated(
            productId: $this->id->value(),
            productName: $this->name->value(),
            imageSrc: $this->imageSrc->value(),
        ));
    }

    public function activate(): void
    {
        if (! $this->active) {
            $this->active = true;
            $this->touch();
            $this->recordEvent(new ProductActivated(
                productId: $this->id->value(),
                productName: $this->name->value(),
            ));
        }
    }

    public function deactivate(): void
    {
        if ($this->active) {
            $this->active = false;
            $this->touch();
            $this->recordEvent(new ProductDeactivated(
                productId: $this->id->value(),
                productName: $this->name->value(),
            ));
        }
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function familyId(): Uuid
    {
        return $this->familyId;
    }

    public function taxId(): Uuid
    {
        return $this->taxId;
    }

    public function imageSrc(): ProductImageSrc
    {
        return $this->imageSrc;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function price(): ProductPrice
    {
        return $this->price;
    }

    public function stock(): ProductStock
    {
        return $this->stock;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function allergens(): ProductAllergens
    {
        return $this->allergens;
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

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return array{name: string, price_cents: int, family_id: string, tax_id: string, active: bool, allergens: array, image_src: ?string}
     */
    private function snapshot(): array
    {
        return [
            'name'       => $this->name->value(),
            'price_cents'=> $this->price->value(),
            'family_id'  => $this->familyId->value(),
            'tax_id'     => $this->taxId->value(),
            'active'     => $this->active,
            'allergens'  => $this->allergens->values(),
            'image_src'  => $this->imageSrc->value(),
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
