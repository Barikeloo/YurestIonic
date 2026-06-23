<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

interface CustomerOfferRepositoryInterface
{
    public function list(string $restaurantId): array;

    public function findByUuid(string $uuid, string $restaurantId): ?array;

    public function create(string $restaurantId, array $data): array;

    public function update(string $uuid, string $restaurantId, array $data): ?array;

    public function delete(string $uuid, string $restaurantId): bool;
}
