<?php

namespace Tests\Unit\Menu\Domain\Entity;

use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use App\Menu\Domain\ValueObject\MenuSectionName;
use App\Shared\Domain\ValueObject\Uuid;

trait MenuEntityTestHelper
{
    private function createSection(string $name = 'Sección'): MenuSection
    {
        $item = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );

        return MenuSection::dddCreate(
            menuId: Uuid::generate(),
            name: MenuSectionName::create($name),
            position: 0,
            choiceRule: MenuSectionChoiceRule::chooseOne(),
            items: [$item],
        );
    }
}
