<?php

namespace App\Tables\Infrastructure\Persistence\Repositories;

use App\Shared\Domain\ValueObject\Uuid;
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
        $zone = EloquentZone::query()->where('uuid', $table->zoneId()->value())->firstOrFail();

        $layout = $table->layout();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $table->id()->value()],
            [
                'restaurant_id'         => $zone->restaurant_id,
                'zone_id'               => $zone->id,
                'name'                  => $table->name()->value(),
                'merged_table_group_id' => $table->mergedTableGroupId()?->value(),
                'pos_x'                 => $layout?->posX,
                'pos_y'                 => $layout?->posY,
                'width'                 => $layout?->width,
                'height'                => $layout?->height,
                'shape'                 => $layout?->shape ?? 'rect',
                'created_at'            => $table->createdAt()->value(),
                'updated_at'            => $table->updatedAt()->value(),
            ],
        );
    }

    public function findById(string $id): ?Table
    {
        $model = $this->model->newQuery()->with('zone')->where('uuid', $id)->first();

        if ($model === null || $model->zone === null) {
            return null;
        }

        return $this->hydrate($model);
    }

    public function findAll(bool $includeDeleted = false): array
    {
        $query = $this->model->newQuery()->with('zone')->orderBy('name');

        if ($includeDeleted) {
            $query->withTrashed();
        }

        return $query->get()
            ->filter(static fn (EloquentTable $m): bool => $m->zone !== null)
            ->map(fn (EloquentTable $m): Table => $this->hydrate($m))
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

    public function findByZoneIdAndName(Uuid $zoneId, string $name, ?string $excludeId = null): ?Table
    {
        $query = $this->model->newQuery()
            ->with('zone')
            ->whereHas('zone', function ($query) use ($zoneId) {
                $query->where('uuid', $zoneId->value());
            })
            ->whereRaw('LOWER(name) = LOWER(?)', [$name]);

        if ($excludeId !== null) {
            $query->where('uuid', '!=', $excludeId);
        }

        $model = $query->first();

        if ($model === null || $model->zone === null) {
            return null;
        }

        return $this->hydrate($model);
    }

    public function findByIds(array $ids): array
    {
        return $this->model->newQuery()
            ->with('zone')
            ->whereIn('uuid', $ids)
            ->get()
            ->filter(static fn (EloquentTable $m): bool => $m->zone !== null)
            ->map(fn (EloquentTable $m): Table => $this->hydrate($m))
            ->values()
            ->all();
    }

    public function findByMergedGroupId(string $groupId): array
    {
        return $this->model->newQuery()
            ->with('zone')
            ->where('merged_table_group_id', $groupId)
            ->get()
            ->filter(static fn (EloquentTable $m): bool => $m->zone !== null)
            ->map(fn (EloquentTable $m): Table => $this->hydrate($m))
            ->values()
            ->all();
    }

    private function hydrate(EloquentTable $model): Table
    {
        return Table::fromPersistence(
            id:                  $model->uuid,
            zoneId:              $model->zone->uuid,
            name:                $model->name,
            mergedTableGroupId:  $model->merged_table_group_id,
            createdAt:           $model->created_at->toDateTimeImmutable(),
            updatedAt:           $model->updated_at->toDateTimeImmutable(),
            posX:                $model->pos_x,
            posY:                $model->pos_y,
            width:               $model->width,
            height:              $model->height,
            shape:               $model->shape ?? 'rect',
        );
    }
}
