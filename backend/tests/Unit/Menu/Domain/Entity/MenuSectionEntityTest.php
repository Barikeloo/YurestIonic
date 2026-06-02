<?php

namespace Tests\Unit\Menu\Domain\Entity;

use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use App\Menu\Domain\ValueObject\MenuSectionName;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class MenuSectionEntityTest extends TestCase
{
    public function test_ddd_create_builds_section(): void
    {
        $menuId = Uuid::generate();
        $item = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );

        $section = MenuSection::dddCreate(
            menuId: $menuId,
            name: MenuSectionName::create('Primer plato'),
            position: 0,
            choiceRule: MenuSectionChoiceRule::chooseOne(),
            items: [$item],
        );

        $this->assertInstanceOf(Uuid::class, $section->id());
        $this->assertSame($menuId->value(), $section->menuId()->value());
        $this->assertSame('Primer plato', $section->name()->value());
        $this->assertSame(0, $section->position());
        $this->assertTrue($section->choiceRule()->isExactlyOne());
        $this->assertCount(1, $section->items());
        $this->assertSame($item->id()->value(), $section->items()[0]->id()->value());
    }

    public function test_ddd_create_with_empty_items_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage("Menu section 'Empty section' must contain at least one item.");

        MenuSection::dddCreate(
            menuId: Uuid::generate(),
            name: MenuSectionName::create('Empty section'),
            position: 0,
            choiceRule: MenuSectionChoiceRule::chooseOne(),
            items: [],
        );
    }

    public function test_from_persistence_rebuilds_section(): void
    {
        $id = Uuid::generate()->value();
        $menuId = Uuid::generate()->value();
        $item = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );

        $section = MenuSection::fromPersistence(
            id: $id,
            menuId: $menuId,
            name: 'Segundo plato',
            position: 1,
            minChoices: 1,
            maxChoices: 2,
            items: [$item],
        );

        $this->assertSame($id, $section->id()->value());
        $this->assertSame($menuId, $section->menuId()->value());
        $this->assertSame('Segundo plato', $section->name()->value());
        $this->assertSame(1, $section->position());
        $this->assertSame(1, $section->choiceRule()->min());
        $this->assertSame(2, $section->choiceRule()->max());
        $this->assertCount(1, $section->items());
    }

    public function test_ddd_create_with_multiple_items(): void
    {
        $item1 = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );
        $item2 = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::create(300),
            position: 1,
        );

        $section = MenuSection::dddCreate(
            menuId: Uuid::generate(),
            name: MenuSectionName::create('Entrantes'),
            position: 0,
            choiceRule: MenuSectionChoiceRule::create(1, 2),
            items: [$item1, $item2],
        );

        $this->assertCount(2, $section->items());
        $this->assertSame(1, $section->choiceRule()->min());
        $this->assertSame(2, $section->choiceRule()->max());
    }
}
