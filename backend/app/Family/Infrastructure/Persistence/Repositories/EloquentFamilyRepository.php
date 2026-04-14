<?php

namespace App\Family\Infrastructure\Persistence\Repositories;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use App\Family\Infrastructure\Persistence\Models\EloquentFamily;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Tenant\TenantContext;

class EloquentFamilyRepository implements FamilyRepositoryInterface
{
    public function __construct(
        private EloquentFamily $model,
        private TenantContext $tenantContext,
    ) {}

    public function save(Family $family): void
    {
        $restaurantId = $this->tenantContext->requireRestaurantId();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $family->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'name' => $family->name()->value(),
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

        return Family::hydrate(
            id: Uuid::create($model->uuid),
            name: FamilyName::create($model->name),
            active: (bool) $model->active,
            createdAt: DomainDateTime::create($model->created_at->toDateTimeImmutable()),
            updatedAt: DomainDateTime::create($model->updated_at->toDateTimeImmutable()),
        );
    }

    public function findAll(bool $includeDeleted = false): array
    {
        $query = $this->model->newQuery()->orderBy('name');

        if ($includeDeleted) {
            $query->withTrashed();
        }

        $models = $query->get();

        return $models->map(static fn (EloquentFamily $model): Family => Family::hydrate(
            id: Uuid::create($model->uuid),
            name: FamilyName::create($model->name),
            active: (bool) $model->active,
            createdAt: DomainDateTime::create($model->created_at->toDateTimeImmutable()),
            updatedAt: DomainDateTime::create($model->updated_at->toDateTimeImmutable()),
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
