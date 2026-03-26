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
}
