<?php

namespace Tests\Unit\Product;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    public function test_ddd_create_returns_value_objects_and_defaults_to_active(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/images/coke.png'),
            name: ProductName::create('Coca Cola'),
            price: ProductPrice::create(250),
            stock: ProductStock::create(10),
        );

        $this->assertInstanceOf(Uuid::class, $product->id());
        $this->assertInstanceOf(Uuid::class, $product->familyId());
        $this->assertInstanceOf(Uuid::class, $product->taxId());
        $this->assertSame('/images/coke.png', $product->imageSrc()->value());
        $this->assertSame('Coca Cola', $product->name()->value());
        $this->assertSame(250, $product->price()->value());
        $this->assertSame(10, $product->stock()->value());
        $this->assertTrue($product->isActive());
    }

    public function test_update_replaces_all_mutable_fields_with_value_objects(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/images/old.png'),
            name: ProductName::create('Old Name'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(1),
        );

        $newFamilyId = Uuid::generate();
        $newTaxId = Uuid::generate();

        $product->update(
            familyId: $newFamilyId,
            taxId: $newTaxId,
            imageSrc: ProductImageSrc::create('/images/new.png'),
            name: ProductName::create('New Name'),
            price: ProductPrice::create(999),
            stock: ProductStock::create(42),
            active: false,
        );

        $this->assertSame($newFamilyId->value(), $product->familyId()->value());
        $this->assertSame($newTaxId->value(), $product->taxId()->value());
        $this->assertSame('/images/new.png', $product->imageSrc()->value());
        $this->assertSame('New Name', $product->name()->value());
        $this->assertSame(999, $product->price()->value());
        $this->assertSame(42, $product->stock()->value());
        $this->assertFalse($product->isActive());
    }

    public function test_activate_and_deactivate_toggle_active_state(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Toggle Product'),
            price: ProductPrice::create(10),
            stock: ProductStock::create(5),
            active: true,
        );

        $product->deactivate();
        $this->assertFalse($product->isActive());

        $product->activate();
        $this->assertTrue($product->isActive());
    }
}
