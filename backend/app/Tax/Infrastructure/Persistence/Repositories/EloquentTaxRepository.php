<?php

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function __construct(
        private EloquentTax $model,
    ) {}

    public function save(Tax $tax): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $tax->id()->value()],
            [
                'name' => $tax->name(),
                'percentage' => $tax->percentage(),
                'created_at' => $tax->createdAt()->value(),
                'updated_at' => $tax->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Tax
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

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

    public function findAll(): array
    {
        $models = $this->model->newQuery()->orderBy('name')->get();

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
