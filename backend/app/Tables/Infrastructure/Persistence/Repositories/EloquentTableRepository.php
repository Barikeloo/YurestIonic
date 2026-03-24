<?php

namespace App\Tables\Infrastructure\Persistence\Repositories;

use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\Zone\Infrastructure\Persistence\Models\EloquentZone;

class EloquentTableRepository implements TableRepositoryInterface
{
    public function __construct(
        private EloquentTable $model,
    ) {}

    public function save(Table $table): void
    {
        $zone = EloquentZone::query()->where('uuid', $table->zoneId())->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $table->id()->value()],
            [
                'zone_id' => $zone->id,
                'name' => $table->name(),
                'created_at' => $table->createdAt()->value(),
                'updated_at' => $table->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Table
    {
        $model = $this->model->newQuery()->with('zone')->where('uuid', $id)->first();

        if ($model === null || $model->zone === null) {
            return null;
        }

        return Table::fromPersistence(
            id: $model->uuid,
            zoneId: $model->zone->uuid,
            name: $model->name,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findAll(): array
    {
        $models = $this->model->newQuery()->with('zone')->orderBy('name')->get();

        return $models
            ->filter(static fn (EloquentTable $model): bool => $model->zone !== null)
            ->map(static fn (EloquentTable $model): Table => Table::fromPersistence(
                id: $model->uuid,
                zoneId: $model->zone->uuid,
                name: $model->name,
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
