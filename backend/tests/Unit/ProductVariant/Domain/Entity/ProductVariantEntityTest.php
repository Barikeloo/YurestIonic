<?php

namespace Tests\Unit\ProductVariant\Domain\Entity;

use App\Product\Domain\Exception\InsufficientStockException;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ProductVariantEntityTest extends TestCase
{
    private Uuid $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productId = Uuid::generate();
    }

    public function test_ddd_create_builds_variant(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertInstanceOf(Uuid::class, $variant->id());
        $this->assertSame($this->productId->value(), $variant->productId()->value());
        $this->assertSame('Rojo', $variant->name()->value());
        $this->assertSame(1500, $variant->price()->value());
        $this->assertSame(10, $variant->stock()->value());
        $this->assertTrue($variant->isActive());
        $this->assertSame(0, $variant->sortOrder());
    }

    public function test_ddd_create_with_custom_sort_order(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Azul'),
            price: VariantPrice::create(1000),
            stock: VariantStock::create(5),
            active: false,
            sortOrder: 2,
        );

        $this->assertFalse($variant->isActive());
        $this->assertSame(2, $variant->sortOrder());
    }

    public function test_update_changes_fields_and_updates_timestamp(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $originalUpdatedAt = $variant->updatedAt()->value();

        sleep(1);

        $variant->update(
            name: VariantName::create('Azul'),
            price: VariantPrice::create(2000),
            stock: VariantStock::create(20),
            active: false,
            sortOrder: 1,
        );

        $this->assertSame('Azul', $variant->name()->value());
        $this->assertSame(2000, $variant->price()->value());
        $this->assertSame(20, $variant->stock()->value());
        $this->assertFalse($variant->isActive());
        $this->assertSame(1, $variant->sortOrder());
        $this->assertGreaterThan($originalUpdatedAt, $variant->updatedAt()->value());
    }

    public function test_activate(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
            active: false,
        );

        $this->assertFalse($variant->isActive());

        $variant->activate();

        $this->assertTrue($variant->isActive());
    }

    public function test_activate_when_already_active_does_nothing(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
            active: true,
        );

        $updatedAt = $variant->updatedAt()->value();
        $variant->activate();

        $this->assertTrue($variant->isActive());
        $this->assertEquals($updatedAt, $variant->updatedAt()->value());
    }

    public function test_deactivate(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
            active: true,
        );

        $variant->deactivate();

        $this->assertFalse($variant->isActive());
    }

    public function test_deactivate_when_already_inactive_does_nothing(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
            active: false,
        );

        $updatedAt = $variant->updatedAt()->value();
        $variant->deactivate();

        $this->assertFalse($variant->isActive());
        $this->assertEquals($updatedAt, $variant->updatedAt()->value());
    }

    public function test_decrease_stock(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $variant->decreaseStock(3);

        $this->assertSame(7, $variant->stock()->value());
    }

    public function test_decrease_stock_insufficient_throws_exception(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $this->expectException(InsufficientStockException::class);

        $variant->decreaseStock(11);
    }

    public function test_increase_stock(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $variant->increaseStock(5);

        $this->assertSame(15, $variant->stock()->value());
    }

    public function test_increase_stock_with_negative_throws_exception(): void
    {
        $variant = ProductVariant::dddCreate(
            productId: $this->productId,
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $this->expectException(\InvalidArgumentException::class);

        $variant->increaseStock(-1);
    }

    public function test_from_persistence_rebuilds_entity(): void
    {
        $id = Uuid::generate()->value();
        $productId = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $variant = ProductVariant::fromPersistence(
            id: $id,
            productId: $productId,
            name: 'Rojo',
            price: 1500,
            stock: 10,
            active: true,
            sortOrder: 1,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame($id, $variant->id()->value());
        $this->assertSame($productId, $variant->productId()->value());
        $this->assertSame('Rojo', $variant->name()->value());
        $this->assertSame(1500, $variant->price()->value());
        $this->assertSame(10, $variant->stock()->value());
        $this->assertTrue($variant->isActive());
        $this->assertSame(1, $variant->sortOrder());
        $this->assertEquals($now, $variant->createdAt()->value());
        $this->assertEquals($now, $variant->updatedAt()->value());
    }
}
