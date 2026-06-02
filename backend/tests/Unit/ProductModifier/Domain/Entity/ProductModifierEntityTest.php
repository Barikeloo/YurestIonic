<?php

namespace Tests\Unit\ProductModifier\Domain\Entity;

use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\ValueObject\ModifierName;
use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ProductModifierEntityTest extends TestCase
{
    private Uuid $productId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productId = Uuid::generate();
    }

    public function test_ddd_create_builds_modifier_with_accompaniment(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Patatas fritas'),
            type: ModifierType::accompaniment(),
            isRequired: true,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(0),
        );

        $this->assertInstanceOf(ProductModifier::class, $modifier);
        $this->assertInstanceOf(Uuid::class, $modifier->id());
        $this->assertSame($this->productId->value(), $modifier->productId()->value());
        $this->assertSame('Patatas fritas', $modifier->name()->value());
        $this->assertSame('accompaniment', $modifier->type()->value());
        $this->assertTrue($modifier->isRequired());
        $this->assertSame('single', $modifier->selectionType()->value());
        $this->assertSame(0, $modifier->price()->value());
        $this->assertTrue($modifier->isActive());
        $this->assertSame(0, $modifier->sortOrder());
    }

    public function test_ddd_create_with_extra_and_not_required(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
        );

        $this->assertSame('extra', $modifier->type()->value());
        $this->assertFalse($modifier->isRequired());
    }

    public function test_ddd_create_extra_required_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: true,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
        );
    }

    public function test_update_changes_fields_and_updates_timestamp(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Patatas fritas'),
            type: ModifierType::accompaniment(),
            isRequired: true,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(0),
        );

        $originalUpdatedAt = $modifier->updatedAt()->value();

        sleep(1);

        $modifier->update(
            name: ModifierName::create('Aros de cebolla'),
            type: ModifierType::extra(),
            isRequired: false,
            selectionType: ModifierSelectionType::multi(),
            price: ModifierPrice::create(300),
            active: false,
            sortOrder: 2,
        );

        $this->assertSame('Aros de cebolla', $modifier->name()->value());
        $this->assertSame('extra', $modifier->type()->value());
        $this->assertFalse($modifier->isRequired());
        $this->assertSame('multi', $modifier->selectionType()->value());
        $this->assertSame(300, $modifier->price()->value());
        $this->assertFalse($modifier->isActive());
        $this->assertSame(2, $modifier->sortOrder());
        $this->assertGreaterThan($originalUpdatedAt, $modifier->updatedAt()->value());
    }

    public function test_update_rejects_extra_required(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Patatas fritas'),
            type: ModifierType::accompaniment(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(0),
        );

        $this->expectException(\InvalidArgumentException::class);

        $modifier->update(
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: true,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
            active: true,
            sortOrder: 1,
        );
    }

    public function test_reorder_changes_sort_order(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Patatas fritas'),
            type: ModifierType::accompaniment(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(0),
            sortOrder: 0,
        );

        $modifier->reorder(5);

        $this->assertSame(5, $modifier->sortOrder());
    }

    public function test_reorder_same_value_does_not_touch(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Patatas fritas'),
            type: ModifierType::accompaniment(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(0),
            sortOrder: 3,
        );

        $updatedAt = $modifier->updatedAt()->value();
        $modifier->reorder(3);

        $this->assertSame(3, $modifier->sortOrder());
        $this->assertEquals($updatedAt, $modifier->updatedAt()->value());
    }

    public function test_activate(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
            active: false,
        );

        $this->assertFalse($modifier->isActive());

        $modifier->activate();

        $this->assertTrue($modifier->isActive());
    }

    public function test_activate_when_already_active_does_nothing(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
            active: true,
        );

        $updatedAt = $modifier->updatedAt()->value();
        $modifier->activate();

        $this->assertTrue($modifier->isActive());
        $this->assertEquals($updatedAt, $modifier->updatedAt()->value());
    }

    public function test_deactivate(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
            active: true,
        );

        $this->assertTrue($modifier->isActive());

        $modifier->deactivate();

        $this->assertFalse($modifier->isActive());
    }

    public function test_deactivate_when_already_inactive_does_nothing(): void
    {
        $modifier = ProductModifier::dddCreate(
            productId: $this->productId,
            name: ModifierName::create('Extra queso'),
            type: ModifierType::extra(),
            isRequired: false,
            selectionType: ModifierSelectionType::single(),
            price: ModifierPrice::create(200),
            active: false,
        );

        $updatedAt = $modifier->updatedAt()->value();
        $modifier->deactivate();

        $this->assertFalse($modifier->isActive());
        $this->assertEquals($updatedAt, $modifier->updatedAt()->value());
    }

    public function test_from_persistence_rebuilds_entity(): void
    {
        $id = Uuid::generate()->value();
        $productId = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $modifier = ProductModifier::fromPersistence(
            id: $id,
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 1,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame($id, $modifier->id()->value());
        $this->assertSame($productId, $modifier->productId()->value());
        $this->assertSame('Extra queso', $modifier->name()->value());
        $this->assertSame('extra', $modifier->type()->value());
        $this->assertFalse($modifier->isRequired());
        $this->assertSame('single', $modifier->selectionType()->value());
        $this->assertSame(200, $modifier->price()->value());
        $this->assertTrue($modifier->isActive());
        $this->assertSame(1, $modifier->sortOrder());
        $this->assertEquals($now, $modifier->createdAt()->value());
        $this->assertEquals($now, $modifier->updatedAt()->value());
    }
}
