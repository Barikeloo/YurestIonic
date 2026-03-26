<?php

namespace App\User\Domain\Interfaces;

use App\User\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function saveAdminForRestaurant(
        string $restaurantUuid,
        string $name,
        string $email,
        string $passwordHash,
    ): void;

    public function syncAdminCredentialsForRestaurant(
        string $restaurantUuid,
        ?string $email,
        ?string $passwordHash,
    ): void;

    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @return array<array{uuid: string, name: string, email: string, role: string}>
     */
    public function getByRestaurantUuid(string $restaurantUuid): array;

    /**
     * @param array<string, string> $updates
     */
    public function updatePartial(string $uuid, array $updates): void;

    public function delete(string $uuid): void;

    public function saveWithRestaurant(
        string $uuid,
        string $name,
        string $email,
        string $passwordHash,
        string $restaurantUuid,
        string $role = 'operator',
    ): void;
}
