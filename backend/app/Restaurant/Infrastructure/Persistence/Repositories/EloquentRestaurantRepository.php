<?php

namespace App\Restaurant\Infrastructure\Persistence\Repositories;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Support\Facades\DB;

final class EloquentRestaurantRepository implements RestaurantRepositoryInterface
{
    public function __construct(
        private EloquentRestaurant $model,
    ) {}

    public function save(Restaurant $restaurant): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $restaurant->id()->value()],
            [
                'name' => $restaurant->name()->value(),
                'legal_name' => $restaurant->legalName()?->value(),
                'tax_id' => $restaurant->taxId()?->value(),
                'email' => $restaurant->email()->value(),
                'password' => $restaurant->password()->value(),
            ],
        );
    }

    public function all(): array
    {
        return $this->model->newQuery()->get()->map(fn ($model) => $this->toDomain($model))->all();
    }

    public function getById(string $id): ?Restaurant
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Restaurant
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?Restaurant
    {
        $model = $this->model->newQuery()->where('email', $email->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Restaurant
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByInternalId(int $internalId): ?Restaurant
    {
        $model = $this->model->newQuery()->where('id', $internalId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByTaxId(string $taxId): array
    {
        return $this->model->newQuery()
            ->where('tax_id', $taxId)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    public function getKpisByUuid(Uuid $uuid): array
    {
        $restaurantId = $this->model->newQuery()
            ->where('uuid', $uuid->value())
            ->value('id');

        if (! is_numeric($restaurantId)) {
            return [
                'users' => 0,
                'zones' => 0,
                'products' => 0,
            ];
        }

        $restaurantId = (int) $restaurantId;

        return [
            'users' => DB::table('users')->where('restaurant_id', $restaurantId)->whereNull('deleted_at')->count(),
            'zones' => DB::table('zones')->where('restaurant_id', $restaurantId)->whereNull('deleted_at')->count(),
            'products' => DB::table('products')->where('restaurant_id', $restaurantId)->whereNull('deleted_at')->count(),
        ];
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentRestaurant $model): Restaurant
    {
        return Restaurant::fromPersistence(
            $model->uuid,
            $model->uuid,
            $model->name,
            $model->legal_name,
            $model->tax_id,
            $model->email,
            $model->password,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}