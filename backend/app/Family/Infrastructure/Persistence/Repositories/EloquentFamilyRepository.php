<?php

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function __construct(
        private EloquentFamily $model,
    ) {}

    public function save(Family $family): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $family->id()->value()],
            [
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
}
