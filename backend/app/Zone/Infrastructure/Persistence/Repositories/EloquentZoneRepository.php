<?php

namespace App\Zone\Infrastructure\Persistence\Repositories;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;

class EloquentZoneRepository implements ZoneRepositoryInterface
{
    public function __construct(
        private EloquentZone $model,
    ) {}

    public function save(Zone $zone): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $zone->id()->value()],
            [
                'name' => $zone->name(),
                'created_at' => $zone->createdAt()->value(),
                'updated_at' => $zone->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Zone
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        if ($model === null) {
            return null;
        }

        return Zone::fromPersistence(
            id: $model->uuid,
            name: $model->name,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findAll(): array
    {
        $models = $this->model->newQuery()->orderBy('name')->get();

        return $models->map(static fn (EloquentZone $model): Zone => Zone::fromPersistence(
            id: $model->uuid,
            name: $model->name,
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
