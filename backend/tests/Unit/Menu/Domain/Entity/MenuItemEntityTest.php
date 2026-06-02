<?php

namespace Tests\Unit\Menu\Domain\Entity;

use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class MenuItemEntityTest extends TestCase
{
    public function test_ddd_create_builds_item(): void
    {
        $sectionId = Uuid::generate();
        $productId = Uuid::generate();
        $variantId = Uuid::generate();

        $item = MenuItem::dddCreate(
            sectionId: $sectionId,
            productId: $productId,
            variantId: $variantId,
            extraPrice: MenuItemExtraPrice::create(200),
            position: 1,
        );

        $this->assertInstanceOf(Uuid::class, $item->id());
        $this->assertSame($sectionId->value(), $item->sectionId()->value());
        $this->assertSame($productId->value(), $item->productId()->value());
        $this->assertSame($variantId->value(), $item->variantId()?->value());
        $this->assertSame(200, $item->extraPrice()->value());
        $this->assertSame(1, $item->position());
    }

    public function test_ddd_create_without_variant(): void
    {
        $item = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );

        $this->assertNull($item->variantId());
        $this->assertTrue($item->extraPrice()->isZero());
    }

    public function test_from_persistence_rebuilds_item(): void
    {
        $id = Uuid::generate()->value();
        $sectionId = Uuid::generate()->value();
        $productId = Uuid::generate()->value();

        $item = MenuItem::fromPersistence(
            id: $id,
            sectionId: $sectionId,
            productId: $productId,
            variantId: null,
            extraPrice: 150,
            position: 2,
        );

        $this->assertSame($id, $item->id()->value());
        $this->assertSame($sectionId, $item->sectionId()->value());
        $this->assertSame($productId, $item->productId()->value());
        $this->assertNull($item->variantId());
        $this->assertSame(150, $item->extraPrice()->value());
        $this->assertSame(2, $item->position());
    }

    public function test_from_persistence_with_variant(): void
    {
        $variantId = Uuid::generate()->value();

        $item = MenuItem::fromPersistence(
            id: Uuid::generate()->value(),
            sectionId: Uuid::generate()->value(),
            productId: Uuid::generate()->value(),
            variantId: $variantId,
            extraPrice: 0,
            position: 0,
        );

        $this->assertSame($variantId, $item->variantId()?->value());
    }
}
