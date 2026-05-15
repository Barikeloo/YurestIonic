<?php

namespace App\ProductVariant\Infrastructure\Persistence\Repositories;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\ProductVariant\Infrastructure\Persistence\Models\EloquentProductVariant;
use App\Shared\Infrastructure\Tenant\TenantContext;

class EloquentProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function __construct(
        private EloquentProductVariant $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(ProductVariant $variant): void
    {
        $product = EloquentProduct::query()
            ->where('uuid', $variant->productId()->value())
            ->where('restaurant_id', $this->tenantContext->requireRestaurantId())
            ->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $variant->id()->value()],
            [
                'product_id' => $product->id,
                'name' => $variant->name()->value(),
                'price' => $variant->price()->value(),
                'stock' => $variant->stock()->value(),
                'active' => $variant->isActive(),
                'sort_order' => $variant->sortOrder(),
                'created_at' => $variant->createdAt()->value(),
                'updated_at' => $variant->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?ProductVariant
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
            ->map(fn (EloquentProductVariant $model): ProductVariant => $this->toDomain($model))
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

    private function toDomain(EloquentProductVariant $model): ProductVariant
    {
        return ProductVariant::fromPersistence(
            id: $model->uuid,
            productId: $model->product->uuid,
            name: $model->name,
            price: (int) $model->price,
            stock: (int) $model->stock,
            active: (bool) $model->active,
            sortOrder: (int) $model->sort_order,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
