<?php

declare(strict_types=1);

namespace App\Menu\Application\Shared;

use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Entity\MenuSection;

/**
 * Convierte un Menu del dominio en su representación serializable para HTTP.
 *
 * Es un helper estático para mantener todas las Responses (Create/Update/Get/List)
 * con el mismo shape sin duplicar código.
 */
final class MenuView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Menu $menu): array
    {
        $validity = $menu->validity();
        $availability = $menu->availability();

        return [
            'id' => $menu->id()->value(),
            'tax_id' => $menu->taxId()->value(),
            'name' => $menu->name()->value(),
            'description' => $menu->description()->value(),
            'price' => $menu->price()->value(),
            'active' => $menu->isActive(),
            'archived' => $menu->isArchived(),
            'validity_from' => $validity->from()?->format('Y-m-d'),
            'validity_to' => $validity->to()?->format('Y-m-d'),
            'available_days' => $availability->daysBitmask(),
            'available_from_time' => $availability->fromTime(),
            'available_to_time' => $availability->toTime(),
            'sections' => array_map([self::class, 'sectionToArray'], $menu->sections()),
            'created_at' => $menu->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $menu->updatedAt()->format(\DateTimeInterface::ATOM),
            'archived_at' => $menu->archivedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function sectionToArray(MenuSection $section): array
    {
        return [
            'id' => $section->id()->value(),
            'name' => $section->name()->value(),
            'position' => $section->position(),
            'min_choices' => $section->choiceRule()->min(),
            'max_choices' => $section->choiceRule()->max(),
            'items' => array_map(
                static fn ($item): array => [
                    'id' => $item->id()->value(),
                    'product_id' => $item->productId()->value(),
                    'variant_id' => $item->variantId()?->value(),
                    'extra_price' => $item->extraPrice()->value(),
                    'position' => $item->position(),
                ],
                $section->items(),
            ),
        ];
    }
}
