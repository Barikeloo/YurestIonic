<?php

namespace App\Restaurant\Infrastructure\Persistence\Repositories;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

final class EloquentRestaurantRepository implements RestaurantRepositoryInterface
{
    public function save(Restaurant $restaurant): void
    {
        EloquentRestaurant::updateOrCreate(
            ['uuid' => $restaurant->getId()->value()],
            [
                'name' => $restaurant->getName(),
                'legal_name' => $restaurant->getLegalName(),
                'tax_id' => $restaurant->getTaxId(),
                'email' => $restaurant->getEmail()->value(),
                'password' => $restaurant->getPassword(),
            ],
        );
    }

    public function all(): array
    {
        return EloquentRestaurant::query()->get()->map(fn ($model) => $this->toDomain($model))->all();
    }

    public function getById(string $id): ?Restaurant
    {
        $model = EloquentRestaurant::where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Restaurant
    {
        $model = EloquentRestaurant::where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email): ?Restaurant
    {
        $model = EloquentRestaurant::where('email', $email->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Restaurant
    {
        $model = EloquentRestaurant::where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function delete(Uuid $id): void
    {
        EloquentRestaurant::where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentRestaurant $model): Restaurant
    {
        return Restaurant::hydrate(
            id: Uuid::create($model->uuid),
            uuid: Uuid::create($model->uuid),
            name: $model->name,
            legalName: $model->legal_name,
            taxId: $model->tax_id,
            email: Email::create($model->email),
            password: $model->password,
            createdAt: DomainDateTime::create($model->created_at->toDateTimeImmutable()),
            updatedAt: DomainDateTime::create($model->updated_at->toDateTimeImmutable()),
            deletedAt: $model->deleted_at ? DomainDateTime::create($model->deleted_at->toDateTimeImmutable()) : null,
        );
    }
}
