<?php

namespace App\Product\Infrastructure\Persistence\Repositories;

use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private EloquentProduct $model,
    ) {}

    public function save(Product $product): void
    {
        $family = EloquentFamily::query()->where('uuid', $product->familyId())->firstOrFail();
        $tax = EloquentTax::query()->where('uuid', $product->taxId())->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $product->id()->value()],
            [
                'family_id' => $family->id,
                'tax_id' => $tax->id,
                'image_src' => $product->imageSrc(),
                'name' => $product->name(),
                'price' => $product->price(),
                'stock' => $product->stock(),
                'active' => $product->isActive(),
                'created_at' => $product->createdAt()->value(),
                'updated_at' => $product->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Product
    {
        $model = $this->model->newQuery()->with(['family', 'tax'])->where('uuid', $id)->first();

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
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findAll(): array
    {
        $models = $this->model->newQuery()->with(['family', 'tax'])->orderBy('name')->get();

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
