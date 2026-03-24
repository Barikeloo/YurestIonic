<?php

namespace App\Tax\Infrastructure\Persistence\Repositories;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Tax\Infrastructure\Persistence\Models\EloquentTax;
use Illuminate\Support\Str;

class EloquentTaxRepository implements TaxRepositoryInterface
{
    public function __construct(
        private EloquentTax $model,
    ) {}

    public function save(Tax $tax): void
    {
        $restaurantId = $this->defaultRestaurantId();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $tax->id()->value()],
            [
                'restaurant_id' => $restaurantId,
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

    private function defaultRestaurantId(): int
    {
        $existingId = EloquentRestaurant::query()->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) EloquentRestaurant::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Default Restaurant',
            'legal_name' => 'Default Restaurant S.L.',
            'tax_id' => 'B00000000',
            'email' => 'default-' . Str::lower(Str::random(8)) . '@local.test',
            'password' => bcrypt('password123'),
        ])->id;
    }
}
