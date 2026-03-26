<?php

namespace App\User\Infrastructure\Persistence\Repositories;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Support\Str;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EloquentUser $model,
    ) {}

    public function save(User $user): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $user->id()->value()],
            [
                'name' => $user->name(),
                'email' => $user->email()->value(),
                'password' => $user->passwordHash(),
                'created_at' => $user->createdAt()->value(),
                'updated_at' => $user->updatedAt()->value(),
            ]
        );
    }

    public function saveAdminForRestaurant(
        string $restaurantUuid,
        string $name,
        string $email,
        string $passwordHash,
    ): void {
        $restaurant = EloquentRestaurant::query()->where('uuid', $restaurantUuid)->first();

        if ($restaurant === null) {
            return;
        }

        $this->model->newQuery()->updateOrCreate(
            ['email' => $email],
            [
                'restaurant_id' => $restaurant->id,
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'pin' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function syncAdminCredentialsForRestaurant(
        string $restaurantUuid,
        ?string $email,
        ?string $passwordHash,
    ): void {
        if ($email === null && $passwordHash === null) {
            return;
        }

        $restaurant = EloquentRestaurant::query()->where('uuid', $restaurantUuid)->first();

        if ($restaurant === null) {
            return;
        }

        $updates = [
            'updated_at' => now(),
        ];

        if ($email !== null) {
            $updates['email'] = $email;
        }

        if ($passwordHash !== null) {
            $updates['password'] = $passwordHash;
        }

        $this->model
            ->newQuery()
            ->where('restaurant_id', $restaurant->id)
            ->where('role', 'admin')
            ->update($updates);
    }

    public function findById(string $id): ?User
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        if ($model === null) {
            return null;
        }

        return User::fromPersistence(
            $model->uuid,
            $model->name,
            $model->email,
            $model->password,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

    public function findByEmail(string $email): ?User
    {
        $model = $this->model->newQuery()->where('email', $email)->first();

        if ($model === null) {
            return null;
        }

        return User::fromPersistence(
            $model->uuid,
            $model->name,
            $model->email,
            $model->password,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<array{uuid: string, name: string, email: string, role: string}>
     */
    public function getByRestaurantUuid(string $restaurantUuid): array
    {
        $restaurant = EloquentRestaurant::query()->where('uuid', $restaurantUuid)->first();

        if ($restaurant === null) {
            return [];
        }

        return $this->model
            ->newQuery()
            ->where('restaurant_id', $restaurant->id)
            ->select('uuid', 'name', 'email', 'role')
            ->get()
            ->map(fn ($user) => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ])
            ->toArray();
    }

    /**
     * @param array<string, string> $updates
     */
    public function updatePartial(string $uuid, array $updates): void
    {
        $this->model
            ->newQuery()
            ->where('uuid', $uuid)
            ->update([...$updates, 'updated_at' => now()]);
    }

    public function delete(string $uuid): void
    {
        $this->model->newQuery()->where('uuid', $uuid)->delete();
    }

    public function saveWithRestaurant(
        string $uuid,
        string $name,
        string $email,
        string $passwordHash,
        string $restaurantUuid,
        string $role = 'operator',
    ): void {
        $restaurant = EloquentRestaurant::query()->where('uuid', $restaurantUuid)->first();

        if ($restaurant === null) {
            return;
        }

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $uuid],
            [
                'restaurant_id' => $restaurant->id,
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
