<?php

namespace Tests\Unit\Menu\Shared;

use App\Menu\Application\Shared\MenuView;
use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Menu\Domain\ValueObject\MenuName;
use App\Menu\Domain\ValueObject\MenuPrice;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use App\Menu\Domain\ValueObject\MenuSectionName;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class MenuViewTest extends TestCase
{
    use \Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

    public function test_to_array_returns_full_structure(): void
    {
        $sectionItem = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );
        $section = MenuSection::dddCreate(
            menuId: Uuid::generate(),
            name: MenuSectionName::create('Principal'),
            position: 0,
            choiceRule: MenuSectionChoiceRule::chooseOne(),
            items: [$sectionItem],
        );
        $menu = \App\Menu\Domain\Entity\Menu::dddCreate(
            taxId: Uuid::generate(),
            name: MenuName::create('Test'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::create('Desc'),
            price: MenuPrice::create(1500),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$section],
        );

        $result = MenuView::toArray($menu);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('tax_id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('active', $result);
        $this->assertArrayHasKey('archived', $result);
        $this->assertArrayHasKey('validity_from', $result);
        $this->assertArrayHasKey('validity_to', $result);
        $this->assertArrayHasKey('available_days', $result);
        $this->assertArrayHasKey('available_from_time', $result);
        $this->assertArrayHasKey('available_to_time', $result);
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
        $this->assertArrayHasKey('archived_at', $result);

        $this->assertSame('Test', $result['name']);
        $this->assertSame('Desc', $result['description']);
        $this->assertSame(1500, $result['price']);
        $this->assertTrue($result['active']);
        $this->assertFalse($result['archived']);
        $this->assertNull($result['archived_at']);
        $this->assertNull($result['validity_from']);
        $this->assertNull($result['validity_to']);
        $this->assertSame(127, $result['available_days']);
        $this->assertNull($result['available_from_time']);
        $this->assertNull($result['available_to_time']);

        $this->assertCount(1, $result['sections']);
        $sectionData = $result['sections'][0];
        $this->assertArrayHasKey('id', $sectionData);
        $this->assertArrayHasKey('name', $sectionData);
        $this->assertArrayHasKey('position', $sectionData);
        $this->assertArrayHasKey('min_choices', $sectionData);
        $this->assertArrayHasKey('max_choices', $sectionData);
        $this->assertArrayHasKey('items', $sectionData);
        $this->assertSame('Principal', $sectionData['name']);
        $this->assertSame(1, $sectionData['min_choices']);
        $this->assertSame(1, $sectionData['max_choices']);

        $this->assertCount(1, $sectionData['items']);
        $itemData = $sectionData['items'][0];
        $this->assertArrayHasKey('id', $itemData);
        $this->assertArrayHasKey('product_id', $itemData);
        $this->assertArrayHasKey('variant_id', $itemData);
        $this->assertArrayHasKey('extra_price', $itemData);
        $this->assertArrayHasKey('position', $itemData);
        $this->assertNull($itemData['variant_id']);
        $this->assertSame(0, $itemData['extra_price']);
    }
}
