<?php

namespace App\ProductModifier\Infrastructure\Persistence\Repositories;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductModifier\Infrastructure\Persistence\Models\EloquentProductModifier;
use App\Shared\Infrastructure\Tenant\TenantContext;

class EloquentProductModifierRepository implements ProductModifierRepositoryInterface
{
    public function __construct(
        private EloquentProductModifier $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(ProductModifier $modifier): void
    {
        $product = EloquentProduct::query()
            ->where('uuid', $modifier->productId()->value())
            ->where('restaurant_id', $this->tenantContext->requireRestaurantId())
            ->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $modifier->id()->value()],
            [
                'product_id' => $product->id,
                'name' => $modifier->name()->value(),
                'type' => $modifier->type()->value(),
                'is_required' => $modifier->isRequired(),
                'selection_type' => $modifier->selectionType()->value(),
                'price' => $modifier->price()->value(),
                'active' => $modifier->isActive(),
                'sort_order' => $modifier->sortOrder(),
                'created_at' => $modifier->createdAt()->value(),
                'updated_at' => $modifier->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?ProductModifier
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $model = $this->model->newQuery()
            ->whereHas('product', function ($query) use ($restaurantId): void {
                $query->where('restaurant_id', $restaurantId);
            })
            ->where('uuid', $id)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findByProductId(string $productId): array
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $product = EloquentProduct::query()
            ->where('uuid', $productId)
            ->where('restaurant_id', $restaurantId)
            ->first();

        if ($product === null) {
            return [];
        }

        $models = $this->model->newQuery()
            ->where('product_id', $product->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $models
            ->map(fn (EloquentProductModifier $model): ProductModifier => $this->toDomain($model))
            ->values()
            ->all();
    }

    public function deleteById(string $id): bool
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $model = $this->model->newQuery()
            ->whereHas('product', function ($query) use ($restaurantId): void {
                $query->where('restaurant_id', $restaurantId);
            })
            ->where('uuid', $id)
            ->first();

        if ($model === null) {
            return false;
        }

        return (bool) $model->delete();
    }

    private function toDomain(EloquentProductModifier $model): ProductModifier
    {
        return ProductModifier::fromPersistence(
            id: $model->uuid,
            productId: $model->product->uuid,
            name: $model->name,
            type: $model->type,
            isRequired: (bool) $model->is_required,
            selectionType: $model->selection_type,
            price: (int) $model->price,
            active: (bool) $model->active,
            sortOrder: (int) $model->sort_order,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
