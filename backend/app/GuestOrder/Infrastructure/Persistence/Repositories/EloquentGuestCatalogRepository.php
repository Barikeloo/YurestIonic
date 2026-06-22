<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\GuestOrder\Domain\Interfaces\GuestCatalogRepositoryInterface;
use App\GuestOrder\Domain\ReadModel\CatalogReadModel;
use App\GuestOrder\Domain\ReadModel\FamilyCatalogItem;
use App\GuestOrder\Domain\ReadModel\MenuCatalogItem;
use App\GuestOrder\Domain\ReadModel\MenuItemCatalogItem;
use App\GuestOrder\Domain\ReadModel\MenuSectionCatalogItem;
use App\GuestOrder\Domain\ReadModel\ModifierCatalogItem;
use App\GuestOrder\Domain\ReadModel\ProductCatalogItem;
use App\GuestOrder\Domain\ReadModel\VariantCatalogItem;
use App\Menu\Infrastructure\Persistence\Models\EloquentMenu;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\ProductModifier\Infrastructure\Persistence\Models\EloquentProductModifier;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;

final class EloquentGuestCatalogRepository implements GuestCatalogRepositoryInterface
{
    public function getCatalog(int $restaurantInternalId, int $catalogVersion): CatalogReadModel
    {
        $families   = $this->loadFamilies($restaurantInternalId);
        $menus      = $this->loadMenus($restaurantInternalId);

        return new CatalogReadModel(
            version: $catalogVersion,
            families: $families,
            menus: $menus,
        );
    }

    public function getCatalogVersion(int $restaurantInternalId): int
    {
        $row = \Illuminate\Support\Facades\DB::table('table_qr_tokens')
            ->where('restaurant_id', $restaurantInternalId)
            ->value('catalog_version');

        return (int) ($row ?? 1);
    }

    private function loadFamilies(int $restaurantInternalId): array
    {
        $families = EloquentFamily::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantInternalId)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        if ($families->isEmpty()) {
            return [];
        }

        $familyIds = $families->pluck('id')->all();

        $products = EloquentProduct::withoutGlobalScopes()
            ->with([
                'variants'  => fn ($q) => $q->where('active', true)->whereNull('deleted_at')->orderBy('sort_order'),
                'modifiers' => fn ($q) => $q->where('active', true)->whereNull('deleted_at')->orderBy('sort_order'),
            ])
            ->whereIn('family_id', $familyIds)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->groupBy('family_id');

        return $families->map(function (EloquentFamily $family) use ($products): FamilyCatalogItem {
            $familyProducts = $products->get($family->id, collect());

            return new FamilyCatalogItem(
                id: $family->uuid,
                name: $family->name,
                icon: $family->icon ?? null,
                color: $family->color ?? null,
                products: $familyProducts->map(fn (EloquentProduct $p): ProductCatalogItem => $this->mapProduct($p))->values()->all(),
            );
        })->all();
    }

    private function mapProduct(EloquentProduct $product): ProductCatalogItem
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants->map(fn (EloquentProductVariant $v): VariantCatalogItem => new VariantCatalogItem(
                id: $v->uuid,
                name: $v->name,
                price_cents: $v->price,
            ))->values()->all()
            : [];

        $modifiers = $product->relationLoaded('modifiers')
            ? $product->modifiers->map(fn (EloquentProductModifier $m): ModifierCatalogItem => new ModifierCatalogItem(
                id: $m->uuid,
                name: $m->name,
                price_cents: $m->price,
                is_required: (bool) $m->is_required,
                selection_type: $m->selection_type,
            ))->values()->all()
            : [];

        return new ProductCatalogItem(
            id: $product->uuid,
            name: $product->name,
            price_cents: $product->price,
            photo_url: $product->image_src ?? null,
            allergens: $product->allergens ?? [],
            available: (bool) ($product->available ?? true),
            variants: $variants,
            modifiers: $modifiers,
        );
    }

    private function loadMenus(int $restaurantInternalId): array
    {
        $menus = EloquentMenu::withoutGlobalScopes()
            ->with([
                'sections.items.product',
                'sections.items.variant',
            ])
            ->where('restaurant_id', $restaurantInternalId)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return $menus->map(fn (EloquentMenu $menu): MenuCatalogItem => new MenuCatalogItem(
            id: $menu->uuid,
            name: $menu->name,
            description: $menu->description,
            price_cents: $menu->price,
            sections: $menu->sections->map(fn ($section): MenuSectionCatalogItem => new MenuSectionCatalogItem(
                id: $section->uuid,
                name: $section->name,
                min_choices: $section->min_choices,
                max_choices: $section->max_choices,
                position: $section->position,
                items: $section->items->map(fn ($item): MenuItemCatalogItem => new MenuItemCatalogItem(
                    id: $item->uuid,
                    product_id: $item->product?->uuid ?? '',
                    product_name: $item->product?->name ?? '',
                    variant_id: $item->variant?->uuid ?? null,
                    variant_name: $item->variant?->name ?? null,
                    extra_price_cents: $item->extra_price,
                    position: $item->position,
                ))->values()->all(),
            ))->values()->all(),
        ))->all();
    }
}
