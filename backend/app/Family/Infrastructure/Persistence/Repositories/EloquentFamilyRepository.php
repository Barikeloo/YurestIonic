<?php

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Support\Str;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function __construct(
        private EloquentFamily $model,
    ) {}

    public function save(Family $family): void
    {
        $restaurantId = $this->defaultRestaurantId();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $family->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'name' => $family->name(),
                'active' => $family->isActive(),
                'created_at' => $family->createdAt()->value(),
                'updated_at' => $family->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Family
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        if ($model === null) {
            return null;
        }

        return Family::fromPersistence(
            id: $model->uuid,
            name: $model->name,
            active: (bool) $model->active,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findAll(): array
    {
        $models = $this->model->newQuery()->orderBy('name')->get();

        return $models->map(static fn (EloquentFamily $model): Family => Family::fromPersistence(
            id: $model->uuid,
            name: $model->name,
            active: (bool) $model->active,
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
