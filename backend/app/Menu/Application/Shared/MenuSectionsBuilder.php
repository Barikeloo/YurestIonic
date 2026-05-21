<?php

declare(strict_types=1);

namespace App\Menu\Application\Shared;

use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use App\Menu\Domain\ValueObject\MenuSectionName;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Convierte la lista de MenuSectionInput recibida del controlador
 * en entidades de dominio (MenuSection con sus MenuItems).
 *
 * El menuId se asigna después por el aggregate al persistir; aquí usamos
 * un Uuid placeholder porque MenuSection requiere menuId en construcción.
 * Esto refleja la realidad: las secciones pertenecen al menú que las contiene.
 */
final class MenuSectionsBuilder
{
    /**
     * @param  MenuSectionInput[]  $sectionInputs
     * @return MenuSection[]
     */
    public static function build(Uuid $menuId, array $sectionInputs): array
    {
        if ($sectionInputs === []) {
            throw MenuInvalidConfigurationException::emptyMenu();
        }

        $sections = [];
        foreach ($sectionInputs as $sectionInput) {
            $section = MenuSection::dddCreate(
                menuId: $menuId,
                name: MenuSectionName::create($sectionInput->name),
                position: $sectionInput->position,
                choiceRule: MenuSectionChoiceRule::create($sectionInput->minChoices, $sectionInput->maxChoices),
                items: self::buildItems($sectionInput),
            );
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * @return MenuItem[]
     */
    private static function buildItems(MenuSectionInput $sectionInput): array
    {
        if ($sectionInput->items === []) {
            throw MenuInvalidConfigurationException::emptySection($sectionInput->name);
        }

        $items = [];
        foreach ($sectionInput->items as $itemInput) {
            $items[] = MenuItem::dddCreate(
                sectionId: Uuid::generate(), // placeholder; el repositorio re-vincula vía la sección padre
                productId: Uuid::create($itemInput->productId),
                variantId: $itemInput->variantId !== null ? Uuid::create($itemInput->variantId) : null,
                extraPrice: MenuItemExtraPrice::create($itemInput->extraPrice),
                position: $itemInput->position,
            );
        }

        return $items;
    }
}
