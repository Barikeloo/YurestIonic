<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Persistence\Repositories;

use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Menu\Infrastructure\Persistence\Models\EloquentMenu;
use App\Menu\Infrastructure\Persistence\Models\EloquentMenuItem;
use App\Menu\Infrastructure\Persistence\Models\EloquentMenuSection;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;
use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Support\Facades\DB;

class EloquentMenuRepository implements MenuRepositoryInterface
{
    public function __construct(
        private EloquentMenu $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(Menu $menu): void
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $tax = EloquentTax::query()->where('uuid', $menu->taxId()->value())->firstOrFail();

        [$productIdMap, $variantIdMap] = $this->resolveProductsAndVariants($menu);

        DB::transaction(function () use ($menu, $restaurantId, $tax, $productIdMap, $variantIdMap): void {
            $availability = $menu->availability();
            $validity = $menu->validity();

            $values = [
                'restaurant_id' => $restaurantId,
                'tax_id' => $tax->id,
                'name' => $menu->name()->value(),
                'description' => $menu->description()->value(),
                'price' => $menu->price()->value(),
                'active' => $menu->isActive(),
                'validity_from' => $validity->from()?->format('Y-m-d'),
                'validity_to' => $validity->to()?->format('Y-m-d'),
                'available_days' => $availability->daysBitmask(),
                'available_from_time' => $availability->fromTime(),
                'available_to_time' => $availability->toTime(),
                'created_at' => $menu->createdAt()->value(),
                'updated_at' => $menu->updatedAt()->value(),
                'deleted_at' => $menu->archivedAt()?->value(),
            ];

            $menuModel = $this->model->newQuery()->withTrashed()->updateOrCreate(
                ['uuid' => $menu->id()->value()],
                $values,
            );

            EloquentMenuSection::query()->where('menu_id', $menuModel->id)->delete();

            foreach ($menu->sections() as $section) {
                $sectionModel = EloquentMenuSection::query()->create([
                    'menu_id' => $menuModel->id,
                    'uuid' => $section->id()->value(),
                    'name' => $section->name()->value(),
                    'position' => $section->position(),
                    'min_choices' => $section->choiceRule()->min(),
                    'max_choices' => $section->choiceRule()->max(),
                ]);

                foreach ($section->items() as $item) {
                    EloquentMenuItem::query()->create([
                        'section_id' => $sectionModel->id,
                        'uuid' => $item->id()->value(),
                        'product_id' => $productIdMap[$item->productId()->value()],
                        'variant_id' => $item->variantId() !== null
                            ? $variantIdMap[$item->variantId()->value()] ?? null
                            : null,
                        'extra_price' => $item->extraPrice()->value(),
                        'position' => $item->position(),
                    ]);
                }
            }
        });
    }

    public function findById(string $id, bool $includeArchived = true): ?Menu
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $query = $this->model->newQuery()
            ->with(['tax', 'sections.items.product', 'sections.items.variant'])
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $id);

        if ($includeArchived) {
            $query->withTrashed();
        }

        $model = $query->first();

        if ($model === null || $model->tax === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findAllByCurrentRestaurant(array $filters = []): array
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $query = $this->model->newQuery()
            ->with(['tax', 'sections.items.product', 'sections.items.variant'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name');

        $archivedFilter = $filters['archived'] ?? null;
        if ($archivedFilter === true) {
            $query->onlyTrashed();
        } elseif ($archivedFilter === false) {

        } else {
            $query->withTrashed();
        }

        if (isset($filters['active']) && is_bool($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['search']).'%';
            $query->where('name', 'LIKE', $search);
        }

        return $query->get()
            ->filter(fn (EloquentMenu $m) => $m->tax !== null)
            ->map(fn (EloquentMenu $m) => $this->toDomain($m))
            ->values()
            ->all();
    }

    public function existsById(string $id): bool
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        return $this->model->newQuery()->withTrashed()
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $id)
            ->exists();
    }

    private function resolveProductsAndVariants(Menu $menu): array
    {
        $productUuids = [];
        $variantUuids = [];
        foreach ($menu->sections() as $section) {
            foreach ($section->items() as $item) {
                $productUuids[$item->productId()->value()] = true;
                if ($item->variantId() !== null) {
                    $variantUuids[$item->variantId()->value()] = true;
                }
            }
        }
        $productUuids = array_keys($productUuids);
        $variantUuids = array_keys($variantUuids);

        $productIdMap = EloquentProduct::query()
            ->whereIn('uuid', $productUuids)
            ->pluck('id', 'uuid')
            ->all();

        $variantIdMap = $variantUuids === []
            ? []
            : EloquentProductVariant::query()
                ->whereIn('uuid', $variantUuids)
                ->pluck('id', 'uuid')
                ->all();

        foreach ($productUuids as $uuid) {
            if (! isset($productIdMap[$uuid])) {
                throw new \RuntimeException("Product with uuid {$uuid} not found.");
            }
        }
        foreach ($variantUuids as $uuid) {
            if (! isset($variantIdMap[$uuid])) {
                throw new \RuntimeException("Product variant with uuid {$uuid} not found.");
            }
        }

        return [$productIdMap, $variantIdMap];
    }

    private function toDomain(EloquentMenu $model): Menu
    {
        $sections = [];
        foreach ($model->sections as $sectionModel) {
            $items = [];
            foreach ($sectionModel->items as $itemModel) {
                if ($itemModel->product === null) {

                    continue;
                }
                $items[] = MenuItem::fromPersistence(
                    id: $itemModel->uuid,
                    sectionId: $sectionModel->uuid,
                    productId: $itemModel->product->uuid,
                    variantId: $itemModel->variant?->uuid,
                    extraPrice: (int) $itemModel->extra_price,
                    position: (int) $itemModel->position,
                );
            }

            if ($items === []) {
                continue;
            }

            $sections[] = MenuSection::fromPersistence(
                id: $sectionModel->uuid,
                menuId: $model->uuid,
                name: $sectionModel->name,
                position: (int) $sectionModel->position,
                minChoices: (int) $sectionModel->min_choices,
                maxChoices: (int) $sectionModel->max_choices,
                items: $items,
            );
        }

        return Menu::fromPersistence(
            id: $model->uuid,
            taxId: $model->tax->uuid,
            name: $model->name,
            description: $model->description,
            price: (int) $model->price,
            active: (bool) $model->active,
            validityFrom: $model->validity_from?->toDateTimeImmutable(),
            validityTo: $model->validity_to?->toDateTimeImmutable(),
            availableDays: (int) $model->available_days,
            availableFromTime: $model->available_from_time,
            availableToTime: $model->available_to_time,
            sections: $sections,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
            archivedAt: $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
