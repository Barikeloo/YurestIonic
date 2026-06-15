<?php

namespace Tests\Unit\Product;

use App\Product\Domain\Entity\Product;
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

    public function test_decrease_stock_reduces_amount(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Stock Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(10),
        );

        $product->decreaseStock(3);

        $this->assertSame(7, $product->stock()->value());
    }

    public function test_decrease_stock_with_insufficient_amount_throws_exception(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Stock Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );

        $this->expectException(InsufficientStockException::class);

        $product->decreaseStock(10);
    }

    public function test_increase_stock_adds_amount(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Stock Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );

        $product->increaseStock(10);

        $this->assertSame(15, $product->stock()->value());
    }

    public function test_increase_stock_with_negative_amount_throws_exception(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Stock Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );

        $this->expectException(\InvalidArgumentException::class);

        $product->increaseStock(-1);
    }

    public function test_from_persistence_reconstitutes_entity(): void
    {
        $now = new \DateTimeImmutable('2026-06-01 12:00:00');

        $product = Product::fromPersistence(
            id: '00000000-0000-4000-8000-000000000001',
            familyId: '00000000-0000-4000-8000-000000000002',
            taxId: '00000000-0000-4000-8000-000000000003',
            imageSrc: '/images/test.png',
            name: 'Reconstituted',
            price: 500,
            stock: 20,
            active: false,
            allergens: ['gluten'],
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame('00000000-0000-4000-8000-000000000001', $product->id()->value());
        $this->assertSame('Reconstituted', $product->name()->value());
        $this->assertSame(500, $product->price()->value());
        $this->assertSame(20, $product->stock()->value());
        $this->assertFalse($product->isActive());
        $this->assertSame(['gluten'], $product->allergens()->values());
    }

    public function test_activate_when_already_active_does_not_change(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );

        $updatedAt = $product->updatedAt()->value();

        usleep(1);
        $product->activate();

        $this->assertTrue($product->isActive());
        $this->assertEquals($updatedAt, $product->updatedAt()->value());
    }

    public function test_deactivate_when_already_inactive_does_not_change(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
            active: false,
        );

        $updatedAt = $product->updatedAt()->value();

        usleep(1);
        $product->deactivate();

        $this->assertFalse($product->isActive());
        $this->assertEquals($updatedAt, $product->updatedAt()->value());
    }

    // --- Domain events ---

    public function test_dddCreate_records_ProductCreated_event(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Bebida'),
            price: ProductPrice::create(150),
            stock: ProductStock::create(0),
        );

        $events = $product->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductCreated::class, $events[0]);
        $this->assertSame($product->id()->value(), $events[0]->auditEntityId());
        $this->assertSame('product.created', $events[0]->auditSlug());
    }

    public function test_update_records_ProductUpdated_when_something_changes(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Original'),
            price: ProductPrice::create(500),
            stock: ProductStock::create(1),
        );
        $product->pullDomainEvents();

        $product->update(
            familyId: $product->familyId(),
            taxId: $product->taxId(),
            imageSrc: $product->imageSrc(),
            name: ProductName::create('Actualizado'),
            price: $product->price(),
            stock: $product->stock(),
            active: $product->isActive(),
        );

        $events = $product->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductUpdated::class, $events[0]);
    }

    public function test_update_records_both_ProductUpdated_and_ProductPriceChanged_when_price_changes(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Producto'),
            price: ProductPrice::create(1000),
            stock: ProductStock::create(1),
        );
        $product->pullDomainEvents();

        $product->update(
            familyId: $product->familyId(),
            taxId: $product->taxId(),
            imageSrc: $product->imageSrc(),
            name: $product->name(),
            price: ProductPrice::create(2500),
            stock: $product->stock(),
            active: $product->isActive(),
        );

        $events = $product->pullDomainEvents();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(ProductUpdated::class, $events[0]);
        $this->assertInstanceOf(ProductPriceChanged::class, $events[1]);
    }

    public function test_update_emits_no_events_when_nothing_changes(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Mismo'),
            price: ProductPrice::create(300),
            stock: ProductStock::create(5),
        );
        $product->pullDomainEvents();

        $product->update(
            familyId: $product->familyId(),
            taxId: $product->taxId(),
            imageSrc: $product->imageSrc(),
            name: $product->name(),
            price: $product->price(),
            stock: $product->stock(),
            active: $product->isActive(),
            allergens: ProductAllergens::empty(),
        );

        $this->assertEmpty($product->pullDomainEvents());
    }

    public function test_delete_records_ProductDeleted_event(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Borrable'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(0),
        );
        $product->pullDomainEvents();

        $product->delete();

        $events = $product->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductDeleted::class, $events[0]);
    }

    public function test_activate_records_ProductActivated_event(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Inactivo'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(0),
            active: false,
        );
        $product->pullDomainEvents();

        $product->activate();

        $events = $product->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductActivated::class, $events[0]);
    }

    public function test_activate_emits_no_event_if_already_active(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Activo'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(0),
            active: true,
        );
        $product->pullDomainEvents();

        $product->activate();

        $this->assertEmpty($product->pullDomainEvents());
    }

    public function test_deactivate_records_ProductDeactivated_event(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Activo'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(0),
            active: true,
        );
        $product->pullDomainEvents();

        $product->deactivate();

        $events = $product->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductDeactivated::class, $events[0]);
    }

    public function test_changeImage_records_ProductPhotoUpdated_event(): void
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Con foto'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(0),
        );
        $product->pullDomainEvents();

        $product->changeImage(ProductImageSrc::create('https://cdn.example.com/img.jpg'));

        $events = $product->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductPhotoUpdated::class, $events[0]);
    }
}
