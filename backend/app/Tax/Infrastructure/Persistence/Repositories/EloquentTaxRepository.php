<?php

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function __construct(
        private EloquentTax $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(Tax $tax): void
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $tax->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'name' => $tax->name()->value(),
                'percentage' => $tax->percentage()->value(),
                'created_at' => $tax->createdAt()->value(),
                'updated_at' => $tax->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Tax
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $model = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $id)
            ->first();

        if ($model === null) {
            return null;
        }

        return Tax::fromPersistence(
            id: $model->uuid,
            name: $model->name,
            percentage: (int) $model->percentage,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findAll(bool $includeDeleted = false): array
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $query = $this->model->newQuery()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('name');

        if ($includeDeleted) {
            $query->withTrashed();
        }

        $models = $query->get();

        return $models->map(static fn (EloquentTax $model): Tax => Tax::fromPersistence(
            id: $model->uuid,
            name: $model->name,
            percentage: (int) $model->percentage,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        ))->all();
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
