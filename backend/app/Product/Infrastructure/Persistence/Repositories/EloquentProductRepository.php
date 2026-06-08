<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Persistence\Repositories;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private EloquentProduct $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(Product $product): void
    {
        $family = EloquentFamily::query()->where('uuid', $product->familyId()->value())->firstOrFail();
        $tax = EloquentTax::query()->where('uuid', $product->taxId()->value())->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $product->id()->value()],
            [
                'restaurant_id' => $family->restaurant_id,
                'family_id' => $family->id,
                'tax_id' => $tax->id,
                'image_src' => $product->imageSrc()->value(),
                'name' => $product->name()->value(),
                'price' => $product->price()->value(),
                'stock' => $product->stock()->value(),
                'active' => $product->isActive(),
                'allergens' => $product->allergens()->values(),
                'created_at' => $product->createdAt()->value(),
                'updated_at' => $product->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Product
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $model = $this->model->newQuery()
            ->with(['family', 'tax'])
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $id)
            ->first();

        if ($model === null || $model->family === null || $model->tax === null) {
            return null;
        }

        return Product::fromPersistence(
            id: $model->uuid,
            familyId: $model->family->uuid,
            taxId: $model->tax->uuid,
            imageSrc: $model->image_src,
            name: $model->name,
            price: (int) $model->price,
            stock: (int) $model->stock,
            active: (bool) $model->active,
            allergens: is_array($model->allergens) ? $model->allergens : [],
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findByIdAndRestaurant(string $id, string $restaurantUuid): ?Product
    {
        $model = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->with(['family', 'tax'])
            ->where('uuid', $id)
            ->whereNull('deleted_at')
            ->whereExists(static function ($query) use ($restaurantUuid): void {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('restaurants')
                    ->whereColumn('restaurants.id', 'products.restaurant_id')
                    ->where('restaurants.uuid', $restaurantUuid);
            })
            ->first();

        if ($model === null || $model->family === null || $model->tax === null) {
            return null;
        }

        return Product::fromPersistence(
            id: $model->uuid,
            familyId: $model->family->uuid,
            taxId: $model->tax->uuid,
            imageSrc: $model->image_src,
            name: $model->name,
            price: (int) $model->price,
            stock: (int) $model->stock,
            active: (bool) $model->active,
            allergens: is_array($model->allergens) ? $model->allergens : [],
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findAll(bool $includeDeleted = false): array
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $query = $this->model->newQuery()
            ->with(['family', 'tax'])
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name');

        if ($includeDeleted) {
            $query->withTrashed();
        }

        $models = $query->get();

        return $models
            ->filter(static fn (EloquentProduct $model): bool => $model->family !== null && $model->tax !== null)
            ->map(static fn (EloquentProduct $model): Product => Product::fromPersistence(
                id: $model->uuid,
                familyId: $model->family->uuid,
                taxId: $model->tax->uuid,
                imageSrc: $model->image_src,
                name: $model->name,
                price: (int) $model->price,
                stock: (int) $model->stock,
                active: (bool) $model->active,
                allergens: is_array($model->allergens) ? $model->allergens : [],
                createdAt: $model->created_at->toDateTimeImmutable(),
                updatedAt: $model->updated_at->toDateTimeImmutable(),
            ))
            ->values()
            ->all();
    }

    public function deleteById(string $id): bool
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        if ($model === null) {
            return false;
        }

        return (bool) $model->delete();
    }
}
